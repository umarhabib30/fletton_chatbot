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

        // Helper to add a message to a thread (with logging)
        $addMessageToThread = function (string $threadId) use ($userText) {
            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$this->openAiKey}",
                'Content-Type'  => 'application/json',
                'OpenAI-Beta'   => 'assistants=v2',
            ])->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
                'role'    => 'user',
                'content' => $userText,
            ]);

            Log::debug('Assistants: add message response', [
                'thread_id' => $threadId,
                'status'    => $resp->status(),
                'ok'        => $resp->ok(),
                'body'      => $resp->json(),
            ]);

            return $resp;
        };

        // 0) Ensure we have a valid thread (recreate if stale)
        $threadId = $this->getOrCreateThreadId($userNumber);
        $addMsg   = $addMessageToThread($threadId);

        if ($addMsg->status() === 404) {
            // Thread likely invalidated or purged – recreate once
            Log::warning('Assistants: thread 404, recreating', [
                'thread_id'   => $threadId,
                'user_number' => $userNumber,
            ]);

            Cache::forget($cacheKey);
            $threadId = $this->getOrCreateThreadId($userNumber);

            $addMsg = $addMessageToThread($threadId);
        }

        if (!$addMsg->ok()) {
            throw new \RuntimeException('Failed to add message: ' . $addMsg->body());
        }

        // 1) Create a run (FORCE file_search + attach your vector store + tighten retrieval)
        // Ensure you have set $this->vectorStoreId during your bootstrap (after uploading/ingesting files)
        if (empty($this->vectorStoreId)) {
            Log::error('Assistants: vector store id missing; file_search will not work as intended');
        }

        $runCreatePayload = [
            'assistant_id' => $this->assistantId,

            // Force the assistant to use file_search (prevents free-wheeling answers)
            'tool_choice'  => ['type' => 'file_search'],

            // You can re-specify tools at run time to override ranking knobs
            'tools' => [[
                'type' => 'file_search',
                'file_search' => [
                    'max_num_results' => 8, // keep the context tight
                    'ranking_options' => [
                        'score_threshold' => 0.55, // ignore weak matches
                        // 'ranker' => 'auto'
                    ],
                ],
            ]],

            // Attach your vector store(s) for this run
            'tool_resources' => [
                'file_search' => [
                    'vector_store_ids' => array_filter([$this->vectorStoreId ?? null]),
                ],
            ],

            // Make it less "creative"
            'temperature' => 0,

            // (Optional) Strong guardrails to keep answers grounded
            'instructions' => implode("\n", [
                "You are a strict RAG bot.",
                "Rules:",
                "1) ALWAYS call the file_search tool before answering.",
                "2) ONLY answer using retrieved passages; if nothing is relevant, say: \"I couldn’t find this in the knowledge base.\"",
                "3) Keep answers concise and cite the retrieved doc names inline like [Doc: <filename>].",
            ]),
        ];

        $run = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
        ])->post("https://api.openai.com/v1/threads/{$threadId}/runs", $runCreatePayload);

        Log::debug('Assistants: run created', [
            'thread_id' => $threadId,
            'status'    => $run->status(),
            'ok'        => $run->ok(),
            'body'      => $run->json(),
        ]);

        if (!$run->ok()) {
            throw new \RuntimeException('Failed to create run: ' . $run->body());
        }

        $runId = (string) data_get($run->json(), 'id');

        // 2) Poll until completion with detailed state logging
        $maxWaitSeconds = 45;
        $sleepMs        = 600;
        $elapsed        = 0;

        while (true) {
            usleep($sleepMs * 1000);
            $elapsed += $sleepMs / 1000;

            $statusResp = Http::withHeaders([
                'Authorization' => "Bearer {$this->openAiKey}",
                'Content-Type'  => 'application/json',
                'OpenAI-Beta'   => 'assistants=v2',
            ])->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");

            if (!$statusResp->ok()) {
                Log::error('Assistants: failed to check run', [
                    'thread_id' => $threadId,
                    'run_id'    => $runId,
                    'status'    => $statusResp->status(),
                    'body'      => $statusResp->body(),
                ]);
                throw new \RuntimeException('Failed to check run: ' . $statusResp->body());
            }

            $statusJson = $statusResp->json();
            $status     = (string) data_get($statusJson, 'status', 'queued');

            Log::debug('Assistants: run status tick', [
                'thread_id' => $threadId,
                'run_id'    => $runId,
                'status'    => $status,
                'elapsed_s' => $elapsed,
            ]);

            if ($status === 'completed') {
                break;
            }

            if ($status === 'requires_action') {
                $toolCalls = data_get($statusJson, 'required_action.submit_tool_outputs.tool_calls', []);
                Log::warning('Assistants: run requires tool action (not implemented)', [
                    'thread_id'  => $threadId,
                    'run_id'     => $runId,
                    'tool_calls' => $toolCalls,
                ]);
                throw new \RuntimeException('Run requires tool action but no tool outputs were provided.');
            }

            if (in_array($status, ['failed', 'cancelled', 'expired'], true)) {
                Log::error('Assistants: run terminal error', [
                    'thread_id'  => $threadId,
                    'run_id'     => $runId,
                    'status'     => $status,
                    'last_error' => data_get($statusJson, 'last_error', null),
                    'full'       => $statusJson,
                ]);
                $lastError = data_get($statusJson, 'last_error.message') ?? 'unknown error';
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

            if ($sleepMs < 1500) $sleepMs += 150; // mild backoff
        }

        // 2.1) OPTIONAL: verify that file_search was actually used (logs only)
        try {
            $stepsResp = Http::withHeaders([
                'Authorization' => "Bearer {$this->openAiKey}",
                'Content-Type'  => 'application/json',
                'OpenAI-Beta'   => 'assistants=v2',
            ])->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}/steps", [
                'limit' => 20,
            ]);

            $usedFileSearch = false;
            if ($stepsResp->ok()) {
                $steps = (array) data_get($stepsResp->json(), 'data', []);
                foreach ($steps as $s) {
                    $json = json_encode($s);
                    if (is_string($json) && str_contains($json, '"file_search"')) {
                        $usedFileSearch = true;
                        break;
                    }
                }
            }

            Log::info('Assistants: verification file_search usage', [
                'thread_id'        => $threadId,
                'run_id'           => $runId,
                'used_file_search' => $usedFileSearch,
                'steps'            => $stepsResp->json(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Assistants: failed to verify file_search usage', ['e' => $e->getMessage()]);
        }

        // 3) Fetch the latest assistant message (most recent first) + try to surface citations
        $messagesResp = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
        ])->get("https://api.openai.com/v1/threads/{$threadId}/messages", [
            'limit' => 5,
            'order' => 'desc',
        ]);

        Log::debug('Assistants: messages fetch', [
            'thread_id' => $threadId,
            'status'    => $messagesResp->status(),
            'ok'        => $messagesResp->ok(),
            'body'      => $messagesResp->json(),
        ]);

        if (!$messagesResp->ok()) {
            throw new \RuntimeException('Failed to list messages: ' . $messagesResp->body());
        }

        $answerHtml = null;
        $items = (array) data_get($messagesResp->json(), 'data', []);
        foreach ($items as $msg) {
            if (($msg['role'] ?? '') !== 'assistant') continue;

            foreach (($msg['content'] ?? []) as $block) {
                if (($block['type'] ?? '') !== 'text') continue;

                $val = (string) data_get($block, 'text.value', '');
                $anns = (array) data_get($block, 'text.annotations', []);

                // Convert simple file citations to a readable suffix (optional, tweak as you like)
                $citations = [];
                foreach ($anns as $ann) {
                    $type = $ann['type'] ?? '';
                    if ($type === 'file_citation') {
                        $fileId = data_get($ann, 'file_citation.file_id');
                        $quote  = trim((string) data_get($ann, 'text', ''));
                        if ($fileId) $citations[] = "[Source: {$fileId}]";
                    }
                }

                if ($val !== '') {
                    $answerHtml = $val . (count($citations) ? "<br><br><small>" . implode(' ', array_unique($citations)) . "</small>" : '');
                    break 2;
                }
            }
        }

        // Fallback (short, WhatsApp-friendly)
        return $answerHtml ?: '<p>Thanks for your message — how can I help further?</p>';
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
