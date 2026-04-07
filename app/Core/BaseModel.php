<?php

class BaseModel
{
    protected string $table;
    protected Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function find(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM {$this->table} WHERE id = :id", ['id' => $id]);
    }
    
    public function findAll(array $conditions = [], array $params = []): array
    {
        $where = '';
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "$column = :$column";
                $params[$column] = $value;
            }
            $where = 'WHERE ' . implode(' AND ', $whereClauses);
        }
        
        return $this->db->fetchAll("SELECT * FROM {$this->table} $where", $params);
    }
    
    public function create(array $data): int
    {
        return $this->db->insert($this->table, $data);
    }
    
    public function update(int $id, array $data): int
    {
        return $this->db->update($this->table, $data, 'id = :id', ['id' => $id]);
    }
    
    public function delete(int $id): int
    {
        return $this->db->delete($this->table, 'id = :id', ['id' => $id]);
    }
    
    public function count(array $conditions = [], array $params = []): int
    {
        $where = '';
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "$column = :$column";
                $params[$column] = $value;
            }
            $where = 'WHERE ' . implode(' AND ', $whereClauses);
        }
        
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM {$this->table} $where", $params);
        return (int) ($result['count'] ?? 0);
    }
}
