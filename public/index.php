<?php

/**
 * PM Box - Main Entry Point
 * 
 * This is the single entry point for all requests.
 * Place this file in your document root (public/).
 */

// Error reporting (disable in production)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start session
session_start();

// Load environment variables from .env file (if exists above public/)
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    foreach (explode("\n", $envContent) as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Autoloader
spl_autoload_register(function ($class) {
    // Core classes (no namespace)
    $coreFile = __DIR__ . '/../app/Core/' . $class . '.php';
    if (file_exists($coreFile)) {
        require_once $coreFile;
        return;
    }
    
    // Namespaced classes (App\Controllers, App\Models, etc.)
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize database connection check
try {
    Database::getInstance();
} catch (PDOException $e) {
    if (strpos($_SERVER['REQUEST_URI'], '/install') === false) {
        http_response_code(500);
        echo "Database connection failed. Please run the installer.";
        exit;
    }
}

// Define routes
$router = new Router();

// Public routes
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');

// Protected routes
$router->get('/dashboard', 'DashboardController@index');

// Projects
$router->get('/projects', 'ProjectController@index');
$router->get('/projects/create', 'ProjectController@create');
$router->post('/projects/create', 'ProjectController@store');
$router->get('/projects/:id', 'ProjectController@show');
$router->post('/projects/:id/update', 'ProjectController@update');
$router->post('/projects/:id/delete', 'ProjectController@delete');

// Tasks
$router->get('/tasks/create/:projectId', 'TaskController@create');
$router->post('/tasks/create', 'TaskController@store');
$router->get('/tasks/:id', 'TaskController@show');
$router->post('/tasks/:id/update', 'TaskController@update');
$router->post('/tasks/:id/delete', 'TaskController@delete');
$router->post('/tasks/:id/comment', 'TaskController@addComment');

// Export routes
$router->get('/export', 'ExportController@index');
$router->post('/export/excel', 'ExportController@toExcel');

// API routes
$router->get('/api/notifications', 'ApiController@notifications');
$router->post('/api/notifications/:id/read', 'ApiController@markNotificationRead');
$router->post('/api/notifications/read-all', 'ApiController@markAllNotificationsRead');
$router->get('/api/search', 'ApiController@search');
$router->get('/api/projects/:id/stats', 'ApiController@taskStats');
$router->get('/api/team/workload', 'ApiController@teamWorkload');

// Default redirect to dashboard or login
$router->get('/', function() {
    if (Auth::check()) {
        header('Location: /dashboard');
    } else {
        header('Location: /login');
    }
    exit;
});

// Handle 404 for undefined routes
$router->get('/:any', function() {
    http_response_code(404);
    echo "404 - Page Not Found";
});

// Dispatch the request
$router->dispatch();
