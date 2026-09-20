<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get recent notifications and unread count
     */
    public function index(): JsonResponse
    {
        $unreadCount = AppNotification::where('is_read', false)->count();
        
        $notifications = AppNotification::with(['letter:id,tracking_code,agenda_number,subject,sender_unit,sender_name,created_at'])
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return response()->json([
            'ok' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(int $id): JsonResponse
    {
        $notification = AppNotification::find($id);

        if ($notification) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        $unreadCount = AppNotification::where('is_read', false)->count();

        return response()->json([
            'ok' => true,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        AppNotification::where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'unread_count' => 0,
        ]);
    }
}
