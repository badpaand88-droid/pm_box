<?php

namespace App\Models;

class Task
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /**
     * Find task by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, 
                   a.first_name as assignee_first_name, a.last_name as assignee_last_name,
                   r.first_name as reporter_first_name, r.last_name as reporter_last_name,
                   c.name as category_name, c.color as category_color
            FROM tasks t
            LEFT JOIN users a ON t.assignee_id = a.id
            LEFT JOIN users r ON t.reporter_id = r.id
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all tasks with optional filtering
     */
    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT t.*, 
                   a.first_name as assignee_first_name, a.last_name as assignee_last_name,
                   r.first_name as reporter_first_name, r.last_name as reporter_last_name,
                   c.name as category_name, c.color as category_color
            FROM tasks t
            LEFT JOIN users a ON t.assignee_id = a.id
            LEFT JOIN users r ON t.reporter_id = r.id
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE 1=1";
        $params = [];

        if (!empty($filters['project_id'])) {
            $sql .= " AND t.project_id = ?";
            $params[] = $filters['project_id'];
        }

        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $sql .= " AND t.status IN ($placeholders)";
            $params = array_merge($params, $statuses);
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['assignee_id'])) {
            $sql .= " AND t.assignee_id = ?";
            $params[] = $filters['assignee_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['parent_task_id'])) {
            $sql .= " AND t.parent_task_id = ?";
            $params[] = $filters['parent_task_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }

        if (!empty($filters['overdue'])) {
            $sql .= " AND t.due_date < CURDATE() AND t.status NOT IN ('done', 'closed')";
        }

        $sql .= " ORDER BY t.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int) $filters['limit'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Create new task
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO tasks (title, description, project_id, category_id, status, priority, 
                assignee_id, reporter_id, parent_task_id, story_points, due_date, started_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['project_id'],
            $data['category_id'] ?? null,
            $data['status'] ?? 'todo',
            $data['priority'] ?? 'medium',
            $data['assignee_id'] ?? null,
            $data['reporter_id'],
            $data['parent_task_id'] ?? null,
            $data['story_points'] ?? null,
            $data['due_date'] ?? null,
            $data['started_at'] ?? null
        ]);

        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Update task
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowedFields = [
            'title', 'description', 'category_id', 'status', 'priority',
            'assignee_id', 'story_points', 'due_date', 'started_at', 'completed_at'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        
        $sql = "UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Delete task
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get tasks for Kanban board
     */
    public function getKanbanTasks(int $projectId): array
    {
        $tasks = $this->getAll(['project_id' => $projectId]);
        
        $kanban = [
            'todo' => [],
            'in_progress' => [],
            'review' => [],
            'done' => [],
            'closed' => []
        ];

        foreach ($tasks as $task) {
            $kanban[$task['status']][] = $task;
        }

        return $kanban;
    }

    /**
     * Get subtasks
     */
    public function getSubtasks(int $parentId): array
    {
        return $this->getAll(['parent_task_id' => $parentId]);
    }

    /**
     * Get task dependencies
     */
    public function getDependencies(int $taskId): array
    {
        $stmt = $this->db->prepare("
            SELECT td.*, t.title as depends_on_title, t.status as depends_on_status
            FROM task_dependencies td
            INNER JOIN tasks t ON td.depends_on_task_id = t.id
            WHERE td.task_id = ?
        ");
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
    }

    /**
     * Add task dependency
     */
    public function addDependency(int $taskId, int $dependsOnTaskId, string $type = 'finish_to_start'): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO task_dependencies (task_id, depends_on_task_id, dependency_type) 
             VALUES (?, ?, ?)"
        );
        return $stmt->execute([$taskId, $dependsOnTaskId, $type]);
    }

    /**
     * Remove task dependency
     */
    public function removeDependency(int $taskId, int $dependsOnTaskId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM task_dependencies WHERE task_id = ? AND depends_on_task_id = ?"
        );
        return $stmt->execute([$taskId, $dependsOnTaskId]);
    }

    /**
     * Move task to different status
     */
    public function changeStatus(int $taskId, string $newStatus): bool
    {
        $data = ['status' => $newStatus];
        
        // Set completed_at if moving to done/closed
        if (in_array($newStatus, ['done', 'closed'])) {
            $data['completed_at'] = date('Y-m-d');
        } else {
            $data['completed_at'] = null;
        }
        
        return $this->update($taskId, $data);
    }

    /**
     * Count tasks
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM tasks WHERE 1=1";
        $params = [];

        if (!empty($filters['project_id'])) {
            $sql .= " AND project_id = ?";
            $params[] = $filters['project_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['assignee_id'])) {
            $sql .= " AND assignee_id = ?";
            $params[] = $filters['assignee_id'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get recent tasks for user
     */
    public function getRecentForUser(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, p.name as project_name
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            WHERE t.assignee_id = ? OR t.reporter_id = ?
            ORDER BY t.updated_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $userId, $limit]);
        return $stmt->fetchAll();
    }
}
