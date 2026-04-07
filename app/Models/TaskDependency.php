<?php

class TaskDependency extends BaseModel
{
    protected string $table = 'task_dependencies';
    
    /**
     * Get all dependencies for a task (tasks this task depends on)
     */
    public function getDependencies(int $taskId): array
    {
        return $this->db->fetchAll("
            SELECT d.*, t.title, t.status, t.priority
            FROM {$this->table} d
            JOIN tasks t ON d.depends_on_task_id = t.id
            WHERE d.task_id = :taskId
            ORDER BY t.created_at DESC
        ", ['taskId' => $taskId]);
    }
    
    /**
     * Get all dependents (tasks that depend on this task)
     */
    public function getDependents(int $taskId): array
    {
        return $this->db->fetchAll("
            SELECT d.*, t.title, t.status, t.priority
            FROM {$this->table} d
            JOIN tasks t ON d.task_id = t.id
            WHERE d.depends_on_task_id = :taskId
            ORDER BY t.created_at DESC
        ", ['taskId' => $taskId]);
    }
    
    /**
     * Check if a dependency exists
     */
    public function exists(int $taskId, int $dependsOnTaskId): bool
    {
        $result = $this->db->fetch("
            SELECT COUNT(*) as count FROM {$this->table}
            WHERE task_id = :taskId AND depends_on_task_id = :dependsOnTaskId
        ", [
            'taskId' => $taskId,
            'dependsOnTaskId' => $dependsOnTaskId
        ]);
        
        return (int) ($result['count'] ?? 0) > 0;
    }
    
    /**
     * Add a dependency
     */
    public function add(int $taskId, int $dependsOnTaskId): int
    {
        if ($this->exists($taskId, $dependsOnTaskId)) {
            return 0;
        }
        
        return parent::create([
            'task_id' => $taskId,
            'depends_on_task_id' => $dependsOnTaskId
        ]);
    }
    
    /**
     * Remove a dependency
     */
    public function remove(int $taskId, int $dependsOnTaskId): int
    {
        return $this->db->delete(
            $this->table,
            'task_id = :taskId AND depends_on_task_id = :dependsOnTaskId',
            [
                'taskId' => $taskId,
                'dependsOnTaskId' => $dependsOnTaskId
            ]
        );
    }
    
    /**
     * Check if task can be started (all dependencies must be done)
     */
    public function canStartTask(int $taskId): array
    {
        $blockedBy = $this->db->fetchAll("
            SELECT t.id, t.title, t.status
            FROM {$this->table} d
            JOIN tasks t ON d.depends_on_task_id = t.id
            WHERE d.task_id = :taskId AND t.status != 'done'
        ", ['taskId' => $taskId]);
        
        return [
            'can_start' => empty($blockedBy),
            'blocked_by' => $blockedBy
        ];
    }
    
    /**
     * Detect cycle using DFS
     * Returns true if adding this dependency would create a cycle
     */
    public function wouldCreateCycle(int $taskId, int $dependsOnTaskId): bool
    {
        // If taskId equals dependsOnTaskId, it's a self-loop
        if ($taskId === $dependsOnTaskId) {
            return true;
        }
        
        // Check if dependsOnTaskId already depends on taskId (direct or indirect)
        $visited = [];
        return $this->hasPath($dependsOnTaskId, $taskId, $visited);
    }
    
    /**
     * DFS to check if there's a path from start to target
     */
    private function hasPath(int $start, int $target, array &$visited): bool
    {
        if ($start === $target) {
            return true;
        }
        
        if (in_array($start, $visited)) {
            return false;
        }
        
        $visited[] = $start;
        
        // Get all tasks that $start depends on
        $dependencies = $this->db->fetchAll("
            SELECT depends_on_task_id FROM {$this->table}
            WHERE task_id = :taskId
        ", ['taskId' => $start]);
        
        foreach ($dependencies as $dep) {
            if ($this->hasPath((int)$dep['depends_on_task_id'], $target, $visited)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get all blocking tasks for a task (recursive)
     */
    public function getAllBlockingTasks(int $taskId): array
    {
        $blocking = [];
        $this->collectBlockingTasks($taskId, $blocking);
        return $blocking;
    }
    
    private function collectBlockingTasks(int $taskId, array &$blocking): void
    {
        $deps = $this->getDependencies($taskId);
        foreach ($deps as $dep) {
            if ($dep['status'] !== 'done') {
                $blocking[] = $dep;
            }
            $this->collectBlockingTasks((int)$dep['id'], $blocking);
        }
    }
}
