<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Mail\AssistantFailureMail;
use App\Models\ChatControll;
use App\Models\ChatHistory;
use App\Models\Credential;
use App\Models\MessageTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client;

class WhatsappService
{
    protected $twilio;
    protected $whatsappFrom;
    protected $openAiKey;
    protected string $assistantId;
    protected $TWILIO_ACCOUNT_SID;
    protected $TWILIO_AUTH_TOKEN;

    public function __construct()
    {
        $credentials = Credential::first();
        $this->assistantId = $credentials->assistant_id;
        $this->twilio = new Client(
            $credentials->twilio_sid,
            $credentials->twilio_token
        );

        $this->whatsappFrom = $credentials->twilio_whats_app;
        $this->TWILIO_ACCOUNT_SID = $credentials->twilio_sid;
        $this->TWILIO_AUTH_TOKEN = $credentials->twilio_token;
        // load OpenAI key from config/services.php → .env
        $this->openAiKey = $credentials->open_ai_key;
    }

    // send first template message
    public function sendWhatsAppMessage($request)
    {
        $template = MessageTemplate::where('used_for', 'old_user')->first();
        $recipientNumber = 'whatsapp:' . $request->phone;
        $friendlyName = $request->phone;
        $message = 'Hello from fletton surveys';
        // $contentSid = 'HX8febaed305fb3d6f705269f53975e86c';
        $contentSid = $template->template_id;

        $twilio = $this->twilio;  // \Twilio\Rest\Client
        $proxyAddress = $this->whatsappFrom;  // e.g. 'whatsapp:+14155238886'

        try {
            // 0) Find or create Twilio Conversation for this WhatsApp number
            $existingSid = null;
            $pcs = $twilio
                ->conversations
                ->v1
                ->participantConversations
                ->read(['address' => $recipientNumber], 20);

            foreach ($pcs as $pc) {
                $binding = $pc->participantMessagingBinding ?? null;
                if ($binding && isset($binding['proxy_address']) && $binding['proxy_address'] === $proxyAddress) {
                    $existingSid = $pc->conversationSid;
                    break;
                }
            }

            if (!$existingSid) {
                $conversation = $twilio
                    ->conversations
                    ->v1
                    ->conversations
                    ->create(['friendlyName' => $friendlyName]);

                $existingSid = $conversation->sid;

                try {
                    $twilio
                        ->conversations
                        ->v1
                        ->conversations($existingSid)
                        ->participants
                        ->create([
                            'messagingBindingAddress' => $recipientNumber,
                            'messagingBindingProxyAddress' => $proxyAddress,
                        ]);
                } catch (\Twilio\Exceptions\RestException $e) {
                    if ($e->getStatusCode() != 409) {
                        throw $e;
                    }
                }
            }

            $firstName = trim($request->first_name);

            // Remove everything except letters and spaces
            $firstName = preg_replace('/[^a-zA-Z ]/', '', $firstName);

            // Convert to proper case (capitalize each word)
            $firstName = ucwords(strtolower($firstName));

            // 1) Send the (template) message
            $msg = $twilio
                ->conversations
                ->v1
                ->conversations($existingSid)
                ->messages
                ->create([
                    'author' => 'system',
                    'body' => $message,  // optional with contentSid
                    'contentSid' => $contentSid,
                    'contentVariables' => json_encode(['1' => (string) $firstName]),
                ]);

            // 2) Upsert the contact + profile in DB
            /** @var ChatControll $contact */
            $contact = ChatControll::updateOrCreate(
                ['sid' => $existingSid],
                [
                    'contact' => $friendlyName,
                    'auto_reply' => true,
                    'first_name' => ucwords(strtolower(preg_replace('/[^a-zA-Z ]/', '', $request->first_name))),
                    'last_name' => preg_replace('/[^a-zA-Z]/', '', $request->last_name),
                    'email' => $request->email,
                    'address' => $request->address,
                    'postal_code' => $request->postal_code,
                    'unread' => true,
                    'unread_message' => $msg->body,
                ]
            );

            ChatHistory::create([
                'conversation_sid' => $existingSid,
                'message_sid' => $msg->sid,
                'body' => $msg->body,
                'author' => 'system',
                'date_created' => Carbon::now()->toDateTimeString(),
            ]);

            event(new MessageSent($msg->body, $existingSid, 'system'));
            // 3) Create/seed the OpenAI thread using ONLY getOrCreateThreadId
            $this->getOrCreateThreadId($friendlyName, [
                'first_name' => ucwords(strtolower(preg_replace('/[^a-zA-Z ]/', '', $contact->first_name))),
                'last_name' => preg_replace('/[^a-zA-Z]/', '', $contact->last_name),
                'email' => $contact->email,
                'address' => $contact->address,
                'postal_code' => $contact->postal_code,
            ]);

            return response()->json([
                'message' => 'WhatsApp message sent successfully',
                'conversation_sid' => $existingSid,
                'message_sid' => $msg->sid,
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
            $msg = $this
                ->twilio
                ->conversations
                ->v1
                ->conversations($conversationSid)
                ->messages
                ->create([
                    'author' => 'system',
                    'body' => $message,
                ]);

            ChatHistory::create([
                'conversation_sid' => $conversationSid,
                'message_sid' => $msg->sid,
                'body' => $message,
                'author' => 'system',
                'date_created' => Carbon::now()->toDateTimeString(),
            ]);

            return response()->json([
                'message' => 'Sent via Conversation',
                'conversationSid' => $conversationSid,
                'messageSid' => $msg->sid,
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
            $convs = $this
                ->twilio
                ->conversations
                ->v1
                ->conversations
                ->stream();  // fetches ALL conversations, auto-paginates

            return array_map(fn($c) => [
                'sid' => $c->sid,
                'friendly_name' => $c->friendlyName,
                'state' => $c->state,
                'date_created' => $c->dateCreated->format('Y-m-d H:i:s'),
            ], iterator_to_array($convs));
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
            // $msgs = $this
            //     ->twilio
            //     ->conversations
            //     ->v1
            //     ->conversations($conversationSid)
            //     ->messages
            //     ->read();

            // return array_map(fn($m) => [
            //     'sid' => $m->sid,
            //     'author' => $m->author,
            //     'body' => $m->body,
            //     'date_created' => $m->dateCreated->format('Y-m-d H:i:s'),
            // ], $msgs);
            $chats = ChatControll::where('sid', $conversationSid)->first();
            $chats->update([
                'unread' => false,
                'unread_count' => 0,
            ]);

            $msgs = ChatHistory::where('conversation_sid', $conversationSid)
                ->orderBy('date_created', 'asc')
                ->get(['id', 'message_sid', 'author', 'body', 'date_created', 'is_starred', 'attachments', 'has_images']);

            return $msgs->map(function ($m) {
                return [
                    'id' => $m->id,
                    'is_starred' => $m->is_starred,
                    'sid' => $m->message_sid,  // matches Twilio 'sid'
                    'author' => $m->author,
                    'body' => $m->body,
                    'attachments' => $m->attachments,
                    'has_images' => $m->has_images,
                    'date_created' => $m->date_created
                        ? Carbon::parse($m->date_created)->format('Y-m-d H:i:s')
                        : null,
                ];
            })->toArray();
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
            $this
                ->twilio
                ->conversations
                ->v1
                ->conversations($conversationSid)
                ->delete();

            ChatControll::where('sid', $conversationSid)->delete();
            ChatHistory::where('conversation_sid', $conversationSid)->delete();
            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully',
                'conversation_sid' => $conversationSid,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle an incoming WhatsApp message → run Assistants API (v2) → reply with Assistant HTML
     */
    public function handleIncoming(Request $request)
    {
        Log::info('WhatsApp webhook payload:', $request->all());

        // Normalise WhatsApp number and find Twilio Conversation SID
        $userNumber = str_replace('whatsapp:', '', (string) $request->input('From', ''));

        $conversation = ChatControll::where('contact', $userNumber)->first();
        if ($conversation->is_blocked) {
            return response()->json(['message' => 'contact is blocked']);
        }
        $conversationSid = $conversation->sid;
        $conversation->update([
            'last_message' => Carbon::now(),
        ]);

        $mediaPaths = [];
        $numMedia = (int) $request->input('NumMedia', 0);

        $userText = trim((string) $request->input('Body', ''));
        if ($userText === '' && $numMedia <= 0) {
            return response()->noContent();
        }

        if ($numMedia > 0) {
            $payload = $request->all();  // ensure raw access to all Twilio fields
            $mediaPaths = [];

            for ($i = 0; $i < $numMedia; $i++) {
                $mediaUrl = $payload["MediaUrl{$i}"] ?? null;
                $mediaType = $payload["MediaContentType{$i}"] ?? null;

                if (!$mediaUrl || !$mediaType) {
                    Log::warning("Missing MediaUrl or MediaContentType for index {$i}");
                    continue;
                }

                // ✅ Download file from Twilio using stored credentials
                $response = Http::withBasicAuth(
                    $this->TWILIO_ACCOUNT_SID,
                    $this->TWILIO_AUTH_TOKEN
                )->get($mediaUrl);

                if (!$response->ok()) {
                    Log::error('Failed to download Twilio media', [
                        'url' => $mediaUrl,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    continue;
                }

                // Determine correct file extension
                $extension = match ($mediaType) {
                    'image/png' => 'png',
                    'image/jpeg', 'image/jpg' => 'jpg',
                    'image/gif' => 'gif',
                    'video/mp4' => 'mp4',
                    default => 'bin',
                };

                // Create unique, timestamp-based filename
                $filename = 'whatsapp_' . uniqid('', true) . "_{$i}." . $extension;

                $uploadDir = public_path('uploads');
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $storagePath = "{$uploadDir}/{$filename}";
                file_put_contents($storagePath, $response->body());

                $publicUrl = asset("uploads/{$filename}");

                $mediaPaths[] = [
                    'local_path' => $storagePath,
                    'image' => $publicUrl,
                    'mime_type' => $mediaType,
                ];

                Log::info('Media saved successfully', [
                    'file' => $filename,
                    'mime' => $mediaType,
                    'url' => $publicUrl,
                ]);
            }

            // ✅ Optionally save all media info in chat history
            if (!empty($mediaPaths)) {
                ChatHistory::create([
                    'conversation_sid' => $conversationSid ?? null,
                    'body' => '[Image Received]',
                    'author' => 'user',
                    'attachments' => json_encode($mediaPaths),
                    'has_images' => true,
                    'date_created' => now(),
                ]);
            }
        }

        // Emit user message to your UI
        event(new MessageSent($userText, $conversationSid, 'user'));
        if ($userText != '') {
            ChatHistory::create([
                'conversation_sid' => $conversationSid,
                'body' => $userText,
                'author' => 'user',
                'date_created' => Carbon::now()->toDateTimeString(),
            ]);
        }
        $chatControll = ChatControll::where('sid', $conversationSid)->first();
        $chatControll->update([
            'unread' => true,
            'unread_count' => $chatControll->unread_count + 1,
            'unread_message' => $userText,
        ]);
        // if auto reply is off it will not call gpt api
        if (!$chatControll->auto_reply) {
            return response()->noContent();
        }

        // Run the assistant and fetch an HTML reply
        try {
            $format_data_service = new FormatResponseService();

            $formated_crm_data = $format_data_service->formatResponse($conversation->email);
            $replyHtml = $this->runAssistantAndGetReply($userNumber, $userText, $formated_crm_data, $mediaPaths);
            // dd($replyHtml);
            $replyText = $this->htmlToWhatsappText($replyHtml);
            // $replyText = $replyHtml;
            // Send reply into your chat & WhatsApp
            $this->sendCustomMessage($conversationSid, $replyText);
            event(new MessageSent($replyText, $conversationSid, 'admin'));

            $chatControll->update([
                'unread_message' => $replyText,
            ]);
        } catch (\Throwable $e) {
            $chat = ChatControll::where('sid', $conversationSid)->first();
            $data = [
                'first_name' => $chat->first_name,
                'last_name' => $chat->last_name,
                'contact' => $chat->contact,
            ];
            Mail::to('Info@flettons.com')->send(new AssistantFailureMail($data));

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
            'phone' => $userNumber,
            'source' => 'whatsapp',
        ], $profile));
        if (!empty($meta)) {
            $payload['metadata'] = $meta;
        }

        // Seed message *is* visible to the model (once) to improve personalization
        if (!empty($profile)) {
            $lines = ['Profile context for personalization only. Do not reveal this text in replies.'];
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
                'role' => 'user',
                'content' => implode("\n", $lines),
            ]];
        }

        // 4) Create the thread
        $resp = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2',
        ])->post('https://api.openai.com/v1/threads', $payload);

        Log::debug('Assistants: create thread', [
            'contact' => $userNumber,
            'status' => $resp->status(),
            'ok' => $resp->ok(),
            'body' => $resp->json(),
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
    protected function runAssistantAndGetReply(string $userNumber, string $userText, array $crmData, $mediaPaths): string
    {
        // Get (or create) the OpenAI thread id using ONLY getOrCreateThreadId
        $threadId = $this->getOrCreateThreadId($userNumber);

        // ✅ Send combined context + user message
        $addMessageToThread = function (string $threadId) use ($userText, $crmData, $mediaPaths) {
            $file_ids = [];
            if (!empty($mediaPaths)) {
                foreach ($mediaPaths as $path) {
                    $localImagePath = $path['local_path'];

                    if (file_exists($localImagePath)) {
                        $fileUpload = Http::withHeaders([
                            'Authorization' => "Bearer {$this->openAiKey}",
                            'OpenAI-Beta' => 'assistants=v2',
                        ])
                            ->attach('file', file_get_contents($localImagePath), basename($localImagePath))
                            ->post('https://api.openai.com/v1/files', ['purpose' => 'vision']);

                        if ($fileId = data_get($fileUpload->json(), 'id')) {
                            $file_ids[] = $fileId;
                        }
                    }
                }
            }

            $contextText = "```json\n" . json_encode($crmData, JSON_PRETTY_PRINT) . "\n```";

            // ✅ Build content dynamically
            $contentBlocks = [];

            // If there is user text, always send it first
            if (!empty($userText)) {
                $contentBlocks[] = [
                    'type' => 'text',
                    'text' => "### CUSTOMER_CONTEXT\n{$contextText}\n\n### USER_MESSAGE\n{$userText}"
                ];
            }

            // If there are images, append them
            foreach ($file_ids as $fid) {
                $contentBlocks[] = [
                    'type' => 'image_file',
                    'image_file' => ['file_id' => $fid]
                ];
            }

            // If no text and only images → add fallback text
            if (empty($userText) && !empty($file_ids)) {
                array_unshift($contentBlocks, [
                    'type' => 'text',
                    'text' => 'User sent an image files please read the thread and relate these files and generate response accordingly'
                ]);
            }

            // If content still empty (shouldn’t happen), prevent API error
            if (empty($contentBlocks)) {
                $contentBlocks[] = [
                    'type' => 'text',
                    'text' => 'No valid message provided'
                ];
            }

            // ✅ Send properly formatted message
            return Http::withHeaders([
                'Authorization' => "Bearer {$this->openAiKey}",
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2',
            ])->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
                'role' => 'user',
                'content' => $contentBlocks,
            ]);
        };

        // Add the incoming user message; if the thread was purged, recreate using ONLY getOrCreateThreadId
        $addMsg = $addMessageToThread($threadId);
        if ($addMsg->status() === 404) {
            Log::warning('Assistants: thread 404, recreating', [
                'thread_id' => $threadId,
                'user_number' => $userNumber,
            ]);

            // Clear the stored thread id so getOrCreateThreadId will create a fresh one
            ChatControll::where('contact', $userNumber)
                ->update(['assistant_thread_id' => null]);

            $threadId = $this->getOrCreateThreadId($userNumber);
            $addMsg = $addMessageToThread($threadId);
        }
        if (!$addMsg->ok()) {
            throw new \RuntimeException('Failed to add message: ' . $addMsg->body());
        }

        // Create a run (keep your dynamic instructions if you have that helper)
        $runCreatePayload = [
            'assistant_id' => $this->assistantId,
        ];

        $run = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2',
        ])->post("https://api.openai.com/v1/threads/{$threadId}/runs", $runCreatePayload);

        Log::debug('Assistants: run created', [
            'thread_id' => $threadId,
            'status' => $run->status(),
            'ok' => $run->ok(),
            'body' => $run->json(),
        ]);

        if (!$run->ok()) {
            throw new \RuntimeException('Failed to create run: ' . $run->body());
        }

        $runId = (string) data_get($run->json(), 'id');

        // Poll until completion
        $maxWaitSeconds = 45;
        $sleepMs = 600;
        $elapsed = 0;

        while (true) {
            usleep($sleepMs * 1000);
            $elapsed += $sleepMs / 1000;

            $statusResp = Http::withHeaders([
                'Authorization' => "Bearer {$this->openAiKey}",
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2',
            ])->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");

            if (!$statusResp->ok()) {
                Log::error('Assistants: failed to check run', [
                    'thread_id' => $threadId,
                    'run_id' => $runId,
                    'status' => $statusResp->status(),
                    'body' => $statusResp->body(),
                ]);
                throw new \RuntimeException('Failed to check run: ' . $statusResp->body());
            }

            $statusJson = $statusResp->json();
            $status = (string) data_get($statusJson, 'status', 'queued');

            Log::debug('Assistants: run status tick', [
                'thread_id' => $threadId,
                'run_id' => $runId,
                'status' => $status,
                'elapsed_s' => $elapsed,
            ]);

            if ($status === 'completed')
                break;

            if ($status === 'requires_action') {
                $toolCalls = data_get($statusJson, 'required_action.submit_tool_outputs.tool_calls', []);
                Log::warning('Assistants: run requires tool action (not implemented)', [
                    'thread_id' => $threadId,
                    'run_id' => $runId,
                    'tool_calls' => $toolCalls,
                ]);
                throw new \RuntimeException('Run requires tool action but no tool outputs were provided.');
            }

            if (in_array($status, ['failed', 'cancelled', 'expired'], true)) {
                Log::error('Assistants: run terminal error', [
                    'thread_id' => $threadId,
                    'run_id' => $runId,
                    'status' => $status,
                    'last_error' => data_get($statusJson, 'last_error', null),
                    'full' => $statusJson,
                ]);
                $lastError = data_get($statusJson, 'last_error.message') ?? 'unknown error';
                throw new \RuntimeException("Run {$status}: {$lastError}");
            }

            if ($elapsed >= $maxWaitSeconds) {
                Log::error('Assistants: run timed out', [
                    'thread_id' => $threadId,
                    'run_id' => $runId,
                    'last_seen' => $status,
                ]);
                throw new \RuntimeException('Run timed out waiting for completion.');
            }

            if ($sleepMs < 1500)
                $sleepMs += 150;  // mild backoff
        }

        // Fetch latest assistant message
        $messagesResp = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2',
        ])->get("https://api.openai.com/v1/threads/{$threadId}/messages", [
            'limit' => 5,
            'order' => 'desc',
        ]);

