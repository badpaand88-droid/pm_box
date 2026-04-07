<?php

class Notification extends BaseModel
{
    protected string $table = 'notifications';
    
    public function getByUser(int $userId, int $limit = 50): array
    {
        return $this->db->fetchAll("
            SELECT * FROM {$this->table}
            WHERE user_id = :userId
            ORDER BY is_read ASC, created_at DESC
            LIMIT :limit
        ", [
            'userId' => $userId,
            'limit' => $limit
        ]);
    }
    
    public function getUnreadCount(int $userId): int
    {
        $result = $this->db->fetch("
            SELECT COUNT(*) as count FROM {$this->table}
            WHERE user_id = :userId AND is_read = 0
        ", ['userId' => $userId]);
        
        return (int) ($result['count'] ?? 0);
    }
    
    public function createNotification(int $userId, string $type, string $title, string $message, ?string $link = null): int
    {
        return parent::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link
        ]);
    }
    
    public function markAsRead(int $notificationId): int
    {
        return $this->db->update($this->table, ['is_read' => 1], 'id = :id', ['id' => $notificationId]);
    }
    
    public function markAllAsRead(int $userId): int
    {
        return $this->db->update($this->table, ['is_read' => 1], 'user_id = :userId', ['userId' => $userId]);
    }
    
    public function notifyTaskAssigned(int $taskId, int $assignedUserId, int $assignedByUserId): void
    {
        $task = (new Task())->getWithDetails($taskId);
        $project = (new Project())->find($task['project_id']);
        
        $this->createNotification(
            $assignedUserId,
            'task_assigned',
            'New Task Assigned',
            "You have been assigned to task: {$task['title']} in project: {$project['name']}",
            "/tasks/$taskId"
        );
    }
    
    public function notifyTaskStatusChange(int $taskId, int $userId, string $newStatus): void
    {
        $task = (new Task())->getWithDetails($taskId);
        
        // Notify the task creator if status changed
        if ($task['created_by'] !== $userId) {
            $this->createNotification(
                $task['created_by'],
                'task_status_changed',
                'Task Status Updated',
                "Task '{$task['title']}' status changed to: $newStatus",
                "/tasks/$taskId"
            );
        }
    }
    
    /**
     * Notify about deadline tomorrow
     */
    public function notifyDeadlineTomorrow(): void
    {
        $taskModel = new Task();
        $dueTasks = $taskModel->getDueTomorrow();
        
        foreach ($dueTasks as $task) {
            $this->createNotification(
                (int)$task['assigned_to'],
                'deadline_tomorrow',
                'Deadline Tomorrow',
                "Task '{$task['title']}' is due tomorrow in project: {$task['project_name']}",
                "/tasks/{$task['id']}"
            );
        }
    }
    
    /**
     * Notify when a dependency is resolved (task unblocked)
     */
    public function notifyDependencyResolved(int $taskId, int $resolvedByTaskId): void
    {
        $task = (new Task())->getWithDetails($taskId);
        $resolvedTask = (new Task())->getWithDetails($resolvedByTaskId);
        
        // Notify assignee of the blocked task
        if ($task['assigned_to']) {
            $this->createNotification(
                (int)$task['assigned_to'],
                'dependency_resolved',
                'Task Unblocked',
                "Task '{$resolvedTask['title']}' is now complete. Task '{$task['title']}' can now be started.",
                "/tasks/$taskId"
            );
        }
    }
    
    /**
     * Check and notify for newly unblocked tasks
     */
    public function checkAndNotifyUnblockedTasks(int $completedTaskId): void
    {
        $depModel = new TaskDependency();
        $dependents = $depModel->getDependents($completedTaskId);
        
        foreach ($dependents as $dependent) {
            $canStart = $depModel->canStartTask((int)$dependent['id']);
            if ($canStart['can_start']) {
                $this->notifyDependencyResolved((int)$dependent['id'], $completedTaskId);
            }
        }
    }
}
