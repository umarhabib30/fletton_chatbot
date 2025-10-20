<?php

namespace App\Http\Controllers;

use App\Models\ChatControll;
use App\Models\ChatHistory;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Services\FcmAccessToken;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FcmNotificationController extends Controller
{
    public function saveToken(Request $request)
    {
        UserDeviceToken::firstOrCreate([
            'user_id' => Auth::user()->id,
            'token' => $request->token,
        ]);

        return response()->json(['message' => 'Token saved successfully.']);
    }

    public function sendNotification(Request $request)
    {
        // dd($request);
        $request->validate([
            'body' => 'required|string|max:500',
        ]);

        $chatcontrol = ChatControll::where('sid', $request->sid)->first();
        $title = $chatcontrol->first_name . ' ' . $chatcontrol->last_name;

        $tokens = UserDeviceToken::pluck('token')->all();
        if (empty($tokens)) {
            return back()->with('status', 'No device tokens found. Click "Allow for Notification" first.');
        }

        $projectId = 'flettonchatbot';  // <- your Firebase project ID
        $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $accessToken = FcmAccessToken::make(public_path('firebase-service-account.json'));
        if (!$accessToken) {
            return back()->with('status', 'Could not get OAuth token. Check service account JSON path & permissions.');
        }

        // Send one-by-one (simpler) or build a batch. Here: one-by-one.
        $client = new Client(['timeout' => 15]);

        $sent = 0;
        $failed = 0;
        foreach ($tokens as $t) {
            $payload = [
                'message' => [
                    'token' => $t,
                    // 1) Put fields in DATA so onMessage fires in foreground
                    'data' => [
                        'title' => $title,
                        'body' => $request->body,
                        'icon' => url('/favicon.ico'),
                    ],
                    // 2) Also provide a webpush notification for background delivery
                    'webpush' => [
                        'notification' => [
                            'title' => $title,
                            'body' => $request->body,
                            'icon' => url('/favicon.ico'),
                        ],
                        'fcm_options' => ['link' => url('/')],
                        'headers' => ['Urgency' => 'high']
                    ],
                ],
                'validate_only' => false,
            ];

            try {
                $res = $client->post($endpoint, [
                    'headers' => [
                        'Authorization' => "Bearer {$accessToken}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                ]);
                $code = $res->getStatusCode();
                $body = (string) $res->getBody();
                Log::info('FCM v1 response', ['code' => $code, 'body' => $body]);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('FCM v1 send error', ['error' => $e->getMessage()]);
            }
        }

        return back()->with('status', "Notification request sent. Success: {$sent}, Failed: {$failed}");
    }
}
