<?php

class Project extends BaseModel
{
    protected string $table = 'projects';
    
    public function getAllWithStats(): array
    {
        return $this->db->fetchAll("
            SELECT 
                p.*,
                u.full_name as creator_name,
                COUNT(DISTINCT t.id) as total_tasks,
                SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN t.status IN ('todo', 'in_progress', 'review') THEN 1 ELSE 0 END) as pending_tasks
            FROM {$this->table} p
            LEFT JOIN users u ON p.created_by = u.id
            LEFT JOIN tasks t ON p.id = t.project_id
            GROUP BY p.id
            ORDER BY p.updated_at DESC
        ");
    }
    
    public function getProjectsByUser(int $userId): array
    {
        return $this->db->fetchAll("
            SELECT DISTINCT p.*
            FROM {$this->table} p
            LEFT JOIN tasks t ON p.id = t.project_id
            WHERE p.created_by = :userId OR t.assigned_to = :userId2
            ORDER BY p.updated_at DESC
        ", ['userId' => $userId, 'userId2' => $userId]);
    }
    
    public function create(array $data): int
    {
        $data['status'] = $data['status'] ?? 'planning';
        return parent::create($data);
    }
    
    public function updateStatus(int $id, string $status): int
    {
        $allowedStatuses = ['planning', 'active', 'on_hold', 'completed', 'cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException("Invalid status: $status");
        }
        
        return $this->db->update($this->table, ['status' => $status], 'id = :id', ['id' => $id]);
    }
    
    public function getTaskCounts(int $projectId): array
    {
        $result = $this->db->fetch("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'review' THEN 1 ELSE 0 END) as review,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done
            FROM tasks
            WHERE project_id = :projectId
        ", ['projectId' => $projectId]);
        
        return $result ?: ['total' => 0, 'todo' => 0, 'in_progress' => 0, 'review' => 0, 'done' => 0];
    }
}