        Log::debug('Assistants: messages fetch', [
            'thread_id' => $threadId,
            'status' => $messagesResp->status(),
            'ok' => $messagesResp->ok(),
            'body' => $messagesResp->json(),
        ]);

        if (!$messagesResp->ok()) {
            throw new \RuntimeException('Failed to list messages: ' . $messagesResp->body());
        }

        $items = (array) data_get($messagesResp->json(), 'data', []);
        foreach ($items as $msg) {
            if (($msg['role'] ?? '') !== 'assistant')
                continue;
            foreach (($msg['content'] ?? []) as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $val = (string) data_get($block, 'text.value', '');
                    if ($val !== '')
                        return $val;
                }
            }
        }

        return 'Thanks for your message — how can I help further?';
    }

    // format the message
    protected function htmlToWhatsappText(string $html): string
    {
        $normalized = preg_replace('/<\s*br\s*\/?>/i', "\n", $html);
        $normalized = preg_replace('/<\/\s*p\s*>/i', "\n\n", $normalized);

        $normalized = preg_replace_callback('~<a\b[^>]*>(.*?)</a>~is', function ($m) {
            $tag = $m[0];
            if (preg_match('~href\s*=\s*([\'"])(.*?)\1~i', $tag, $hrefMatch)) {
                return $hrefMatch[2];  // keep only the URL
            }
            return isset($m[1]) ? strip_tags($m[1]) : '';
        }, $normalized);

        $text = strip_tags($normalized);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\r\n?/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);

        return trim($text);
    }

    // sync chats between twilio and database
    public function syncChats()
    {
        dd('syncChats');
        try {
            $conversations = $this->getConversations();

            if (isset($conversations['error'])) {
                return response()->json(['error' => $conversations['error']], 500);
            }

            foreach ($conversations as $conv) {
                $msgs = $this
                    ->twilio
                    ->conversations
                    ->v1
                    ->conversations($conv['sid'])
                    ->messages
                    ->read();

                foreach ($msgs as $msg) {
                    ChatHistory::create([
                        'conversation_sid' => $conv['sid'],
                        'message_sid' => $msg->sid ?? null,
                        'body' => $msg->body ?? '',
                        'author' => $msg->author,
                        'date_created' => Carbon::parse($msg->dateCreated)->format('Y-m-d H:i:s'),
                    ]);
                }
            }

            return response()->json([
                'message' => 'Chats synchronized successfully',
                'count' => count($conversations),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //  public function getConversations(): array
    // {
    //     try {
    //         $convs = $this->twilio
    //             ->conversations
    //             ->v1
    //             ->conversations
    //             ->stream(); // all conversations

    //         $result = [];

    //         foreach ($convs as $c) {
    //             // fetch last message for this conversation
    //             $messages = $this->twilio
    //                 ->conversations
    //                 ->v1
    //                 ->conversations($c->sid)
    //                 ->messages
    //                 ->read([], 1); // only the latest one

    //             $lastMessageDate = null;
    //             if (!empty($messages)) {
    //                 $lastMessageDate = $messages[0]->dateCreated->format('Y-m-d H:i:s');
    //             }

    //             $result[] = [
    //                 'sid'            => $c->sid,
    //                 'friendly_name'  => $c->friendlyName,
    //                 'state'          => $c->state,
    //                 'date_created'   => $c->dateCreated->format('Y-m-d H:i:s'),
    //                 'last_message_at' => $lastMessageDate,
    //             ];
    //         }

    //         return $result;
    //     } catch (\Exception $e) {
    //         return ['error' => $e->getMessage()];
    //     }
    // }
}
