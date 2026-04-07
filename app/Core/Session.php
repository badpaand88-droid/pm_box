<?php

class Session
{
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set(string $key, mixed $value): void
    {
        self::init();
        $_SESSION[$key] = $value;
    }
    
    public static function get(string $key, mixed $default = null): mixed
    {
        self::init();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has(string $key): bool
    {
        self::init();
        return isset($_SESSION[$key]);
    }
    
    public static function remove(string $key): void
    {
        self::init();
        unset($_SESSION[$key]);
    }
    
    public static function destroy(): void
    {
        self::init();
        session_destroy();
    }
    
    public static function regenerate(): void
    {
        self::init();
        session_regenerate_id(true);
    }
    
    public static function setFlash(string $key, mixed $value): void
    {
        self::set("flash_$key", $value);
    }
    
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = self::get("flash_$key", $default);
        self::remove("flash_$key");
        return $value;
    }
}
