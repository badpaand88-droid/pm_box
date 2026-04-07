<?php

namespace App\Models;

class Category
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /**
     * Find category by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all categories for a project
     */
    public function getAllForProject(int $projectId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, COUNT(t.id) as task_count
            FROM categories c
            LEFT JOIN tasks t ON c.id = t.category_id
            WHERE c.project_id = ?
            GROUP BY c.id
            ORDER BY c.sort_order, c.name
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    /**
     * Create new category
     */
    public function create(array $data): int|false
    {
        // Get max sort order
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories WHERE project_id = ?"
        );
        $stmt->execute([$data['project_id']]);
        $sortOrder = (int) $stmt->fetchColumn();

        $sql = "INSERT INTO categories (project_id, name, color, sort_order) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            $data['project_id'],
            $data['name'],
            $data['color'] ?? '#3498db',
            $sortOrder
        ]);

        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Update category
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowedFields = ['name', 'color', 'sort_order'];
        
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
        
        $sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Delete category
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Reorder categories
     */
    public function reorder(int $projectId, array $order): bool
    {
        // $order is array of [id => new_sort_order]
        Database::beginTransaction();
        
        try {
            $stmt = $this->db->prepare("UPDATE categories SET sort_order = ? WHERE id = ? AND project_id = ?");
            
            foreach ($order as $id => $sortOrder) {
                $stmt->execute([$sortOrder, $id, $projectId]);
            }
            
            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollback();
            return false;
        }
    }

    /**
     * Get category with task count
     */
    public function getWithTaskCount(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, COUNT(t.id) as task_count
            FROM categories c
            LEFT JOIN tasks t ON c.id = t.category_id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
