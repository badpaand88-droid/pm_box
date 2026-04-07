<?php

namespace App\Models;

class ChangeLog
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /**
     * Log a change
     */
    public function log(array $data): int|false
    {
        $sql = "INSERT INTO change_logs (entity_type, entity_id, user_id, action, field_name, old_value, new_value) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            $data['entity_type'],
            $data['entity_id'],
            $data['user_id'],
            $data['action'],
            $data['field_name'] ?? null,
            $data['old_value'] !== null ? json_encode($data['old_value'], JSON_UNESCAPED_UNICODE) : null,
            $data['new_value'] !== null ? json_encode($data['new_value'], JSON_UNESCAPED_UNICODE) : null
        ]);

        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Get change log for entity
     */
    public function getForEntity(string $entityType, int $entityId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT cl.*, u.first_name, u.last_name, u.email
            FROM change_logs cl
            INNER JOIN users u ON cl.user_id = u.id
            WHERE cl.entity_type = ? AND cl.entity_id = ?
            ORDER BY cl.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$entityType, $entityId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get changes by user
     */
    public function getByUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT cl.*, u.first_name, u.last_name
            FROM change_logs cl
            INNER JOIN users u ON cl.user_id = u.id
            WHERE cl.user_id = ?
            ORDER BY cl.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Log task creation
     */
    public function logTaskCreation(int $taskId, int $userId, array $taskData): void
    {
        $this->log([
            'entity_type' => 'task',
            'entity_id' => $taskId,
            'user_id' => $userId,
            'action' => 'created',
            'field_name' => null,
            'old_value' => null,
            'new_value' => $taskData
        ]);
    }

    /**
     * Log task update
     */
    public function logTaskUpdate(int $taskId, int $userId, string $fieldName, mixed $oldValue, mixed $newValue): void
    {
        // Don't log if values are the same
        if ($oldValue === $newValue) {
            return;
        }

        $action = 'updated';
        
        // Special handling for status changes
        if ($fieldName === 'status') {
            $action = 'status_changed';
        } elseif ($fieldName === 'assignee_id') {
            $action = 'assigned';
        }

        $this->log([
            'entity_type' => 'task',
            'entity_id' => $taskId,
            'user_id' => $userId,
            'action' => $action,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue
        ]);
    }

    /**
     * Log task deletion
     */
    public function logTaskDeletion(int $taskId, int $userId, array $taskData): void
    {
        $this->log([
            'entity_type' => 'task',
            'entity_id' => $taskId,
            'user_id' => $userId,
            'action' => 'deleted',
            'field_name' => null,
            'old_value' => $taskData,
            'new_value' => null
        ]);
    }

    /**
     * Log project creation
     */
    public function logProjectCreation(int $projectId, int $userId, array $projectData): void
    {
        $this->log([
            'entity_type' => 'project',
            'entity_id' => $projectId,
            'user_id' => $userId,
            'action' => 'created',
            'field_name' => null,
            'old_value' => null,
            'new_value' => $projectData
        ]);
    }

    /**
     * Log project update
     */
    public function logProjectUpdate(int $projectId, int $userId, string $fieldName, mixed $oldValue, mixed $newValue): void
    {
        if ($oldValue === $newValue) {
            return;
        }

        $this->log([
            'entity_type' => 'project',
            'entity_id' => $projectId,
            'user_id' => $userId,
            'action' => 'updated',
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue
        ]);
    }

    /**
     * Log comment creation
     */
    public function logCommentCreation(int $commentId, int $userId, int $taskId): void
    {
        $this->log([
            'entity_type' => 'comment',
            'entity_id' => $commentId,
            'user_id' => $userId,
            'action' => 'created',
            'field_name' => 'task_id',
            'old_value' => null,
            'new_value' => $taskId
        ]);
    }

    /**
     * Get recent activity across all projects
     */
    public function getRecentActivity(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT cl.*, u.first_name, u.last_name, u.email
            FROM change_logs cl
            INNER JOIN users u ON cl.user_id = u.id
            ORDER BY cl.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get recent activity for user's projects
     */
    public function getUserActivity(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT cl.*, u.first_name, u.last_name, u.email
            FROM change_logs cl
            INNER JOIN users u ON cl.user_id = u.id
            WHERE cl.entity_type = 'task' AND cl.entity_id IN (
                SELECT t.id FROM tasks t
                INNER JOIN project_members pm ON t.project_id = pm.project_id
                WHERE pm.user_id = ?
            )
            ORDER BY cl.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
}
