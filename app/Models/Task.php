<?php

class Task extends BaseModel
{
    protected string $table = 'tasks';
    
    public function getByProject(int $projectId, ?string $status = null): array
    {
        $sql = "SELECT t.*, u.full_name as assignee_name, creator.full_name as creator_name,
                       c.name as category_name, c.color as category_color
                FROM {$this->table} t
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN users creator ON t.created_by = creator.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.project_id = :projectId";
        
        $params = ['projectId' => $projectId];
        
        if ($status) {
            $sql .= " AND t.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY 
            t.parent_task_id IS NOT NULL,
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
                   p.name as project_name,
                   c.name as category_name,
                   c.color as category_color
            FROM {$this->table} t
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN users creator ON t.created_by = creator.id
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.id = :taskId
        ", ['taskId' => $taskId]);
    }
    
    /**
     * Get subtasks for a task
     */
    public function getSubtasks(int $taskId): array
    {
        return $this->db->fetchAll("
            SELECT * FROM {$this->table}
            WHERE parent_task_id = :parentId
            ORDER BY created_at ASC
        ", ['parentId' => $taskId]);
    }
    
    /**
     * Calculate progress based on subtasks
     */
    public function calculateProgress(int $taskId): float
    {
        $subtasks = $this->getSubtasks($taskId);
        
        if (empty($subtasks)) {
            // No subtasks - return 100 if done, 0 otherwise
            $task = $this->find($taskId);
            return ($task && $task['status'] === 'done') ? 100.0 : 0.0;
        }
        
        $doneCount = 0;
        foreach ($subtasks as $subtask) {
            if ($subtask['status'] === 'done') {
                $doneCount++;
            }
        }
        
        return round(($doneCount / count($subtasks)) * 100, 2);
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
    
    /**
     * Get tasks due tomorrow (for notifications)
     */
    public function getDueTomorrow(): array
    {
        return $this->db->fetchAll("
            SELECT t.*, p.name as project_name, u.full_name as assignee_name, u.email as assignee_email
            FROM {$this->table} t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
              AND t.status NOT IN ('done', 'closed')
              AND t.assigned_to IS NOT NULL
        ");
    }
    
    /**
     * Get team workload (active tasks per user)
     */
    public function getTeamWorkload(?int $projectId = null): array
    {
        $where = $projectId ? "AND t.project_id = :projectId" : "";
        $params = $projectId ? ['projectId' => $projectId] : [];
        
        return $this->db->fetchAll("
            SELECT u.id, u.full_name, u.avatar,
                   COUNT(t.id) as active_tasks,
                   SUM(CASE WHEN t.priority = 'critical' THEN 4 
                            WHEN t.priority = 'high' THEN 3 
                            WHEN t.priority = 'medium' THEN 2 
                            ELSE 1 END) as weighted_load
            FROM users u
            LEFT JOIN tasks t ON u.id = t.assigned_to 
                AND t.status IN ('todo', 'in_progress', 'review')
                $where
            WHERE u.is_active = 1
            GROUP BY u.id
            ORDER BY active_tasks DESC
        ", $params);
    }
    
    /**
     * Get tasks filtered by multiple criteria (for export)
     */
    public function getFiltered(array $filters): array
    {
        $sql = "SELECT t.*, 
                       p.name as project_name,
                       c.name as category_name,
                       u.full_name as assignee_name,
                       creator.full_name as creator_name
                FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN users creator ON t.created_by = creator.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['project_id'])) {
            $sql .= " AND t.project_id = :project_id";
            $params['project_id'] = $filters['project_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to = :assigned_to";
            $params['assigned_to'] = $filters['assigned_to'];
        }
        
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = :priority";
            $params['priority'] = $filters['priority'];
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
}
