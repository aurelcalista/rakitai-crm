<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }
        
        return response()->json(['success' => true]);
    }
}
