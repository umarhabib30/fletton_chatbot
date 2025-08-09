<?php

namespace App\Services;

use App\Events\MessageSent;
use Twilio\Rest\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            // you could also throw or return [] depending on design
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
     * Handle an incoming WhatsApp message, pass to OpenAI, and reply
     */
    public function handleIncoming(Request $request)
    {
         Log::info('WhatsApp webhook payload:', $request->all());


        $userText = $request->input('Body');



        // 1) Get the OpenAI response
        $aiResponse = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model'    => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $userText],
            ],
        ]);

        $reply = $aiResponse->json('choices.0.message.content')
            ?? 'Sorry, something went wrong.';

        // 2) Send back over WhatsApp
        $userNumber = $request->input('From');
        $userNumber = str_replace('whatsapp:', '', $userNumber);
        $conversations = $this->getConversations();
        $conversationSid = null;
        foreach ($conversations as $conv) {
            if ($conv['friendly_name'] === $userNumber) {
                $conversationSid = $conv['sid'];
                break;
            }
        }

        event(new MessageSent($userText, $conversationSid));
        $this->sendCustomMessage( $conversationSid,  trim($reply)  );

        return response()->noContent();
    }
}
