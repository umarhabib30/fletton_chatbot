<?php

namespace App\Http\Controllers\Admin;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatControll;
use App\Models\ChatHistory;
use App\Models\Credential;
use App\Models\MessageTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SendTemplateController extends Controller
{
    protected $twilio;
    protected $whatsappFrom;
    protected $openAiKey;
    protected string $assistantId;

    public function __construct()
    {
        $credentials = Credential::first();
        $this->assistantId = $credentials->assistant_id;
        $this->twilio = new Client(
            $credentials->twilio_sid,
            $credentials->twilio_token
        );

        $this->whatsappFrom = $credentials->twilio_whats_app;

        // load OpenAI key from config/services.php → .env
        $this->openAiKey = $credentials->open_ai_key;
    }

    public function index()
    {
        $data = [
            'title' => 'Send Message',
            'active' => 'messages',
            'templates' => MessageTemplate::all(),
            'users' => ChatControll::where('is_blocked', false)->get(),
        ];
        return view('admin.send-message.index', $data);
    }

    // send first template message
    public function sendTemplate(Request $request)
    {
        $cleanPhone = preg_replace('/[^0-9+]/', '', $request->phone);
        $recipientNumber = 'whatsapp:' . $cleanPhone;
        $credentials = Credential::first();
        $friendlyName = $cleanPhone;
        $message = 'Hello from fletton surveys';
        // $contentSid = 'HX8febaed305fb3d6f705269f53975e86c';
        $contentSid = $request->template_id;

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

            $payload = [
                'given_name' => $request->first_name,
                'family_name' => $request->last_name,
                'lead_source_id' => 329,
                'contact_type' => 'Prospective',
                'duplicate_option' => 'Email',
                // ✅ Add this line
                'opt_in_reason' => 'Explicit consent provided during website registration',
                // Billing address
                'addresses' => [
                    [
                        'line1' => $request->address,
                        'locality' => '',
                        'postal_code' => $request->postal_code ?? '',
                        'country_code' => '',
                        'field' => 'BILLING'
                    ]
                ],
                // Phone numbers
                'phone_numbers' => [
                    [
                        'number' => str_replace(' ', '', $cleanPhone),
                        'field' => 'PHONE1'
                    ]
                ],
                // Email addresses
                'email_addresses' => [
                    [
                        'email' => $request->email,
                        'field' => 'EMAIL1'
                    ]
                ],
            ];

            // ✅ Send to Keap
            $response = Http::withHeaders([
                'X-Keap-API-Key' => $credentials->keap_api_key,
                'Authorization' => 'Bearer '. $credentials->keap_api_key,
                'Content-Type' => 'application/json',
            ])->put('https://api.infusionsoft.com/crm/rest/v1/contacts', $payload);
            $contactData = $response->json();

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

            return redirect()->back()->with('success', 'WhatsApp message sent successfully');
        } catch (\Twilio\Exceptions\RestException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

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
}
