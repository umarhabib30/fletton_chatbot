<?php

namespace App\Services;

use App\Events\MessageSent;
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
        $recipientNumber = 'whatsapp:+923466363116';
        $friendlyName = "+923466363116";
        $message = "Hello from Programming Experience";
        $contentSid = "HX1ae7bac573156bfd28607c4d45fb2957"; // Your ContentSid

        // Twilio SDK Client
        $twilio = $this->twilio;

        try {
            // Step 1: Create a new conversation
            $conversation = $twilio->conversations->v1->conversations->create([
                'friendlyName' => $friendlyName
            ]);

            // Step 2: Add participant (the WhatsApp user)
            $twilio->conversations->v1->conversations($conversation->sid)
                ->participants
                ->create([
                    'messagingBindingAddress' => $recipientNumber,
                    'messagingBindingProxyAddress' =>  $this->whatsappFrom
                ]);

            // Step 3: Send a message to the created conversation
            $messageInstance = $twilio->conversations->v1->conversations($conversation->sid)
                ->messages
                ->create([
                    'author' => 'system',
                    'body' => $message,
                    'contentSid' => $contentSid,
                    'contentVariables' => json_encode(["1" => "Simon", "2" => "habib"]) // Your dynamic variables
                ]);

            return response()->json(['message' => 'WhatsApp message sent successfully', 'conversation_sid' => $conversation->sid, 'message_sid' => $messageInstance->sid]);
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

        // Run the assistant and fetch an HTML reply
        try {
            $replyHtml = $this->runAssistantAndGetReply($userNumber, $userText);
            $replyText = $this->htmlToWhatsappText($replyHtml);
        } catch (\Throwable $e) {
            Log::error('Assistant error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $replyText = 'Sorry, something went wrong.';
        }

        // Send reply into your chat & WhatsApp
        $this->sendCustomMessage($conversationSid, $replyText);
        event(new MessageSent($replyText, $conversationSid, 'admin'));

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

        // 2) Create a run for your Assistant
        $run = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
        ])->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
            'assistant_id' => $this->assistantId,
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

            if ($status === 'completed') {
                break;
            }

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
            $contentBlocks = $msg['content'] ?? [];

            foreach ($contentBlocks as $block) {
                // Expecting 'text' blocks with the Assistant’s HTML string
                if (($block['type'] ?? '') === 'text') {
                    $val = (string) data_get($block, 'text.value', '');
                    if ($val !== '') {
                        return $val;
                    }
                }
                // (If you later use tools, you might parse 'tool_output' here.)
            }
        }

        // Fallback
        return 'Thanks for your message. We’ll be back in touch shortly.';
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
