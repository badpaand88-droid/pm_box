<?php
/**
 * Application constants
 */

// Application version
define('APP_VERSION', '1.0.0');
define('APP_NAME', 'PM Box');

// Paths
define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// URL
define('APP_URL', EnvLoader::get('APP_URL', 'http://localhost'));

// Database config
define('DB_HOST', EnvLoader::get('DB_HOST', 'localhost'));
define('DB_NAME', EnvLoader::get('DB_NAME', 'pm_box'));
define('DB_USER', EnvLoader::get('DB_USER', 'root'));
define('DB_PASS', EnvLoader::get('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// Session config
define('SESSION_LIFETIME', EnvLoader::getInt('SESSION_LIFETIME', 120));
define('SESSION_NAME', 'pmbox_session');

// Security
define('SECRET_KEY', EnvLoader::get('SECRET_KEY', 'change_me_to_random_32_chars'));
define('CSRF_TOKEN_NAME', 'csrf_token');

// Upload settings
define('MAX_UPLOAD_SIZE', EnvLoader::getInt('MAX_UPLOAD_SIZE', 10485760)); // 10MB default
define('ALLOWED_EXTENSIONS', explode(',', EnvLoader::get('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip')));

// Pagination
define('DEFAULT_PER_PAGE', 20);

// Task statuses
define('TASK_STATUSES', [
    'todo' => 'To Do',
    'in_progress' => 'In Progress',
    'review' => 'Review',
    'done' => 'Done',
    'closed' => 'Closed'
]);

// Task priorities
define('TASK_PRIORITIES', [
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
    'critical' => 'Critical'
]);

// Project statuses
define('PROJECT_STATUSES', [
    'planning' => 'Planning',
    'active' => 'Active',
    'on_hold' => 'On Hold',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
]);

// User roles
define('USER_ROLES', [
    'admin' => 'Administrator',
    'manager' => 'Manager',
    'developer' => 'Developer',
    'viewer' => 'Viewer'
]);

// Timezone
date_default_timezone_set('UTC');

// Error reporting (adjust for production)
if (EnvLoader::get('APP_ENV', 'production') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Set locale
setlocale(LC_ALL, 'en_US.UTF-8');
