<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatControll;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
   public function index()
{
    // Get stats
    $totalContacts   = ChatControll::count();
    $unreadChats     = ChatControll::where('unread', 1)->count();
    $blockedContacts = ChatControll::where('is_blocked', 1)->count();
    $pausedAutoReply = ChatControll::where('auto_reply', 0)->count();

    // Example weekly message count (replace with real logic if needed)
    $currentWeek = [12, 19, 3, 17, 6, 3, 9];  // Mon-Sun (dummy data)
    $previousWeek = [2, 29, 4, 5, 2, 3, 10];

    return view('admin.dashboard.index', [
        'heading' => 'Dashboard',
        'title'   => 'Dashboard',
        'active'  => 'dashboard',
        'totalContacts' => $totalContacts,
        'unreadChats'   => $unreadChats,
        'blockedContacts' => $blockedContacts,
        'pausedAutoReply' => $pausedAutoReply,
        'currentWeek' => $currentWeek,
        'previousWeek' => $previousWeek
    ]);
}

}
