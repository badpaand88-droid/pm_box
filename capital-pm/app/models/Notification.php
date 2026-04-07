<?php

namespace App\Models;

class Notification
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /**
     * Find notification by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all notifications for user
     */
    public function getAllForUser(int $userId, array $filters = []): array
    {
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        $params = [$userId];

        if (isset($filters['is_read'])) {
            $sql .= " AND is_read = ?";
            $params[] = $filters['is_read'] ? 1 : 0;
        }

        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }

        $sql .= " ORDER BY created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int) $filters['limit'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Create notification
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO notifications (user_id, type, title, message, related_entity_type, related_entity_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            $data['user_id'],
            $data['type'],
            $data['title'],
            $data['message'],
            $data['related_entity_type'] ?? null,
            $data['related_entity_id'] ?? null
        ]);

        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0"
        );
        return $stmt->execute([$userId]);
    }

    /**
     * Delete notification
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Delete old notifications
     */
    public function deleteOld(int $userId, int $days = 30): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM notifications WHERE user_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        return $stmt->execute([$userId, $days]);
    }

    /**
     * Create task assigned notification
     */
    public function notifyTaskAssigned(int $taskId, int $assigneeId, int $assignedBy): int|false
    {
        $taskModel = new Task();
        $task = $taskModel->findById($taskId);
        
        if (!$task) {
            return false;
        }

        $projectModel = new Project();
        $project = $projectModel->findById($task['project_id']);

        return $this->create([
            'user_id' => $assigneeId,
            'type' => 'task_assigned',
            'title' => 'Task Assigned',
            'message' => sprintf(
                'You have been assigned to task "%s" in project "%s"',
                $task['title'],
                $project['name'] ?? 'Unknown'
            ),
            'related_entity_type' => 'task',
            'related_entity_id' => $taskId
        ]);
    }

    /**
     * Create comment added notification
     */
    public function notifyCommentAdded(int $taskId, int $recipientId, int $commenterId): int|false
    {
        $taskModel = new Task();
        $task = $taskModel->findById($taskId);
        
        if (!$task) {
            return false;
        }

        $projectModel = new Project();
        $project = $projectModel->findById($task['project_id']);

        // Don't notify if commenting on own task
        if ($task['assignee_id'] === $commenterId) {
            return false;
        }

        return $this->create([
            'user_id' => $recipientId,
            'type' => 'comment_added',
            'title' => 'New Comment',
            'message' => sprintf(
                'New comment on task "%s" in project "%s"',
                $task['title'],
                $project['name'] ?? 'Unknown'
            ),
            'related_entity_type' => 'task',
            'related_entity_id' => $taskId
        ]);
    }

    /**
     * Create deadline reminder notification
     */
    public function notifyDeadlineApproaching(int $taskId, int $assigneeId): int|false
    {
        $taskModel = new Task();
        $task = $taskModel->findById($taskId);
        
        if (!$task) {
            return false;
        }

        $projectModel = new Project();
        $project = $projectModel->findById($task['project_id']);

        return $this->create([
            'user_id' => $assigneeId,
            'type' => 'deadline_reminder',
            'title' => 'Deadline Approaching',
            'message' => sprintf(
                'Task "%s" in project "%s" is due %s',
                $task['title'],
                $project['name'] ?? 'Unknown',
                format_date($task['due_date'])
            ),
            'related_entity_type' => 'task',
            'related_entity_id' => $taskId
        ]);
    }

    /**
     * Send notifications to project members
     */
    public function notifyProjectMembers(int $projectId, string $type, string $title, string $message, 
                                        ?string $entityType = null, ?int $entityId = null, 
                                        ?int $excludeUserId = null): void
    {
        $projectModel = new Project();
        $members = $projectModel->getMembers($projectId);

        foreach ($members as $member) {
            if ($excludeUserId && $member['id'] === $excludeUserId) {
                continue;
            }

            $this->create([
                'user_id' => $member['id'],
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'related_entity_type' => $entityType,
                'related_entity_id' => $entityId
            ]);
        }
    }
}
