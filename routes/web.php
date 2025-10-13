<?php

use App\Http\Controllers\Admin\CredentialController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MessageTemplateController;
use App\Http\Controllers\Admin\SendTemplateController;
use App\Http\Controllers\Admin\UserController;
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
    Route::get('chat/block/{id}',[ChatController::class, 'block']);
    Route::get('contact/details/{sid}', [ChatController::class, 'getContactDetails'])->name('contact.details');

    Route::get('message/star/{id}',[ChatController::class, 'star'])->name('message.star');

    Route::get('chats/sync', [ChatController::class, 'syncChats'])->name('chats.sync');

    // --------- Admin Routes -----------
    Route::get('admin/dashboard',[DashboardController::class,'index'])->name('admin.dashboard');

    Route::get('admin/credentials', [CredentialController::class,'index'])->name('admin.credentials');
    Route::post('admin/credentials/update', [CredentialController::class, 'update'])->name('admin.credentials.update');

    Route::get('admin/templates/index', [MessageTemplateController::class, 'index']);
    Route::get('admin/template/create', [MessageTemplateController::class, 'create']);
    Route::post('admin/template/store', [MessageTemplateController::class, 'store']);
    Route::get('admin/template/edit/{id}', [MessageTemplateController::class, 'edit']);
    Route::post('admin/template/update', [MessageTemplateController::class, 'update']);
    Route::get('admin/template/delete/{id}', [MessageTemplateController::class, 'delete']);

    Route::get('admin/users/index', [UserController::class, 'index']);
    Route::get('admin/user/create', [UserController::class, 'create']);
    Route::post('admin/user/store', [UserController::class, 'store']);
    Route::get('admin/user/edit/{id}', [UserController::class, 'edit']);
    Route::post('admin/user/update', [UserController::class, 'update']);
    Route::get('admin/user/delete/{id}', [UserController::class, 'delete']);

    Route::get('admin/send-template', [SendTemplateController::class, 'index']);
    Route::post('admin/send-teplate/store', [SendTemplateController::class, 'sendTemplate']);

});



