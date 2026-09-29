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

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        if ($user) {
            $notification = $user->notifications()->find($id);
            if ($notification) {
                $notification->markAsRead();
            }
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * Get latest notifications & unread count for dynamic polling.
     */
    public function getLatest(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['unreadCount' => 0, 'notifications' => []]);
        }

        $rawNotifications = $user->notifications()
            ->where(function ($q) {
                $q->whereNull('data->title')
                  ->orWhere(function ($sub) {
                      $sub->where('data->title', 'not like', '%Profil%')
                          ->where('data->title', 'not like', '%Profile%');
                  });
            })
            ->limit(20)
            ->get();

        $unreadCount = $user->unreadNotifications()
            ->where(function ($q) {
                $q->whereNull('data->title')
                  ->orWhere(function ($sub) {
                      $sub->where('data->title', 'not like', '%Profil%')
                          ->where('data->title', 'not like', '%Profile%');
                  });
            })
            ->count();
        $notifications = $rawNotifications->map(function ($notif) {
            return [
                'id'      => $notif->id,
                'title'   => $notif->data['title'] ?? 'Notifikasi Baru',
                'message' => $notif->data['message'] ?? '',
                'time'    => $notif->created_at->diffForHumans(),
                'type'    => $notif->data['type'] ?? 'info',
                'read'    => $notif->read_at !== null,
                'link'    => $notif->data['link'] ?? $notif->data['url'] ?? '#',
                'icon'    => $notif->data['icon'] ?? '🔔',
            ];
        });

        return response()->json([
            'unreadCount'   => $unreadCount,
            'notifications' => $notifications,
        ]);
    }
}
