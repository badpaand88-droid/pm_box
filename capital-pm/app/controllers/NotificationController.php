<?php

namespace App\Controllers;

use App\Models\Notification;

class NotificationController
{
    private Notification $notificationModel;

    public function __construct()
    {
        Auth::requireLogin();
        $this->notificationModel = new Notification();
    }

    /**
     * Get notifications for current user (AJAX polling)
     */
    public function getNotifications(): void
    {
        $userId = Auth::id();
        
        $notifications = $this->notificationModel->getAllForUser($userId, [
            'limit' => 20
        ]);

        $unreadCount = $this->notificationModel->getUnreadCount($userId);

        json_success([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('Invalid request', 400);
        }

        require_csrf();

        $userId = Auth::id();
        $notification = $this->notificationModel->findById($id);

        if (!$notification || $notification['user_id'] !== $userId) {
            json_error('Notification not found', 404);
        }

        if ($this->notificationModel->markAsRead($id)) {
            json_success(['message' => 'Notification marked as read']);
        } else {
            json_error('Failed to mark notification as read');
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('Invalid request', 400);
        }

        require_csrf();

        $userId = Auth::id();

        if ($this->notificationModel->markAllAsRead($userId)) {
            json_success(['message' => 'All notifications marked as read']);
        } else {
            json_error('Failed to mark notifications as read');
        }
    }

    /**
     * Delete notification
     */
    public function delete(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('Invalid request', 400);
        }

        require_csrf();

        $userId = Auth::id();
        $notification = $this->notificationModel->findById($id);

        if (!$notification || $notification['user_id'] !== $userId) {
            json_error('Notification not found', 404);
        }

        if ($this->notificationModel->delete($id)) {
            json_success(['message' => 'Notification deleted']);
        } else {
            json_error('Failed to delete notification');
        }
    }
}
