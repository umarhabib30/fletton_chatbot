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
    protected Client $twilio;
    protected string $whatsappFrom;
    protected string $openAiKey;

    /** Your Assistant ID (Flettons Customer Services) as configured in OpenAI. */
    protected string $assistantId = 'asst_PY5ZXiliSAQjA7scJ8mTdR66';

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $this->whatsappFrom = (string) config('services.twilio.whatsapp_from');
        $this->openAiKey    = (string) config('services.openai.key');
    }

    // send first template message (unchanged)
    public function sendWhatsAppMessage($request)
    {
        $recipientNumber = 'whatsapp:+923096176606';
        $friendlyName    = '+923096176606';
        $message         = 'Hello from Programming Experience';
        $contentSid      = 'HX1ae7bac573156bfd28607c4d45fb2957';

        $twilio       = $this->twilio;
        $proxyAddress = $this->whatsappFrom;

        try {
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

            $msg = $twilio->conversations->v1->conversations($existingSid)
                ->messages
                ->create([
                    'author'           => 'system',
                    'body'             => $message,
                    'contentSid'       => $contentSid,
                    'contentVariables' => json_encode(["1" => "Simon", "2" => "habib"]),
                ]);

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
            return response()->json(['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function sendCustomMessage(string $conversationSid, string $message)
    {
        try {
            $msg = $this->twilio->conversations->v1
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

    public function getConversations(): array
    {
        try {
            $convs = $this->twilio->conversations->v1->conversations->read();

            return array_map(fn($c) => [
                'sid'           => $c->sid,
                'friendly_name' => $c->friendlyName,
                'state'         => $c->state,
                'date_created'  => $c->dateCreated ? $c->dateCreated->format('Y-m-d H:i:s') : null,
            ], $convs);
        } catch (\Exception $e) {
            // >>> CHANGED: return empty list instead of error array (callers expect an array of conversations)
            Log::warning('getConversations failed: '.$e->getMessage());
            return [];
        }
    }

    public function getMessages(string $conversationSid): array
    {
        try {
            $msgs = $this->twilio->conversations->v1
                ->conversations($conversationSid)
                ->messages
                ->read();

            return array_map(fn($m) => [
                'sid'          => $m->sid,
                'author'       => $m->author,
                'body'         => $m->body,
                'date_created' => $m->dateCreated ? $m->dateCreated->format('Y-m-d H:i:s') : null,
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

        // Emit user message to your UI (even if conversation not found yet)
        event(new MessageSent($userText, $conversationSid, 'user'));

        // If no conversation exists yet, create one now so we can reply back reliably
        if (!$conversationSid) {
            try {
                $conversation = $this->twilio->conversations->v1->conversations
                    ->create(['friendlyName' => $userNumber]);
                $conversationSid = $conversation->sid;

                // Ensure participant is added
                try {
                    $this->twilio->conversations->v1->conversations($conversationSid)
                        ->participants
                        ->create([
                            'messagingBindingAddress'      => 'whatsapp:'.$userNumber,
                            'messagingBindingProxyAddress' => $this->whatsappFrom,
                        ]);
                } catch (\Twilio\Exceptions\RestException $e) {
                    if ($e->getStatusCode() != 409) {
                        throw $e;
                    }
                }

                // default ChatControll row
                ChatControll::updateOrCreate(
                    ['sid' => $conversationSid],
                    ['contact' => $userNumber, 'auto_reply' => true]
                );
            } catch (\Throwable $e) {
                Log::error('Failed to ensure conversation: '.$e->getMessage());
            }
        }

        // If auto reply is off, do not call OpenAI
        $chatControll = $conversationSid
            ? ChatControll::where('sid', $conversationSid)->first()
            : null;

        if ($chatControll && $chatControll->auto_reply === false) {
            return response()->noContent();
        }

        // Run the assistant and fetch an HTML reply
        try {
            $replyHtml = $this->runAssistantAndGetReply($userNumber, $userText);
            $replyText = $this->htmlToWhatsappText($replyHtml);

            // Send reply into your chat & WhatsApp
            if ($conversationSid) {
                $this->sendCustomMessage($conversationSid, $replyText);
            }
            event(new MessageSent($replyText, $conversationSid, 'admin'));
        } catch (\Throwable $e) {
            Log::error('Assistant error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // Soft fallback to avoid loops
            $fallback = 'Thanks for your message — one moment while I check that.';
            if ($conversationSid) {
                $this->sendCustomMessage($conversationSid, $fallback);
            }
            event(new MessageSent($fallback, $conversationSid, 'admin'));
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
     * IMPORTANT: We let the configured Assistant’s own system prompt & tools drive behaviour.
     * We do NOT override with per-run "instructions".
     */
    protected function runAssistantAndGetReply(string $userNumber, string $userText): string
    {
        $threadId = $this->getOrCreateThreadId($userNumber);

        // 1) Add the user message to the thread
        $addMsg = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
        ])->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
            'role'    => 'user',
            'content' => $userText,
        ]);

        if (!$addMsg->ok()) {
            throw new \RuntimeException('Failed to add message: ' . $addMsg->body());
        }

        // 2) Create a run for your Assistant — no per-run instructions
        $run = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
        ])->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
            'assistant_id'          => $this->assistantId,
            'max_completion_tokens' => 200,   // small but safe
            'max_prompt_tokens'     => 4000,  // allow thread context
        ]);

        if (!$run->ok()) {
            throw new \RuntimeException('Failed to create run: ' . $run->body());
        }

        $runId = (string) data_get($run->json(), 'id');

        // 3) Poll until completed (simple backoff loop)
        $maxWaitSeconds = 45;
        $sleepMs = 600;
        $elapsed = 0;

        while (true) {
            usleep($sleepMs * 1000);
            $elapsed += $sleepMs / 1000;

            $statusResp = Http::withHeaders([
                'Authorization' => "Bearer {$this->openAiKey}",
                'Content-Type'  => 'application/json',
                'OpenAI-Beta'   => 'assistants=v2',
            ])->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");

            if (!$statusResp->ok()) {
                throw new \RuntimeException('Failed to check run: ' . $statusResp->body());
            }

            $status = (string) data_get($statusResp->json(), 'status', 'queued');

            if ($status === 'completed') break;

            // (Optional) If your assistant uses tools requiring action, you could handle here.
            if (in_array($status, ['failed', 'cancelled', 'expired'], true)) {
                $lastError = data_get($statusResp->json(), 'last_error.message') ?? 'unknown error';
                throw new \RuntimeException("Run {$status}: {$lastError}");
            }

            if ($elapsed >= $maxWaitSeconds) {
                throw new \RuntimeException('Run timed out waiting for completion.');
            }

            if ($sleepMs < 1500) $sleepMs += 150; // mild backoff
        }

        // 4) Fetch the latest assistant message (most recent first)
        $messagesResp = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
        ])->get("https://api.openai.com/v1/threads/{$threadId}/messages", [
            'limit' => 10,
            'order' => 'desc',
        ]);

        if (!$messagesResp->ok()) {
            throw new \RuntimeException('Failed to list messages: ' . $messagesResp->body());
        }

        $items = (array) data_get($messagesResp->json(), 'data', []);
        foreach ($items as $msg) {
            if (($msg['role'] ?? '') !== 'assistant') continue;
            foreach ((array) ($msg['content'] ?? []) as $block) {
                // Assistants v2 "text" block
                if (($block['type'] ?? '') === 'text') {
                    $val = (string) data_get($block, 'text.value', '');
                    if ($val !== '') return $val;
                }
                // Some SDKs/versions surface "output_text"
                if (($block['type'] ?? '') === 'output_text') {
                    $val = (string) data_get($block, 'output_text', '');
                    if ($val !== '') return $val;
                }
            }
        }

        // Fallback (short, WhatsApp-friendly)
        return '<p>Thanks for your message — how can I help further?</p>';
    }

    /**
     * Convert simple HTML to WhatsApp-friendly text.
     * - Preserves paragraphs and simple lists
     * - Replaces <a> with its href
     */
    protected function htmlToWhatsappText(string $html): string
    {
        $text = $html;

        // Normalise <br> and paragraph endings to newlines
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\/\s*p\s*>/i', "\n\n", $text);

        // Convert <li> to "- " lines
        $text = preg_replace('/<\s*li\s*>/i', "- ", $text);
        $text = preg_replace('/<\/\s*li\s*>/i', "\n", $text);
        // End of lists insert a blank line
        $text = preg_replace('/<\/\s*ul\s*>/i', "\n", $text);
        $text = preg_replace('/<\/\s*ol\s*>/i', "\n", $text);

        // Replace anchors with just their href (prefer URL over anchor text)
        $text = preg_replace_callback('~<a\b[^>]*>(.*?)</a>~is', function ($m) {
            $tag = $m[0];
            if (preg_match('~href\s*=\s*([\'"])(.*?)\1~i', $tag, $hrefMatch)) {
                return $hrefMatch[2];
            }
            return isset($m[1]) ? strip_tags($m[1]) : '';
        }, $text);

        // Remove all remaining tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Tidy whitespace
        $text = preg_replace("/\r\n?/", "\n", $text);   // CRLF → LF
        $text = preg_replace("/\n{3,}/", "\n\n", $text); // collapse 3+ NL to 2
        $text = preg_replace('/[ \t]{2,}/', ' ', $text); // collapse runs of spaces/tabs
        $text = trim($text);

        return $text;
    }
}
