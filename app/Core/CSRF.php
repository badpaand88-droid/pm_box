<?php

class CSRF
{
    private static string $tokenKey = 'csrf_token';
    
    public static function generateToken(): string
    {
        if (empty($_SESSION[self::$tokenKey])) {
            $_SESSION[self::$tokenKey] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$tokenKey];
    }
    
    public static function getToken(): string
    {
        return self::generateToken();
    }
    
    public static function validateToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[self::$tokenKey])) {
            return false;
        }
        
        return hash_equals($_SESSION[self::$tokenKey], $token);
    }
    
    public static function regenerateToken(): string
    {
        $_SESSION[self::$tokenKey] = bin2hex(random_bytes(32));
        return $_SESSION[self::$tokenKey];
    }
    
    public static function inputField(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
