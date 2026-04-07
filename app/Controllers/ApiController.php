<?php

namespace App\Controllers;

class ApiController extends BaseController
{
    public function notifications(): void
    {
        $this->requireAuth();
        
        $notificationModel = new Notification();
        $user = Auth::user();
        
        $notifications = $notificationModel->getByUser($user['id'], 10);
        $unreadCount = $notificationModel->getUnreadCount($user['id']);
        
        $this->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }
    
    public function markNotificationRead(int $id): void
    {
        $this->requireAuth();
        
        $notificationModel = new Notification();
        $notification = $notificationModel->find($id);
        
        if (!$notification || $notification['user_id'] !== Auth::id()) {
            $this->json(['success' => false, 'message' => 'Notification not found'], 404);
        }
        
        $notificationModel->markAsRead($id);
        
        $this->json(['success' => true]);
    }
    
    public function markAllNotificationsRead(): void
    {
        $this->requireAuth();
        
        $notificationModel = new Notification();
        $notificationModel->markAllAsRead(Auth::id());
        
        $this->json(['success' => true]);
    }
    
    public function search(): void
    {
        $this->requireAuth();
        
        $query = trim($_GET['q'] ?? '');
        
        if (strlen($query) < 2) {
            $this->json(['tasks' => [], 'projects' => []]);
            return;
        }
        
        $taskModel = new Task();
        $projectModel = new Project();
        
        $tasks = $taskModel->search($query, 10);
        
        // Search projects
        $db = Database::getInstance();
        $projects = $db->fetchAll("
            SELECT * FROM projects 
            WHERE name LIKE :query OR description LIKE :query2
            LIMIT 10
        ", [
            'query' => "%$query%",
            'query2' => "%$query%"
        ]);
        
        $this->json([
            'tasks' => $tasks,
            'projects' => $projects
        ]);
    }
    
    public function taskStats(int $projectId): void
    {
        $this->requireAuth();
        
        $projectModel = new Project();
        $taskCounts = $projectModel->getTaskCounts($projectId);
        
        $this->json(['stats' => $taskCounts]);
    }
}
