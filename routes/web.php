<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('chats', [ChatController::class, 'index']);
Route::get('chat/messages/{conversationSid}', [ChatController::class, 'getMessages']);
Route::post('send-message', [ChatController::class, 'sendCustomMessage']);

Route::get('/send-whatsapp', [WhatsAppController::class, 'sendWhatsAppMessage']);
Route::get('/whatsapp/conversations', [WhatsAppController::class, 'getConversations']);
Route::get('/whatsapp/conversation/messages/{conversationSid}', [WhatsAppController::class, 'getMessages']);
Route::get('conversation/delete/{conversationSid}', [WhatsAppController::class, 'deleteConversation']);
