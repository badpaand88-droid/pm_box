<?php

class BaseController
{
    protected array $data = [];
    protected string $layout = 'main';
    
    public function __construct()
    {
        $this->data['csrf_token'] = CSRF::getToken();
        $this->data['user'] = Auth::user();
        $this->data['flash_success'] = Session::getFlash('success');
        $this->data['flash_error'] = Session::getFlash('error');
    }
    
    protected function view(string $view, array $data = []): void
    {
        $this->data = array_merge($this->data, $data);
        
        extract($this->data);
        
        $viewPath = __DIR__ . "/../Views/$view.php";
        
        if ($this->layout) {
            $layoutPath = __DIR__ . "/../Views/layouts/{$this->layout}.php";
            if (file_exists($layoutPath)) {
                include $layoutPath;
            } else {
                include $viewPath;
            }
        } else {
            include $viewPath;
        }
    }
    
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
    
    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }
    
    protected function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }
    
    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            Session::setFlash('error', 'Please log in to continue.');
            $this->redirect('/login');
        }
    }
    
    protected function requireRole(array $roles): void
    {
        $this->requireAuth();
        
        $user = Auth::user();
        if (!in_array($user['role'], $roles, true)) {
            Session::setFlash('error', 'You do not have permission to access this page.');
            $this->back();
        }
    }
}
