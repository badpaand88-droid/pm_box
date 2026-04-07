<?php

namespace App\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class DashboardController
{
    private Project $projectModel;
    private Task $taskModel;
    private User $userModel;

    public function __construct()
    {
        Auth::requireLogin();
        
        $this->projectModel = new Project();
        $this->taskModel = new Task();
        $this->userModel = new User();
    }

    /**
     * Show dashboard
     */
    public function index(): void
    {
        $userId = Auth::id();
        $user = Auth::user();

        // Get user's projects
        $projects = $this->projectModel->getAll([
            'user_id' => $userId,
            'limit' => 10
        ]);

        // Get tasks assigned to user
        $myTasks = $this->taskModel->getRecentForUser($userId, 10);

        // Get overdue tasks
        $overdueTasks = $this->taskModel->getAll([
            'assignee_id' => $userId,
            'overdue' => true,
            'status' => ['todo', 'in_progress', 'review']
        ]);

        // Get statistics
        $stats = [
            'total_projects' => $this->projectModel->count(['user_id' => $userId]),
            'active_projects' => $this->projectModel->count(['user_id' => $userId, 'status' => 'active']),
            'my_tasks' => $this->taskModel->count(['assignee_id' => $userId]),
            'overdue_tasks' => count($overdueTasks)
        ];

        // Get recent activity
        $recentActivity = [];
        if ($user['role'] === 'admin') {
            $changeLogModel = new \App\Models\ChangeLog();
            $recentActivity = $changeLogModel->getRecentActivity(15);
        } else {
            $changeLogModel = new \App\Models\ChangeLog();
            $recentActivity = $changeLogModel->getUserActivity($userId, 15);
        }

        require APP_PATH . '/views/dashboard/index.php';
    }

    /**
     * Get dashboard data via AJAX
     */
    public function getData(): void
    {
        $userId = Auth::id();
        $user = Auth::user();

        $data = [
            'stats' => [
                'total_projects' => $this->projectModel->count(['user_id' => $userId]),
                'active_projects' => $this->projectModel->count(['user_id' => $userId, 'status' => 'active']),
                'my_tasks' => $this->taskModel->count(['assignee_id' => $userId]),
                'overdue_tasks' => $this->taskModel->count([
                    'assignee_id' => $userId,
                    'status' => ['todo', 'in_progress', 'review']
                ])
            ],
            'projects' => array_slice($this->projectModel->getAll(['user_id' => $userId, 'limit' => 5]), 0, 5),
            'tasks' => $this->taskModel->getRecentForUser($userId, 5)
        ];

        json_success($data);
    }
}
