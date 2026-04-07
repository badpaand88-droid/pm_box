<?php

class User extends BaseModel
{
    protected string $table = 'users';
    
    public function findByEmail(string $email): ?array
    {
        return $this->db->fetch("SELECT * FROM {$this->table} WHERE email = :email", ['email' => $email]);
    }
    
    public function create(array $data): int
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return parent::create($data);
    }
    
    public function updatePassword(int $id, string $password): int
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        return $this->db->update($this->table, ['password' => $hashedPassword], 'id = :id', ['id' => $id]);
    }
    
    public function getAllExcept(int $excludeId = null): array
    {
        if ($excludeId) {
            return $this->db->fetchAll(
                "SELECT id, email, full_name, role, is_active FROM {$this->table} WHERE id != :excludeId ORDER BY full_name",
                ['excludeId' => $excludeId]
            );
        }
        
        return $this->db->fetchAll("SELECT id, email, full_name, role, is_active FROM {$this->table} ORDER BY full_name");
    }
    
    public function getDevelopers(): array
    {
        return $this->db->fetchAll(
            "SELECT id, email, full_name FROM {$this->table} WHERE role IN ('developer', 'admin', 'manager') AND is_active = 1 ORDER BY full_name"
        );
    }
}
