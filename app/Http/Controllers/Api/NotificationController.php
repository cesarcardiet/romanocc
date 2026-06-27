<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\UserFcmToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Get user notifications
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'total_pages' => $notifications->lastPage(),
                'total_items' => $notifications->total(),
                'per_page' => $notifications->perPage(),
            ]
        ]);
    }

    /**
     * Update FCM token for user
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $fcmToken = $request->fcm_token;

        // If token is empty, deactivate all tokens for user
        if (empty($fcmToken)) {
            UserFcmToken::deactivateUserTokens($user->id);
            
            return response()->json([
                'success' => true,
                'message' => 'FCM tokens deactivated successfully'
            ]);
        }

        try {
            // Check if token already exists for this user
            $existingToken = UserFcmToken::where('user_id', $user->id)
                ->where('fcm_token', $fcmToken)
                ->first();

            if ($existingToken) {
                // Token exists, just activate it and update timestamp
                $existingToken->update([
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
                Log::debug($existingToken);
            } else {
                // Deactivate all previous tokens for this user
                UserFcmToken::deactivateUserTokens($user->id);

                // Create new active token
                $existingToken = UserFcmToken::create([
                    'user_id' => $user->id,
                    'fcm_token' => $fcmToken,
                    'device_type' => 'android',
                    'is_active' => true,
                ]);
                Log::debug($existingToken);
            }

            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id): JsonResponse
    {
        $user = auth()->user();
        
        $notification = $user->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = auth()->user();
        
        $user->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(): JsonResponse
    {
        $user = auth()->user();
        
        $count = $user->notifications()
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }
}
