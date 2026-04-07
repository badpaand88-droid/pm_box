<?php
/**
 * Environment loader
 * Loads .env file and sets environment variables
 */

class EnvLoader
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
    }

    public function load(): void
    {
        $envFile = $this->basePath . '/.env';
        
        if (!file_exists($envFile)) {
            throw new RuntimeException('.env file not found at ' . $envFile);
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse key=value
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (($value[0] === '"' && $value[-1] === '"') || 
                    ($value[0] === "'" && $value[-1] === "'")) {
                    $value = substr($value, 1, -1);
                }

                if (!getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default ? 'true' : 'false');
        return in_array(strtolower((string)$value), ['true', '1', 'yes', 'on'], true);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }
}
