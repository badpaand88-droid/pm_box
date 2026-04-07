<?php

class Category extends BaseModel
{
    protected string $table = 'categories';
    
    /**
     * Default categories to create for each project
     */
    public const DEFAULT_CATEGORIES = [
        ['name' => 'Разработка', 'color' => '#3b82f6'],
        ['name' => 'Маркетинг', 'color' => '#8b5cf6'],
        ['name' => 'Юр. часть', 'color' => '#f59e0b'],
        ['name' => 'Дизайн', 'color' => '#ec4899'],
        ['name' => 'Тестирование', 'color' => '#22c55e'],
        ['name' => 'Документация', 'color' => '#64748b'],
    ];
    
    /**
     * Create default categories for a project
     */
    public function createDefaultCategories(int $projectId): void
    {
        foreach (self::DEFAULT_CATEGORIES as $category) {
            $this->create([
                'project_id' => $projectId,
                'name' => $category['name'],
                'color' => $category['color']
            ]);
        }
    }
    
    /**
     * Get all categories for a project
     */
    public function getByProject(int $projectId): array
    {
        return $this->db->fetchAll("
            SELECT * FROM {$this->table}
            WHERE project_id = :projectId OR project_id IS NULL
            ORDER BY name
        ", ['projectId' => $projectId]);
    }
    
    /**
     * Get all global categories (not tied to a project)
     */
    public function getGlobal(): array
    {
        return $this->db->fetchAll("
            SELECT * FROM {$this->table}
            WHERE project_id IS NULL
            ORDER BY name
        ");
    }
    
    /**
     * Create a category
     */
    public function create(array $data): int
    {
        return parent::create($data);
    }
    
    /**
     * Get category with task count
     */
    public function getWithTaskCount(int $projectId): array
    {
        return $this->db->fetchAll("
            SELECT c.*, COUNT(t.id) as task_count
            FROM {$this->table} c
            LEFT JOIN tasks t ON c.id = t.category_id AND t.project_id = :projectId
            WHERE c.project_id = :projectId OR c.project_id IS NULL
            GROUP BY c.id
            ORDER BY c.name
        ", ['projectId' => $projectId]);
    }
}
