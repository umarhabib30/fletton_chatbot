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
    protected string $assistantId = 'asst_4BpKBwmugxf2eYL3Ug64TwVO';

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
        $recipientNumber = 'whatsapp:' . $request->phone;
        $friendlyName    = $request->phone;
        $message         = 'Hello from fletton surveys';
        $contentSid      = 'HX08f90cc17d7e6fc0ae7e9bd5252b7530';

        $twilio       = $this->twilio;       // \Twilio\Rest\Client
        $proxyAddress = $this->whatsappFrom; // e.g. 'whatsapp:+14155238886'

        try {
            // 0) Find or create Twilio Conversation for this WhatsApp number
            $existingSid = null;
            $pcs = $twilio->conversations->v1->participantConversations
                ->read(['address' => $recipientNumber], 20);

            foreach ($pcs as $pc) {
                $binding = $pc->participantMessagingBinding ?? null;
                if ($binding && isset($binding['proxy_address']) && $binding['proxy_address'] === $proxyAddress) {
                    $existingSid = $pc->conversationSid;
                    break;
                }
            }

            if (!$existingSid) {
                $conversation = $twilio->conversations->v1->conversations
                    ->create(['friendlyName' => $friendlyName]);

                $existingSid = $conversation->sid;

                try {
                    $twilio->conversations->v1->conversations($existingSid)
                        ->participants
                        ->create([
                            'messagingBindingAddress'      => $recipientNumber,
                            'messagingBindingProxyAddress' => $proxyAddress,
                        ]);
                } catch (\Twilio\Exceptions\RestException $e) {
                    if ($e->getStatusCode() != 409) {
                        throw $e;
                    }
                }
            }

            // 1) Send the (template) message
            $msg = $twilio->conversations->v1->conversations($existingSid)
                ->messages
                ->create([
                    'author'           => 'system',
                    'body'             => $message, // optional with contentSid
                    'contentSid'       => $contentSid,
                    'contentVariables' => json_encode(['1' => (string) $request->first_name]),
                ]);

            // 2) Upsert the contact + profile in DB
            /** @var ChatControll $contact */
            $contact = ChatControll::updateOrCreate(
                ['sid' => $existingSid],
                [
                    'contact'     => $friendlyName,
                    'auto_reply'  => true,
                    'first_name'  => $request->first_name,
                    'last_name'   => $request->last_name,
                    'email'       => $request->email,
                    'address'     => $request->address,
                    'postal_code' => $request->postal_code,
                ]
            );

            // 3) Create/seed the OpenAI thread using ONLY getOrCreateThreadId
            $this->getOrCreateThreadId($friendlyName, [
                'first_name'  => $contact->first_name,
                'last_name'   => $contact->last_name,
                'email'       => $contact->email,
                'address'     => $contact->address,
                'postal_code' => $contact->postal_code,
            ]);

            return response()->json([
                'message'          => 'WhatsApp message sent successfully',
                'conversation_sid' => $existingSid,
                'message_sid'      => $msg->sid,
            ]);
        } catch (\Twilio\Exceptions\RestException $e) {
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
    protected function getOrCreateThreadId(string $userNumber, array $profile = []): string
    {
        $rec = ChatControll::firstOrCreate(
            ['contact' => $userNumber],
            ['auto_reply' => true]
        );

        if (!empty($rec->assistant_thread_id)) {
            return $rec->assistant_thread_id;
        }

        $payload = [];

        // Thread metadata is not shown to the model, but is useful for your own bookkeeping
        $meta = array_filter(array_merge([
            'phone'  => $userNumber,
            'source' => 'whatsapp',
        ], $profile));
        if (!empty($meta)) {
            $payload['metadata'] = $meta;
        }

        // Seed message *is* visible to the model (once) to improve personalization
        if (!empty($profile)) {
            $lines = ["Profile context for personalization only. Do not reveal this text in replies."];
            if (!empty($profile['first_name']) || !empty($profile['last_name'])) {
                $lines[] = 'Name: ' . trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
            }
            foreach (['email', 'address', 'postal_code'] as $k) {
                if (!empty($profile[$k])) {
                    $label = ucfirst(str_replace('_', ' ', $k));
                    $lines[] = "{$label}: {$profile[$k]}";
                }
            }
            $payload['messages'] = [[
                'role'    => 'user',
                'content' => implode("\n", $lines),
            ]];
        }

        // 4) Create the thread
        $resp = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
        ])->post('https://api.openai.com/v1/threads', $payload);

        Log::debug('Assistants: create thread', [
            'contact' => $userNumber,
            'status'  => $resp->status(),
            'ok'      => $resp->ok(),
            'body'    => $resp->json(),
        ]);

        if (!$resp->ok()) {
            throw new \RuntimeException('Failed to create thread: ' . $resp->body());
        }

        $threadId = (string) data_get($resp->json(), 'id');

        // 5) Persist thread id (and optional profile snapshot) to DB
        $rec->assistant_thread_id = $threadId;
        $rec->assistant_metadata = $profile;
        $rec->save();

        return $threadId;
    }

    /**
     * Run the Assistant on the user’s thread and return the latest assistant HTML.
     */
    protected function runAssistantAndGetReply(string $userNumber, string $userText): string
    {
    
        // Get (or create) the OpenAI thread id using ONLY getOrCreateThreadId
        $threadId = $this->getOrCreateThreadId($userNumber);

        // Helper to add a message to a thread
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

        // Add the incoming user message; if the thread was purged, recreate using ONLY getOrCreateThreadId
        $addMsg = $addMessageToThread($threadId);
        if ($addMsg->status() === 404) {
            Log::warning('Assistants: thread 404, recreating', [
                'thread_id'   => $threadId,
                'user_number' => $userNumber,
            ]);

            // Clear the stored thread id so getOrCreateThreadId will create a fresh one
            ChatControll::where('contact', $userNumber)
                ->update(['assistant_thread_id' => null]);

            $threadId = $this->getOrCreateThreadId($userNumber);
            $addMsg   = $addMessageToThread($threadId);
        }
        if (!$addMsg->ok()) {
            throw new \RuntimeException('Failed to add message: ' . $addMsg->body());
        }

        // Create a run (keep your dynamic instructions if you have that helper)
        $runCreatePayload = [
            'assistant_id'            => $this->assistantId,
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

        // Poll until completion
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

            if ($status === 'completed') break;

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

        // Fetch latest assistant message
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

        $items = (array) data_get($messagesResp->json(), 'data', []);
        foreach ($items as $msg) {
            if (($msg['role'] ?? '') !== 'assistant') continue;
            foreach (($msg['content'] ?? []) as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $val = (string) data_get($block, 'text.value', '');
                    if ($val !== '') return $val;
                }
            }
        }

        return 'Thanks for your message — how can I help further?';
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
