<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\ChatControll;
use Twilio\Rest\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WhatsappService
{
    /**
     * @var Client
     */
    protected $twilio;

    /**
     * @var string
     */
    protected $whatsappFrom;

    /**
     * @var string
     */
    protected $openAiKey;

    /**
     * Your Assistant ID (Flettons Customer Services)
     * as configured in OpenAI.
     */
    protected string $assistantId = 'asst_PY5ZXiliSAQjA7scJ8mTdR66';

    public function __construct()
    {
        // load Twilio creds from config/services.php → .env
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $this->whatsappFrom = config('services.twilio.whatsapp_from');

        // load OpenAI key from config/services.php → .env
        $this->openAiKey = config('services.openai.key');
    }

    // send first template message
    public function sendWhatsAppMessage($request)
    {
        $recipientNumber = 'whatsapp:+923096176606';
        $friendlyName    = '+923096176606';
        $message         = 'Hello from Programming Experience';
        $contentSid      = 'HX1ae7bac573156bfd28607c4d45fb2957';

        $twilio       = $this->twilio;          // \Twilio\Rest\Client
        $proxyAddress = $this->whatsappFrom;    // e.g. 'whatsapp:+14155238886'

        try {
            // 0) Try to find an existing conversation for this participant (and proxy)
            $existingSid = null;
            $pcs = $twilio->conversations->v1->participantConversations
                ->read(['address' => $recipientNumber], 20); // Twilio SDK url-encodes the '+'

            foreach ($pcs as $pc) {
                // Defensive: make sure we match the same proxy/sender
                $binding = $pc->participantMessagingBinding ?? null;
                if ($binding && isset($binding['proxy_address']) && $binding['proxy_address'] === $proxyAddress) {
                    $existingSid = $pc->conversationSid;
                    break;
                }
            }

            if (!$existingSid) {
                // 1) Create a new conversation
                $conversation = $twilio->conversations->v1->conversations
                    ->create(['friendlyName' => $friendlyName]);

                $existingSid = $conversation->sid;

                // 2) Add participant (may 409 if a race condition; ignore if so)
                try {
                    $twilio->conversations->v1->conversations($existingSid)
                        ->participants
                        ->create([
                            'messagingBindingAddress'       => $recipientNumber,
                            'messagingBindingProxyAddress'  => $proxyAddress,
                        ]);
                } catch (\Twilio\Exceptions\RestException $e) {
                    // 50437/50416 → participant or binding already exists; proceed
                    if ($e->getStatusCode() != 409) {
                        throw $e;
                    }
                }
            }

            // 3) Send your (template) message on the located/created conversation
            $msg = $twilio->conversations->v1->conversations($existingSid)
                ->messages
                ->create([
                    'author'           => 'system',
                    'body'             => $message,        // optional if you rely on contentSid only
                    'contentSid'       => $contentSid,     // WhatsApp template via Content API
                    'contentVariables' => json_encode(["1" => "Simon", "2" => "habib"]),
                ]);

            // 4) Persist for next time so you can jump straight to sending
            ChatControll::updateOrCreate(
                ['sid' => $existingSid],
                ['contact' => $friendlyName, 'auto_reply' => true]
            );

            return response()->json([
                'message'          => 'WhatsApp message sent successfully',
                'conversation_sid' => $existingSid,
                'message_sid'      => $msg->sid,
            ]);
        } catch (\Twilio\Exceptions\RestException $e) {
            // Fallback: if Twilio already told us the conversation sid in the 409 message, you can parse it and retry send
            return response()->json(['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Send a custom conversation message
     */
    public function sendCustomMessage(string $conversationSid, string $message)
    {
        try {
            $msg = $this->twilio
                ->conversations
                ->v1
                ->conversations($conversationSid)
                ->messages
                ->create([
                    'author' => 'system',
                    'body'   => $message,
                ]);

            return response()->json([
                'message'         => 'Sent via Conversation',
                'conversationSid' => $conversationSid,
                'messageSid'      => $msg->sid,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all conversations
     */
    public function getConversations(): array
    {
        try {
            $convs = $this->twilio
                ->conversations
                ->v1
                ->conversations
                ->read();

            return array_map(fn($c) => [
                'sid'           => $c->sid,
                'friendly_name' => $c->friendlyName,
                'state'         => $c->state,
                'date_created'  => $c->dateCreated->format('Y-m-d H:i:s'),
            ], $convs);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get messages for one conversation
     */
    public function getMessages(string $conversationSid): array
    {
        try {
            $msgs = $this->twilio
                ->conversations
                ->v1
                ->conversations($conversationSid)
                ->messages
                ->read();

            return array_map(fn($m) => [
                'sid'          => $m->sid,
                'author'       => $m->author,
                'body'         => $m->body,
                'date_created' => $m->dateCreated->format('Y-m-d H:i:s'),
            ], $msgs);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete a conversation
     */
    public function deleteConversation(string $conversationSid)
    {
        try {
            $this->twilio
                ->conversations
                ->v1
                ->conversations($conversationSid)
                ->delete();

            return response()->json([
                'message'          => 'Conversation deleted successfully',
                'conversation_sid' => $conversationSid,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle an incoming WhatsApp message → run Assistants API (v2) → reply with Assistant HTML
     */
    public function handleIncoming(Request $request)
    {
        Log::info('WhatsApp webhook payload:', $request->all());

        $userText = trim((string) $request->input('Body', ''));
        if ($userText === '') {
            return response()->noContent();
        }

        // Normalise WhatsApp number and find Twilio Conversation SID
        $userNumber = str_replace('whatsapp:', '', (string) $request->input('From', ''));
        $conversations = $this->getConversations();
        $conversationSid = null;
        foreach ($conversations as $conv) {
            if (($conv['friendly_name'] ?? null) === $userNumber) {
                $conversationSid = $conv['sid'];
                break;
            }
        }

        // Emit user message to your UI
        event(new MessageSent($userText, $conversationSid, 'user'));

        // if auto reply is off it will not call gpt api
        $chatControll = ChatControll::where('sid', $conversationSid)->first();
        if (!$chatControll->auto_reply) {
            return response()->noContent();
        }

        // Run the assistant and fetch an HTML reply
        try {
            $replyHtml = $this->runAssistantAndGetReply($userNumber, $userText);
            $replyText = $this->htmlToWhatsappText($replyHtml);
            // $replyText = $replyHtml;
            // Send reply into your chat & WhatsApp
            $this->sendCustomMessage($conversationSid, $replyText);
            event(new MessageSent($replyText, $conversationSid, 'admin'));
        } catch (\Throwable $e) {
            Log::error('Assistant error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $replyText = 'Please wait';
        }

        return response()->noContent();
    }

    /**
     * Create or reuse a Thread ID for this WhatsApp number.
     * Uses Cache forever; switch to DB if you prefer persistent mapping.
     */
    protected function getOrCreateThreadId(string $userNumber): string
    {
        $cacheKey = "flettons:assistant_thread:{$userNumber}";

        return Cache::rememberForever($cacheKey, function () {
            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$this->openAiKey}",
                'Content-Type'  => 'application/json',
                'OpenAI-Beta'   => 'assistants=v2',
            ])->post('https://api.openai.com/v1/threads', []);

            if (!$resp->ok()) {
                throw new \RuntimeException('Failed to create thread: ' . $resp->body());
            }

            return (string) data_get($resp->json(), 'id');
        });
    }

    /**
     * Run the Assistant on the user’s thread and return the latest assistant HTML.
     */
    protected function runAssistantAndGetReply(string $userNumber, string $userText): string
    {
        $cacheKey = "flettons:assistant_thread:{$userNumber}";
        $vectorStoreId = 'vs_68a475c65ef48191bc346af37caebe76';

        if (empty($this->assistantId)) {
            throw new \RuntimeException('assistantId missing');
        }
        if (empty($this->openAiKey)) {
            throw new \RuntimeException('OPENAI_API_KEY missing');
        }

        $headers = [
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
        ];

        // Helper to add a message to a thread (with logging)
        $addMessageToThread = function (string $threadId) use ($userText, $headers) {
            $resp = Http::withHeaders($headers)->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
                'role'    => 'user',
                'content' => $userText,
            ]);

            Log::debug('Assistants: add message response', [
                'thread_id' => $threadId,
                'status'    => $resp->status(),
                'ok'        => $resp->ok(),
                'req_id'    => $resp->header('x-request-id'),
                'body'      => $resp->json(),
            ]);

            return $resp;
        };

        // 0) Ensure we have a valid thread (recreate if stale)
        $threadId = $this->getOrCreateThreadId($userNumber);
        $addMsg   = $addMessageToThread($threadId);

        if ($addMsg->status() === 404) {
            Log::warning('Assistants: thread 404, recreating', [
                'thread_id'   => $threadId,
                'user_number' => $userNumber,
            ]);

            Cache::forget($cacheKey);
            $threadId = $this->getOrCreateThreadId($userNumber);
            $addMsg   = $addMessageToThread($threadId);
        }

        if (!$addMsg->ok()) {
            throw new \RuntimeException('Failed to add message (status ' . $addMsg->status() . ', req_id: ' . $addMsg->header('x-request-id') . '): ' . $addMsg->body());
        }

        // 1) Create a run (attach vector store, force file_search, and add strict instructions)
        $runCreatePayload = [
            'assistant_id' => $this->assistantId,
            'temperature'  => 0,
            'instructions' => implode("\n", [
                "You are a strict retrieval bot for Flettons.",
                "Rules: it is an assistant designed to provide customer services within Flettons Surveyors.",
                "Output must be valid json with a single text field called 'answer'.",
                "1) ALWAYS search the attached knowledge base (file_search) first.",
                "2) ONLY answer using passages retrieved from the KB that directly match the user's question.",
                "3) If nothing relevant is found, reply exactly: \"I couldn’t find this in the knowledge base.\"",
                "4) Be concise. No generic marketing/booking lines unless the user explicitly asks.",
                "5) Cite source filenames inline like [Doc: <filename>] but do not include raw engine tokens.",
            ]),
            'tool_resources' => [
                'file_search' => [
                    'vector_store_ids' => [$vectorStoreId],
                ],
            ],
            'tool_choice' => ['type' => 'file_search'],
        ];


        // Create run with targeted retries on 5xx
        $maxAttempts = 3;
        $attempt     = 0;
        $run         = null;

        do {
            $attempt++;

            $run = Http::withHeaders($headers)
                ->post("https://api.openai.com/v1/threads/{$threadId}/runs", $runCreatePayload);

            $reqId = $run->header('x-request-id');

            Log::debug('Assistants: run create attempt', [
                'attempt' => $attempt,
                'status'  => $run->status(),
                'ok'      => $run->ok(),
                'req_id'  => $reqId,
                'payload' => $runCreatePayload,
                'body'    => $run->json(),
            ]);

            if ($run->ok()) break;

            if ($run->status() >= 500 && $run->status() < 600 && $attempt < $maxAttempts) {
                usleep(250000 * $attempt); // 250ms, 500ms...
                continue;
            }

            throw new \RuntimeException('Failed to create run (status ' . $run->status() . ", req_id: {$reqId}): " . $run->body());
        } while ($attempt < $maxAttempts);

        $runId = (string) data_get($run->json(), 'id');
        if (!$runId) {
            throw new \RuntimeException('Run created but no id returned (req_id: ' . $run->header('x-request-id') . ')');
        }

        // 2) Poll until completion
        $maxWaitSeconds = 45;
        $sleepMs        = 600;
        $elapsed        = 0;

        while (true) {
            usleep($sleepMs * 1000);
            $elapsed += $sleepMs / 1000;

            $statusResp = Http::withHeaders($headers)
                ->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");

            if (!$statusResp->ok()) {
                Log::error('Assistants: failed to check run', [
                    'thread_id' => $threadId,
                    'run_id'    => $runId,
                    'status'    => $statusResp->status(),
                    'req_id'    => $statusResp->header('x-request-id'),
                    'body'      => $statusResp->body(),
                ]);
                throw new \RuntimeException('Failed to check run (status ' . $statusResp->status() . ', req_id: ' . $statusResp->header('x-request-id') . '): ' . $statusResp->body());
            }

            $status = (string) data_get($statusResp->json(), 'status', 'queued');

            Log::debug('Assistants: run status tick', [
                'thread_id' => $threadId,
                'run_id'    => $runId,
                'status'    => $status,
                'elapsed_s' => $elapsed,
            ]);

            if ($status === 'completed') break;

            if ($status === 'requires_action') {
                $toolCalls = data_get($statusResp->json(), 'required_action.submit_tool_outputs.tool_calls', []);
                Log::warning('Assistants: run requires tool action (not implemented)', [
                    'thread_id'  => $threadId,
                    'run_id'     => $runId,
                    'tool_calls' => $toolCalls,
                ]);
                throw new \RuntimeException('Run requires tool action but no tool outputs were provided.');
            }

            if (in_array($status, ['failed', 'cancelled', 'expired'], true)) {
                $lastError = data_get($statusResp->json(), 'last_error.message') ?? 'unknown error';
                Log::error('Assistants: run terminal error', [
                    'thread_id'  => $threadId,
                    'run_id'     => $runId,
                    'status'     => $status,
                    'last_error' => $lastError,
                    'full'       => $statusResp->json(),
                ]);
                throw new \RuntimeException("Run {$status}: {$lastError}");
            }

            if ($elapsed >= $maxWaitSeconds) {
                Log::error('Assistants: run timed out', [
                    'thread_id' => $threadId,
                    'run_id'    => $runId,
                    'last_seen' => $status,
                ]);
                throw new \RuntimeException('Run timed out waiting for completion.');
            }

            if ($sleepMs < 1500) $sleepMs += 150;
        }

        // 3) Fetch the latest assistant message and strip engine-style citations before sending to WhatsApp
        $messagesResp = Http::withHeaders($headers)
            ->get("https://api.openai.com/v1/threads/{$threadId}/messages", [
                'limit' => 5,
                'order' => 'desc',
            ]);

        Log::debug('Assistants: messages fetch', [
            'thread_id' => $threadId,
            'status'    => $messagesResp->status(),
            'ok'        => $messagesResp->ok(),
            'req_id'    => $messagesResp->header('x-request-id'),
            'body'      => $messagesResp->json(),
        ]);

        if (!$messagesResp->ok()) {
            throw new \RuntimeException('Failed to list messages (status ' . $messagesResp->status() . ', req_id: ' . $messagesResp->header('x-request-id') . '): ' . $messagesResp->body());
        }

        $items = (array) data_get($messagesResp->json(), 'data', []);
        foreach ($items as $msg) {
            if (($msg['role'] ?? '') !== 'assistant') continue;
            foreach (($msg['content'] ?? []) as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $val = (string) data_get($block, 'text.value', '');
                    if ($val !== '') {
                        // Remove raw engine-style citations like:
                        $val = preg_replace('/\x{3010}.*?\x{3011}/u', '', $val); // strip anything between 【 and 】
                        // Optional: keep our friendlier citation style if you included [Doc: <filename>] in instructions.
                        return trim($val);
                    }
                }
            }
        }

        return '<p>Thanks for your message — how can I help further?</p>';
    }




    // In WhatsappService

    protected function htmlToWhatsappText(string $html): string
    {
        // 1) Normalise <br> and paragraph breaks to newlines
        $normalized = preg_replace('/<\s*br\s*\/?>/i', "\n", $html);
        $normalized = preg_replace('/<\/\s*p\s*>/i', "\n\n", $normalized);

        // 2) Replace anchors with just their href (remove anchor text)
        //    e.g. <a href="https://x.com">Click here</a> => https://x.com
        $normalized = preg_replace_callback('~<a\b[^>]*>(.*?)</a>~is', function ($m) {
            $tag = $m[0];
            if (preg_match('~href\s*=\s*([\'"])(.*?)\1~i', $tag, $hrefMatch)) {
                return $hrefMatch[2]; // keep only the URL
            }
            // No href found: drop the tag but keep inner text as last resort
            return isset($m[1]) ? strip_tags($m[1]) : '';
        }, $normalized);

        // 3) Remove all remaining tags
        $text = strip_tags($normalized);

        // 4) Decode entities (&nbsp; → space, etc.)
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 5) Trim & tidy whitespace/newlines
        //    - Collapse 3+ newlines to 2
        //    - Convert Windows/Mac line endings if any slipped through
        $text = preg_replace("/\r\n?/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);

        return trim($text);
    }
}
