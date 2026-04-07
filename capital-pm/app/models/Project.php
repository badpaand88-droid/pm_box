<?php

namespace App\Models;

class Project
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /**
     * Find project by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all projects with optional filtering
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT p.*, u.first_name as owner_first_name, u.last_name as owner_last_name
                FROM projects p
                INNER JOIN users u ON p.owner_id = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND p.priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['owner_id'])) {
            $sql .= " AND p.owner_id = ?";
            $params[] = $filters['owner_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }

        if (!empty($filters['user_id'])) {
            // Get projects where user is member or owner
            $sql .= " AND (p.owner_id = ? OR p.id IN (
                SELECT project_id FROM project_members WHERE user_id = ?
            ))";
            $params = array_merge($params, [$filters['user_id'], $filters['user_id']]);
        }

        $sql .= " ORDER BY p.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int) $filters['limit'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Create new project
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO projects (name, description, status, priority, start_date, end_date, budget, owner_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['status'] ?? 'planning',
            $data['priority'] ?? 'medium',
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['budget'] ?? null,
            $data['owner_id']
        ]);

        if ($result) {
            $projectId = $this->db->lastInsertId();
            
            // Add owner as project member with owner role
            $memberStmt = $this->db->prepare(
                "INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'owner')"
            );
            $memberStmt->execute([$projectId, $data['owner_id']]);
            
            return $projectId;
        }

        return false;
    }

    /**
     * Update project
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowedFields = ['name', 'description', 'status', 'priority', 'start_date', 'end_date', 'budget'];
        
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
        
        $sql = "UPDATE projects SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Delete project
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get project members
     */
    public function getMembers(int $projectId): array
    {
        $stmt = $this->db->prepare("
            SELECT u.*, pm.role as member_role, pm.joined_at
            FROM project_members pm
            INNER JOIN users u ON pm.user_id = u.id
            WHERE pm.project_id = ?
            ORDER BY pm.role, u.last_name, u.first_name
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    /**
     * Add member to project
     */
    public function addMember(int $projectId, int $userId, string $role = 'member'): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE role = ?"
        );
        return $stmt->execute([$projectId, $userId, $role, $role]);
    }

    /**
     * Remove member from project
     */
    public function removeMember(int $projectId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM project_members WHERE project_id = ? AND user_id = ?"
        );
        return $stmt->execute([$projectId, $userId]);
    }

    /**
     * Check if user is member of project
     */
    public function isMember(int $projectId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id = ?"
        );
        $stmt->execute([$projectId, $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Get user's role in project
     */
    public function getUserRole(int $projectId, int $userId): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT role FROM project_members WHERE project_id = ? AND user_id = ?"
        );
        $stmt->execute([$projectId, $userId]);
        $result = $stmt->fetch();
        return $result ? $result['role'] : null;
    }

    /**
     * Get project statistics
     */
    public function getStatistics(int $projectId): array
    {
        $stats = [];

        // Task counts by status
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) as count 
            FROM tasks 
            WHERE project_id = ? 
            GROUP BY status
        ");
        $stmt->execute([$projectId]);
        $taskStatuses = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Total tasks
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
        $stmt->execute([$projectId]);
        $stats['total_tasks'] = (int) $stmt->fetchColumn();

        // Tasks by status
        $stats['tasks_by_status'] = [
            'todo' => $taskStatuses['todo'] ?? 0,
            'in_progress' => $taskStatuses['in_progress'] ?? 0,
            'review' => $taskStatuses['review'] ?? 0,
            'done' => $taskStatuses['done'] ?? 0,
            'closed' => $taskStatuses['closed'] ?? 0,
        ];

        // Overdue tasks
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM tasks 
            WHERE project_id = ? AND status NOT IN ('done', 'closed') 
            AND due_date < CURDATE()
        ");
        $stmt->execute([$projectId]);
        $stats['overdue_tasks'] = (int) $stmt->fetchColumn();

        // Member count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ?");
        $stmt->execute([$projectId]);
        $stats['member_count'] = (int) $stmt->fetchColumn();

        // Category count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE project_id = ?");
        $stmt->execute([$projectId]);
        $stats['category_count'] = (int) $stmt->fetchColumn();

        return $stats;
    }

    /**
     * Count projects
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM projects WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND (owner_id = ? OR id IN (
                SELECT project_id FROM project_members WHERE user_id = ?
            ))";
            $params = array_merge($params, [$filters['user_id'], $filters['user_id']]);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
