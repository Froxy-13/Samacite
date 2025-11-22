<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends BaseController
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(): JsonResponse
    {
        $notifications = auth('sanctum')->user()
            ->notifications()
            ->paginate(15);

        return $this->success(
            $notifications,
            'Notifications récupérées avec succès'
        );
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(): JsonResponse
    {
        $count = auth('sanctum')->user()
            ->unreadNotifications()
            ->count();

        return $this->success(
            ['unread_count' => $count],
            'Nombre de notifications non lues récupéré'
        );
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(string $id): JsonResponse
    {
        $notification = DatabaseNotification::findOrFail($id);

        if ($notification->notifiable_id !== auth('sanctum')->id()) {
            return $this->forbidden('Non autorisé');
        }

        $notification->markAsRead();

        return $this->success(
            $notification,
            'Notification marquée comme lue'
        );
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        auth('sanctum')->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return $this->success(
            null,
            'Toutes les notifications marquées comme lues'
        );
    }

    /**
     * Delete a notification
     */
    public function destroy(string $id): JsonResponse
    {
        $notification = DatabaseNotification::findOrFail($id);

        if ($notification->notifiable_id !== auth('sanctum')->id()) {
            return $this->forbidden('Non autorisé');
        }

        $notification->delete();

        return $this->success(
            null,
            'Notification supprimée avec succès'
        );
    }

    /**
     * Delete all notifications
     */
    public function deleteAll(): JsonResponse
    {
        auth('sanctum')->user()
            ->notifications()
            ->delete();

        return $this->success(
            null,
            'Toutes les notifications supprimées avec succès'
        );
    }
}
