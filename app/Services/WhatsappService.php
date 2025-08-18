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

        // 2) Create a run for your Assistant — add style + length controls here
        $run = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
        ])->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
            'assistant_id' => $this->assistantId,

            // Human + concise WhatsApp tone (per-run override/augment)
            'instructions' => implode("\n", [
                "WhatsApp tone: friendly, clear, human.",
                "Cap replies at 2–4 short sentences (≈60–90 words).",
                "Avoid fluff and long lists. If needed, 3 bullets max, 6–9 words each.",
                "Prefer simple HTML: <p>...</p> and optional <ul><li>...</li></ul> only.",
                "End with one simple next step or question when helpful.",
            ]),

            // Length controls (Assistants v2)
            'max_completion_tokens' => 120,  // output cap
            'max_prompt_tokens'     => 2000, // keep context lean so replies stay focused
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
            'limit' => 5,
            'order' => 'desc',
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

        // Fallback (short, WhatsApp-friendly)
        return '<p>Thanks for your message — how can I help further?</p>';
    }


    // In WhatsappService

    protected function htmlToWhatsappText(string $html): string
    {
        // Normalise <br> and paragraph breaks to newlines
        $normalized = preg_replace('/<\s*br\s*\/?>/i', "\n", $html);
        $normalized = preg_replace('/<\/\s*p\s*>/i', "\n\n", $normalized);

        // Remove all remaining tags
        $text = strip_tags($normalized);

        // Decode entities (&nbsp; → space, etc.)
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Trim excess whitespace/newlines
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }
}
