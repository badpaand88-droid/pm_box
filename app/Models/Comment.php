<?php

class Comment extends BaseModel
{
    protected string $table = 'comments';
    
    public function getByTask(int $taskId): array
    {
        return $this->db->fetchAll("
            SELECT c.*, u.full_name, u.avatar
            FROM {$this->table} c
            JOIN users u ON c.user_id = u.id
            WHERE c.task_id = :taskId
            ORDER BY c.created_at ASC
        ", ['taskId' => $taskId]);
    }
    
    public function create(int $taskId, int $userId, string $content): int
    {
        return parent::create([
            'task_id' => $taskId,
            'user_id' => $userId,
            'content' => $content
        ]);
    }
    
    public function update(int $id, int $userId, string $content): int
    {
        // Verify ownership
        $comment = $this->find($id);
        if ($comment['user_id'] !== $userId) {
            return 0;
        }
        
        return parent::update($id, ['content' => $content]);
    }
}
