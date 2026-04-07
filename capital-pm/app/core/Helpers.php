<?php
/**
 * Helper functions
 * Common utility functions used throughout the application
 */

/**
 * Escape HTML output to prevent XSS
 */
function e(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get POST data with optional default value
 */
function post(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

/**
 * Get GET data with optional default value
 */
function get(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

/**
 * Return JSON response
 */
function json(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Return error JSON response
 */
function json_error(string $message, int $statusCode = 400): void
{
    json(['success' => false, 'error' => $message], $statusCode);
}

/**
 * Return success JSON response
 */
function json_success(array $data = [], string $message = 'Success'): void
{
    json(array_merge(['success' => true, 'message' => $message], $data));
}

/**
 * Check if request is AJAX
 */
function is_ajax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Generate CSRF token field for forms
 */
function csrf_field(): string
{
    $token = Auth::generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e($token) . '">';
}

/**
 * Validate CSRF token from request
 */
function validate_csrf(?string $token = null): bool
{
    $token = $token ?? $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    return Auth::validateCsrfToken($token);
}

/**
 * Require valid CSRF token or die
 */
function require_csrf(?string $token = null): void
{
    if (!validate_csrf($token)) {
        if (is_ajax()) {
            json_error('Invalid CSRF token', 403);
        }
        http_response_code(403);
        die('Invalid CSRF token');
    }
}

/**
 * Format date for display
 */
function format_date(?string $date, string $format = 'M d, Y'): string
{
    if (!$date) {
        return '-';
    }
    
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '-';
}

/**
 * Format datetime for display
 */
function format_datetime(?string $datetime, string $format = 'M d, Y H:i'): string
{
    if (!$datetime) {
        return '-';
    }
    
    $timestamp = strtotime($datetime);
    return $timestamp ? date($format, $timestamp) : '-';
}

/**
 * Get relative time ago (e.g., "2 hours ago")
 */
function time_ago(string $datetime): string
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return format_date($datetime);
    }
}

/**
 * Truncate text to specified length
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Generate random string
 */
function random_string(int $length = 32): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get file extension
 */
function get_extension(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file extension is allowed
 */
function is_allowed_extension(string $filename): bool
{
    $ext = get_extension($filename);
    return in_array($ext, ALLOWED_EXTENSIONS, true);
}

/**
 * Format file size
 */
function format_file_size(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Get avatar URL for user
 */
function get_avatar_url(?int $userId, ?string $avatar = null, int $size = 40): string
{
    if ($avatar) {
        return APP_URL . '/uploads/' . $avatar;
    }
    
    // Use gravatar or placeholder
    $email = '';
    if ($userId) {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $email = $user['email'] ?? '';
    }
    
    $hash = md5(strtolower(trim($email)));
    return 'https://www.gravatar.com/avatar/' . $hash . '?s=' . $size . '&d=mp';
}

/**
 * Get priority badge class
 */
function priority_class(string $priority): string
{
    return match($priority) {
        'critical' => 'badge-danger',
        'high' => 'badge-warning',
        'medium' => 'badge-info',
        'low' => 'badge-secondary',
        default => 'badge-secondary'
    };
}

/**
 * Get status badge class
 */
function status_class(string $status, string $type = 'task'): string
{
    if ($type === 'task') {
        return match($status) {
            'done', 'closed' => 'badge-success',
            'in_progress' => 'badge-primary',
            'review' => 'badge-warning',
            'todo' => 'badge-secondary',
            default => 'badge-secondary'
        };
    }
    
    // Project status
    return match($status) {
        'completed' => 'badge-success',
        'active' => 'badge-primary',
        'on_hold' => 'badge-warning',
        'cancelled' => 'badge-danger',
        'planning' => 'badge-secondary',
        default => 'badge-secondary'
    };
}

/**
 * Paginate query results
 */
function paginate(array $items, int $page = 1, int $perPage = DEFAULT_PER_PAGE): array
{
    $page = max(1, $page);
    $total = count($items);
    $totalPages = ceil($total / $perPage);
    $page = min($page, $totalPages);
    
    $offset = ($page - 1) * $perPage;
    $pagedItems = array_slice($items, $offset, $perPage);
    
    return [
        'items' => $pagedItems,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
        ]
    ];
}

/**
 * Debug helper (only in development)
 */
function dd(mixed ...$vars): void
{
    if (getenv('APP_ENV') !== 'development') {
        return;
    }
    
    echo '<pre style="background:#f5f5f5;padding:15px;border-radius:5px;">';
    foreach ($vars as $var) {
        var_dump($var);
        echo "\n";
    }
    echo '</pre>';
    exit;
}
