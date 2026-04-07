<?php

class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $db = Database::getInstance();
        
        $user = $db->fetch("SELECT * FROM users WHERE email = :email AND is_active = 1", ['email' => $email]);
        
        if ($user && password_verify($password, $user['password'])) {
            self::login($user);
            return true;
        }
        
        return false;
    }
    
    public static function login(array $user): void
    {
        Session::set('user_id', $user['id']);
        Session::set('user_email', $user['email']);
        Session::set('user_role', $user['role']);
        
        // Update last login
        $db = Database::getInstance();
        $db->update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        
        Session::regenerate();
    }
    
    public static function logout(): void
    {
        Session::destroy();
        session_start();
    }
    
    public static function check(): bool
    {
        return Session::has('user_id');
    }
    
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        
        $db = Database::getInstance();
        $userId = Session::get('user_id');
        
        return $db->fetch("SELECT id, email, full_name, role, avatar FROM users WHERE id = :id", ['id' => $userId]);
    }
    
    public static function id(): ?int
    {
        return Session::get('user_id');
    }
    
    public static function isAdmin(): bool
    {
        return Session::get('user_role') === 'admin';
    }
    
    public static function isManager(): bool
    {
        $role = Session::get('user_role');
        return $role === 'admin' || $role === 'manager';
    }
}
