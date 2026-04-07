<?php

class Task extends BaseModel
{
    protected string $table = 'tasks';
    
    public function getByProject(int $projectId, ?string $status = null): array
    {
        $sql = "SELECT t.*, u.full_name as assignee_name, creator.full_name as creator_name
                FROM {$this->table} t
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN users creator ON t.created_by = creator.id
                WHERE t.project_id = :projectId";
        
        $params = ['projectId' => $projectId];
        
        if ($status) {
            $sql .= " AND t.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY 
            CASE t.priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            t.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getWithDetails(int $taskId): ?array
    {
        return $this->db->fetch("
            SELECT t.*, 
                   u.full_name as assignee_name, 
                   u.email as assignee_email,
                   creator.full_name as creator_name,
                   p.name as project_name
            FROM {$this->table} t
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN users creator ON t.created_by = creator.id
            LEFT JOIN projects p ON t.project_id = p.id
            WHERE t.id = :taskId
        ", ['taskId' => $taskId]);
    }
    
    public function create(array $data): int
    {
        $data['status'] = $data['status'] ?? 'todo';
        $data['priority'] = $data['priority'] ?? 'medium';
        
        $id = parent::create($data);
        
        // Log history
        $this->logHistory($id, $data['created_by'], 'created', null, json_encode($data));
        
        return $id;
    }
    
    public function update(int $id, array $data, int $userId): int
    {
        // Get old values for history
        $oldTask = $this->find($id);
        
        $changedFields = [];
        foreach ($data as $key => $value) {
            if (isset($oldTask[$key]) && $oldTask[$key] !== $value) {
                $changedFields[$key] = ['old' => $oldTask[$key], 'new' => $value];
            }
        }
        
        $result = parent::update($id, $data);
        
        // Log history for changed fields
        foreach ($changedFields as $field => $values) {
            $this->logHistory($id, $userId, "updated_$field", $values['old'], $values['new']);
        }
        
        // Update completed_at if status changed to done
        if (isset($data['status']) && $data['status'] === 'done' && $oldTask['status'] !== 'done') {
            $this->db->update($this->table, ['completed_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
        }
        
        return $result;
    }
    
    public function logHistory(int $taskId, int $userId, string $action, $oldValue = null, $newValue = null): void
    {
        $this->db->insert('task_history', [
            'task_id' => $taskId,
            'user_id' => $userId,
            'action' => $action,
            'old_value' => $oldValue !== null ? (is_array($oldValue) ? json_encode($oldValue) : $oldValue) : null,
            'new_value' => $newValue !== null ? (is_array($newValue) ? json_encode($newValue) : $newValue) : null
        ]);
    }
    
    public function search(string $query, int $limit = 20): array
    {
        return $this->db->fetchAll("
            SELECT t.*, p.name as project_name, u.full_name as assignee_name
            FROM {$this->table} t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.title LIKE :query OR t.description LIKE :query2
            LIMIT :limit
        ", [
            'query' => "%$query%",
            'query2' => "%$query%",
            'limit' => $limit
        ]);
    }
    
    public function getOverdueTasks(): array
    {
        return $this->db->fetchAll("
            SELECT t.*, p.name as project_name, u.full_name as assignee_name
            FROM {$this->table} t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.due_date < CURDATE() 
              AND t.status NOT IN ('done', 'closed')
            ORDER BY t.due_date ASC
        ");
    }
}
