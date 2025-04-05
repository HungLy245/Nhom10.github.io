<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->update(['read_at' => now()]);
        return response()->json($notification);
    }
} 