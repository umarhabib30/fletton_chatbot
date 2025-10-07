<?php

use App\Http\Controllers\AuthController;
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

Route::get('login', [AuthController::class, 'index'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('admin.login');
Route::get('logout', [AuthController::class, 'logout'])->name('admin.logout');

Route::group(['middleware' => ['auth']], function () {
    Route::get('/', [ChatController::class, 'index']);
    Route::get('chat/messages/{conversationSid}', [ChatController::class, 'getMessages']);
    Route::post('send-message', [ChatController::class, 'sendCustomMessage']);
    Route::get('autoreply/stop/{sid}', [ChatController::class, 'stopAutoReply']);
    Route::get('autoreply/resume/{sid}' , [ChatController::class, 'resumeAutoReply']);
    Route::get('chat/delete/{id}', [ChatController::class, 'deleteConversation']);
    Route::get('contact/details/{sid}', [ChatController::class, 'getContactDetails'])->name('contact.details');

    Route::get('chats/sync', [ChatController::class, 'syncChats'])->name('chats.sync');
});



