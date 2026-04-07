<?php

namespace App\Controllers;

use App\Models\User;

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
        Auth::startSession();
    }

    /**
     * Show login page
     */
    public function showLogin(): void
    {
        if (Auth::isLoggedIn()) {
            redirect(APP_URL . '/dashboard');
        }

        require APP_PATH . '/views/auth/login.php';
    }

    /**
     * Process login
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(APP_URL . '/auth/login');
        }

        require_csrf();

        $email = trim(post('email', ''));
        $password = post('password', '');
        $remember = post('remember', false);

        // Validation
        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Email and password are required';
            redirect(APP_URL . '/auth/login');
        }

        $result = Auth::login($email, $password);

        if ($result['success']) {
            // Handle remember me
            if ($remember) {
                ini_set('session.cookie_lifetime', 30 * 24 * 60 * 60); // 30 days
            }

            redirect(APP_URL . '/dashboard');
        } else {
            $_SESSION['login_error'] = $result['error'];
            redirect(APP_URL . '/auth/login');
        }
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        Auth::logout();
        redirect(APP_URL . '/auth/login');
    }

    /**
     * Show registration page
     */
    public function showRegister(): void
    {
        if (Auth::isLoggedIn()) {
            redirect(APP_URL . '/dashboard');
        }

        require APP_PATH . '/views/auth/register.php';
    }

    /**
     * Process registration
     */
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(APP_URL . '/auth/register');
        }

        require_csrf();

        $firstName = trim(post('first_name', ''));
        $lastName = trim(post('last_name', ''));
        $email = trim(post('email', ''));
        $password = post('password', '');
        $passwordConfirm = post('password_confirm', '');

        // Validation
        $errors = [];

        if (empty($firstName)) {
            $errors[] = 'First name is required';
        }

        if (empty($lastName)) {
            $errors[] = 'Last name is required';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match';
        }

        // Check if email exists
        if ($this->userModel->findByEmail($email)) {
            $errors[] = 'Email already registered';
        }

        if (!empty($errors)) {
            $_SESSION['register_errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            redirect(APP_URL . '/auth/register');
        }

        // Create user
        $userId = $this->userModel->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
            'role' => 'developer'
        ]);

        if ($userId) {
            // Auto login
            Auth::login($email, $password);
            redirect(APP_URL . '/dashboard');
        } else {
            $_SESSION['register_errors'] = ['Registration failed. Please try again.'];
            redirect(APP_URL . '/auth/register');
        }
    }

    /**
     * Show profile page
     */
    public function showProfile(): void
    {
        Auth::requireLogin();
        
        $user = Auth::user();
        require APP_PATH . '/views/auth/profile.php';
    }

    /**
     * Update profile
     */
    public function updateProfile(): void
    {
        Auth::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(APP_URL . '/profile');
        }

        require_csrf();

        $userId = Auth::id();
        $firstName = trim(post('first_name', ''));
        $lastName = trim(post('last_name', ''));
        $password = post('password', '');
        $passwordConfirm = post('password_confirm', '');

        $errors = [];

        if (empty($firstName)) {
            $errors[] = 'First name is required';
        }

        if (empty($lastName)) {
            $errors[] = 'Last name is required';
        }

        if (!empty($password)) {
            if (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            }
            
            if ($password !== $passwordConfirm) {
                $errors[] = 'Passwords do not match';
            }
        }

        if (!empty($errors)) {
            $_SESSION['profile_errors'] = $errors;
            redirect(APP_URL . '/profile');
        }

        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName
        ];

        if (!empty($password)) {
            $data['password'] = $password;
        }

        if ($this->userModel->update($userId, $data)) {
            $_SESSION['profile_success'] = 'Profile updated successfully';
        } else {
            $_SESSION['profile_errors'] = ['Update failed. Please try again.'];
        }

        redirect(APP_URL . '/profile');
    }
}
