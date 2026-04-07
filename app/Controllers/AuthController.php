<?php

namespace App\Controllers;

class AuthController extends BaseController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        
        $this->view('auth/login');
    }
    
    public function login(): void
    {
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Invalid security token.');
            $this->back();
        }
        
        $validator = Validator::make($_POST, [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);
        
        if ($validator->fails()) {
            Session::setFlash('error', $validator->firstError());
            $this->back();
        }
        
        if (Auth::attempt($_POST['email'], $_POST['password'])) {
            Session::setFlash('success', 'Welcome back!');
            $this->redirect('/dashboard');
        } else {
            Session::setFlash('error', 'Invalid email or password.');
            $this->back();
        }
    }
    
    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        
        $this->view('auth/register');
    }
    
    public function register(): void
    {
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Invalid security token.');
            $this->back();
        }
        
        $validator = Validator::make($_POST, [
            'full_name' => 'required|min:2|max:100',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'password_confirm' => 'required'
        ]);
        
        if ($validator->fails()) {
            Session::setFlash('error', $validator->firstError());
            $this->back();
        }
        
        if ($_POST['password'] !== $_POST['password_confirm']) {
            Session::setFlash('error', 'Passwords do not match.');
            $this->back();
        }
        
        $userModel = new User();
        
        // Check if email exists
        if ($userModel->findByEmail($_POST['email'])) {
            Session::setFlash('error', 'Email already registered.');
            $this->back();
        }
        
        // Create user
        $userId = $userModel->create([
            'full_name' => $_POST['full_name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'role' => 'developer'
        ]);
        
        if ($userId) {
            // Auto login
            $user = $userModel->find($userId);
            Auth::login($user);
            
            Session::setFlash('success', 'Account created successfully!');
            $this->redirect('/dashboard');
        } else {
            Session::setFlash('error', 'Failed to create account.');
            $this->back();
        }
    }
    
    public function logout(): void
    {
        Auth::logout();
        Session::setFlash('success', 'You have been logged out.');
        $this->redirect('/login');
    }
}
