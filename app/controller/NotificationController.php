<?php
require_once __DIR__ . "/../service/NotificationService.php";

class NotificationController
{
    private NotificationService $service;

    public function __construct()
    {
        $this->service = new NotificationService();
    }

    public function getUserNotifications($userId)
    {
        header("Content-Type: application/json");
        $result = $this->service->getUserNotifications((int) $userId);
        echo json_encode($result);
    }

    public function markAsRead($userId, $notificationId)
    {
        header("Content-Type: application/json");
        $success = $this->service->markAsRead((int) $userId, (int) $notificationId);
        echo json_encode([
            "success" => $success,
            "message" => $success ? "Notification marked as read" : "Failed to update notification"
        ]);
    }

    public function markAllAsRead($userId)
    {
        header("Content-Type: application/json");
        $success = $this->service->markAllAsRead((int) $userId);
        echo json_encode([
            "success" => $success,
            "message" => $success ? "All notifications marked as read" : "Failed to update notifications"
        ]);
    }
}
