<?php

/**
 * PM Box - Database Installer
 * 
 * Run this script once to create all database tables.
 * Access via: http://your-domain.com/install.php
 * 
 * DELETE THIS FILE after successful installation!
 */

// Load environment
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found. Please create it from .env.example");
}

$envContent = file_get_contents($envFile);
foreach (explode("\n", $envContent) as $line) {
    $line = trim($line);
    if ($line && !str_starts_with($line, '#')) {
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, '"\'');
    }
}

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'pm_box';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

echo "<!DOCTYPE html>
<html>
<head>
    <title>PM Box - Installation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #2563eb; }
        .success { color: #22c55e; padding: 10px; background: #dcfce7; border-radius: 4px; margin: 10px 0; }
        .error { color: #ef4444; padding: 10px; background: #fee2e2; border-radius: 4px; margin: 10px 0; }
        .info { color: #3b82f6; padding: 10px; background: #dbeafe; border-radius: 4px; margin: 10px 0; }
        pre { background: #f1f5f9; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .warning { color: #f59e0b; font-weight: bold; }
    </style>
</head>
<body>
<h1>🚀 PM Box Installation</h1>";

try {
    // Connect to MySQL
    $dsn = "mysql:host=$host;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<div class='success'>✓ Connected to MySQL server successfully</div>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    
    echo "<div class='success'>✓ Database '$dbname' is ready</div>";
    
    // Load migrations
    $migrations = require __DIR__ . '/../config/migrations.php';
    
    echo "<h2>Creating Tables...</h2>";
    
    foreach ($migrations as $tableName => $sql) {
        try {
            $pdo->exec($sql);
            echo "<div class='success'>✓ Table '$tableName' created</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>✗ Error creating table '$tableName': " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    // Create default admin user
    echo "<h2>Creating Default Admin User...</h2>";
    
    $adminEmail = 'admin@example.com';
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, full_name, role, is_active, created_at)
            VALUES (:email, :password, 'Admin User', 'admin', 1, NOW())
            ON DUPLICATE KEY UPDATE email = email
        ");
        $stmt->execute(['email' => $adminEmail, 'password' => $adminPassword]);
        
        echo "<div class='success'>✓ Default admin user created</div>";
        echo "<div class='info'>
            <strong>Default Admin Credentials:</strong><br>
            Email: <code>admin@example.com</code><br>
            Password: <code>admin123</code><br>
            <span class='warning'>⚠️ Please change this password immediately after login!</span>
        </div>";
    } catch (PDOException $e) {
        echo "<div class='error'>✗ Error creating admin user: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "<h2>✅ Installation Complete!</h2>";
    echo "<div class='info'>
        <p>Your PM Box application is now ready to use.</p>
        <p><strong>Important Security Steps:</strong></p>
        <ol>
            <li>Delete this <code>install.php</code> file from your server</li>
            <li>Login and change the default admin password</li>
            <li>Update the CSRF_SECRET in your .env file with a random string</li>
            <li>Set APP_ENV to 'production' in your .env file</li>
        </ol>
    </div>";
    
    echo "<p><a href='/public/' style='display:inline-block;padding:10px 20px;background:#2563eb;color:white;text-decoration:none;border-radius:4px;margin-top:20px;'>Go to Login Page →</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='error'>
        <strong>Database Connection Failed!</strong><br><br>
        Error: " . htmlspecialchars($e->getMessage()) . "<br><br>
        Please check your database credentials in the .env file.
    </div>";
}

echo "</body></html>";
