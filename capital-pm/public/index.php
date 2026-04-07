<?php
/**
 * PM Box - Project Management System
 * Main entry point
 */

// Load environment variables
require_once __DIR__ . '/../app/config/env.php';
$envLoader = new EnvLoader(__DIR__ . '/..');
$envLoader->load();

// Load constants
require_once __DIR__ . '/../app/config/constants.php';

// Load core classes
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Helpers.php';

// Start session
Auth::startSession();

// Initialize router
$router = new Router();

// Public routes
$router->get('/auth/login', 'AuthController@showLogin');
$router->post('/auth/login', 'AuthController@login');
$router->get('/auth/register', 'AuthController@showRegister');
$router->post('/auth/register', 'AuthController@register');
$router->get('/auth/logout', 'AuthController@logout');

// Protected routes - Dashboard
$router->get('/dashboard', 'DashboardController@index');
$router->get('/api/dashboard/data', 'DashboardController@getData');

// Protected routes - Projects
$router->get('/projects', 'ProjectController@index');
$router->get('/projects/create', 'ProjectController@create');
$router->post('/projects', 'ProjectController@store');
$router->get('/projects/{id}', 'ProjectController@show');
$router->get('/projects/{id}/edit', 'ProjectController@edit');
$router->post('/projects/{id}', 'ProjectController@update');
$router->post('/projects/{id}/delete', 'ProjectController@delete');
$router->post('/projects/{id}/members', 'ProjectController@addMember');
$router->post('/projects/{projectId}/members/{userId}/remove', 'ProjectController@removeMember');

// Protected routes - Tasks
$router->get('/projects/{id}/kanban', 'TaskController@kanban');
$router->get('/tasks/{id}', 'TaskController@show');
$router->post('/tasks', 'TaskController@store');
$router->post('/tasks/{id}', 'TaskController@update');
$router->post('/tasks/{id}/delete', 'TaskController@delete');
$router->post('/tasks/{id}/move', 'TaskController@move');
$router->post('/tasks/{id}/comments', 'TaskController@addComment');

// Protected routes - Notifications
$router->get('/api/notifications', 'NotificationController@getNotifications');
$router->post('/api/notifications/{id}/read', 'NotificationController@markAsRead');
$router->post('/api/notifications/read-all', 'NotificationController@markAllAsRead');
$router->post('/api/notifications/{id}/delete', 'NotificationController@delete');

// Protected routes - Export
$router->get('/export/projects', 'ExportController@projects');
$router->get('/export/tasks/{id}', 'ExportController@tasks');

// Protected routes - Profile
$router->get('/profile', 'AuthController@showProfile');
$router->post('/profile', 'AuthController@updateProfile');

// Default route
$router->get('/', function() {
    if (Auth::isLoggedIn()) {
        Router::redirect(APP_URL . '/dashboard');
    } else {
        Router::redirect(APP_URL . '/auth/login');
    }
});

// Dispatch request
try {
    $router->dispatch();
} catch (Exception $e) {
    if (getenv('APP_ENV') === 'development') {
        echo '<pre>';
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo $e->getTraceAsString();
        echo '</pre>';
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
}
