<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!Schema::hasTable('notifications')) {
            return response()->json(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => (int) $request->query('per_page', 20), 'total' => 0]]);
        }
        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function unread(Request $request)
    {
        $user = Auth::user();
        if (!Schema::hasTable('notifications')) {
            return response()->json(['data' => []]);
        }
        $list = $user->unreadNotifications()->orderByDesc('created_at')->limit(50)->get();
        return response()->json(['data' => $list]);
    }

    public function markRead(string $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        if (!$notification->read_at) {
            $notification->markAsRead();
        }
        return response()->json(['message' => 'Marked as read']);
    }

    public function markAllRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All notifications marked as read']);
    }
}
