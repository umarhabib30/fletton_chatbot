<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        $watsAppService = new WhatsappService();
        $data = [
            'conversations' => $watsAppService->getConversations(),
        ];

        return view('chats.index', $data);
    }

    public function sendTemplateMessage(Request $request)
    {
        $watsAppService = new WhatsappService();
        $response = $watsAppService->sendWhatsAppMessage($request);
        return response()->json($response);
    }

    public function getMessages($conversationSid)
    {
        $watsAppService = new WhatsappService();
        $messages = $watsAppService->getMessages($conversationSid);

        return response()->json(['messages' => $messages]);
    }

    public function sendCustomMessage(Request $request)
    {
        $watsAppService = new WhatsappService();
        $watsAppService->sendCustomMessage($request->sid, $request->message);
        return response()->json(['message' => 'Message sent successfully']);
    }

    public function sendAutoReply(Request $request)
    {
        $watsAppService = new WhatsappService();
        $response = $watsAppService->handleIncoming($request);
        return response()->noContent();
    }

    public function broadCastMessage(Request $request)
    {
        try {
            // Get the message from the request
            $message = $request->input('message');
            $sid = $request->input('sid');
            // Broadcast the message using the MessageSent event
            event(new MessageSent($message, $sid));

            return response()->json(['status' => 'Message broadcasted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
