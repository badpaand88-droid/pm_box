<?php

namespace App\Controllers;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        
        $user = Auth::user();
        $projectModel = new Project();
        $taskModel = new Task();
        $notificationModel = new Notification();
        
        // Get user's projects
        if (Auth::isAdmin() || Auth::isManager()) {
            $projects = $projectModel->getAllWithStats();
        } else {
            $projects = $projectModel->getProjectsByUser($user['id']);
        }
        
        // Get tasks assigned to user
        $myTasks = $taskModel->getByProject(null); // Will filter in view
        
        // Get overdue tasks
        $overdueTasks = $taskModel->getOverdueTasks();
        
        // Get unread notifications count
        $unreadNotifications = $notificationModel->getUnreadCount($user['id']);
        
        // Get team workload
        $teamWorkload = $taskModel->getTeamWorkload();
        
        // Statistics
        $stats = [
            'total_projects' => count($projects),
            'active_projects' => count(array_filter($projects, fn($p) => $p['status'] === 'active')),
            'my_tasks' => 0,
            'completed_tasks' => 0
        ];
        
        $this->view('dashboard/index', [
            'projects' => array_slice($projects, 0, 5),
            'overdue_tasks' => array_slice($overdueTasks, 0, 5),
            'stats' => $stats,
            'unread_notifications' => $unreadNotifications,
            'team_workload' => $teamWorkload
        ]);
    }
}
