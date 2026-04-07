<?php
/**
 * Authentication helper class
 * Handles user authentication, sessions, and authorization
 */

class Auth
{
    private static ?array $currentUser = null;

    /**
     * Start session if not already started
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }

    /**
     * Attempt to login a user
     */
    public static function login(string $email, string $password): array
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT u.*, 
                   COALESCE(pm.role, u.role) as project_role
            FROM users u
            LEFT JOIN project_members pm ON u.id = pm.user_id
            WHERE u.email = ? AND u.is_active = 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            $updateStmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);

            // Store in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();

            // Regenerate session ID for security
            session_regenerate_id(true);

            return ['success' => true, 'user' => $user];
        }

        return ['success' => false, 'error' => 'Invalid email or password'];
    }

    /**
     * Logout current user
     */
    public static function logout(): void
    {
        self::startSession();
        
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        self::$currentUser = null;
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        self::startSession();
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }

        // Check session lifetime
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > (SESSION_LIFETIME * 60)) {
            self::logout();
            return false;
        }

        return true;
    }

    /**
     * Get current logged in user
     */
    public static function user(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        
        self::$currentUser = $stmt->fetch() ?: null;
        
        // If user not found or inactive, logout
        if (self::$currentUser === null) {
            self::logout();
        }

        return self::$currentUser;
    }

    /**
     * Get current user ID
     */
    public static function id(): ?int
    {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if current user has role
     */
    public static function hasRole(string|array $roles): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($user['role'], $roles, true);
    }

    /**
     * Check if current user is admin
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }

    /**
     * Require authentication, redirect to login if not authenticated
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }
    }

    /**
     * Require specific role, redirect if not authorized
     */
    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();
        
        if (!self::hasRole($roles)) {
            http_response_code(403);
            die('Access denied. Insufficient permissions.');
        }
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        self::startSession();
        
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(?string $token): bool
    {
        self::startSession();
        
        if (empty($token) || empty($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }

        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    /**
     * Regenerate CSRF token
     */
    public static function regenerateCsrfToken(): string
    {
        self::startSession();
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        return $_SESSION[CSRF_TOKEN_NAME];
    }
}
