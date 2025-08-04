<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        $conversations = $this->getConversations();
        $conversationSid = null;
        foreach ($conversations as $conv) {
            if ($conv['friendly_name'] === $userNumber) {
                $conversationSid = $conv['sid'];
                break;
            }
        }

        $this->sendCustomMessage( $conversationSid,  trim($reply)  );

        return response()->noContent();
        // $this->twilio->messages->create($userNumber, [
        //     'from' => $this->whatsappFrom,
        //     'body' => trim($reply),
        // ]);
    }
}
