<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatControll;
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


    // ** first message to customer ** //
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
        $auto_reply = (int) ChatControll::where('sid', $conversationSid)->value('auto_reply') ?? 0;
        return response()->json(['messages' => $messages, 'auto_reply' => $auto_reply]);
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

    public function stopAutoReply($sid)
    {
        try {
            $chat = ChatControll::where('sid', $sid)->first();
            $chat->update([
                'auto_reply' => false,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Chat auto replies are paused successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'error' . $e->getMessage(),
            ]);
        }
    }

    public function resumeAutoReply($sid)
    {
        try {
            $chat = ChatControll::where('sid', $sid)->first();
            $chat->update([
                'auto_reply' => true,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Chat auto replies are resumed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'error' . $e->getMessage(),
            ]);
        }
    }
}
