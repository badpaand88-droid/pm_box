<?php

class Router
{
    private array $routes = [];
    private array $middleware = [];
    
    public function get(string $path, string|array $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }
    
    public function post(string $path, string|array $handler): self
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }
    
    public function put(string $path, string|array $handler): self
    {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }
    
    public function delete(string $path, string|array $handler): self
    {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }
    
    private function addRoute(string $method, string $path, string|array $handler): void
    {
        $path = trim($path, '/');
        if ($path === '') {
            $path = '/';
        }
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'pattern' => $this->convertToRegex($path)
        ];
    }
    
    private function convertToRegex(string $path): string
    {
        // Convert :param to regex capture group
        $pattern = preg_replace('/:[a-zA-Z_]+/', '([a-zA-Z0-9_-]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = trim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if ($route['path'] === '/') {
                $routePath = '/';
            } else {
                $routePath = $route['path'];
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches); // Remove full match
                
                $handler = $route['handler'];
                
                if (is_string($handler)) {
                    [$controllerClass, $method] = explode('@', $handler);
                    $controllerClass = "App\\Controllers\\$controllerClass";
                    
                    if (!class_exists($controllerClass)) {
                        $controllerClass = $controllerClass;
                    }
                    
                    $controller = new $controllerClass();
                    call_user_func_array([$controller, $method], $matches);
                } else {
                    call_user_func_array($handler, $matches);
                }
                
                return;
            }
        }
        
        http_response_code(404);
        echo "404 - Page Not Found";
    }
}
