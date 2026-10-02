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
     * Helper to clean text from emojis / sticker symbols.
     */
    public static function cleanNotificationText(?string $string): string
    {
        if (empty($string)) {
            return '';
        }
        $clean = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{FE00}-\x{FE0F}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{200D}\x{2300}-\x{23FF}\x{2B50}\x{2B55}\x{20E3}\x{2934}\x{2935}\x{2190}-\x{21FF}\x{2705}\x{274C}\x{2728}\x{2714}\x{2716}]/u', '', $string);
        $clean = str_replace(['✓', '✔', '✅', '❌', '✖', '🎯', '📋', '🔔', '🎓', '🎉', '📈', '✨', '🔥', '🚨', '⚠️', '✏️', '📅'], '', $clean);
        return trim(preg_replace('/\s+/', ' ', $clean));
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

        $rawNotifications = $user->notifications()->limit(20)->get();
        $unreadCount = $user->unreadNotifications()->count();
        $notifications = $rawNotifications->map(function ($notif) {
            return [
                'id'      => $notif->id,
                'title'   => self::cleanNotificationText($notif->data['title'] ?? 'Notifikasi Baru'),
                'message' => self::cleanNotificationText($notif->data['message'] ?? ''),
                'time'    => $notif->created_at->diffForHumans(),
                'type'    => $notif->data['type'] ?? 'info',
                'read'    => $notif->read_at !== null,
                'link'    => $notif->data['link'] ?? $notif->data['url'] ?? '#',
                'icon'    => '',
            ];
        });

        return response()->json([
            'unreadCount'   => $unreadCount,
            'notifications' => $notifications,
        ]);
    }
}
