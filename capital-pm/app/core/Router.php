<?php
/**
 * Simple Router class
 * Handles URL routing and request dispatching
 */

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $basePath = '';

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Register a GET route
     */
    public function get(string $path, string|array $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, string|array $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, string|array $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, string|array $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Register a route for any method
     */
    public function any(string $path, string|array $handler): self
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $path, $handler);
    }

    /**
     * Add a route to the routes array
     */
    private function addRoute(string|array $methods, string $path, string|array $handler): self
    {
        $methods = is_array($methods) ? $methods : [$methods];
        
        // Normalize path
        $path = '/' . trim($path, '/');
        
        foreach ($methods as $method) {
            $this->routes[$method][$path] = $handler;
        }

        return $this;
    }

    /**
     * Register middleware for a route
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    /**
     * Dispatch the request to the appropriate handler
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        
        // Remove base path if present
        if ($this->basePath !== '' && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }
        
        $uri = '/' . trim($uri, '/');
        
        // Find matching route
        $handler = $this->findRoute($method, $uri);
        
        if ($handler === null) {
            // Try to find a dynamic route with parameters
            $result = $this->findDynamicRoute($method, $uri);
            
            if ($result === null) {
                http_response_code(404);
                $this->renderError('Page not found');
                return;
            }
            
            $handler = $result['handler'];
            $params = $result['params'];
        } else {
            $params = [];
        }

        // Run middleware
        foreach ($this->middleware as $middlewareClass) {
            $middleware = new $middlewareClass();
            $response = $middleware->handle();
            
            if ($response === false) {
                return;
            }
        }

        // Call handler
        $this->callHandler($handler, $params);
    }

    /**
     * Find exact route match
     */
    private function findRoute(string $method, string $uri): ?array
    {
        return $this->routes[$method][$uri] ?? null;
    }

    /**
     * Find dynamic route with parameters
     */
    private function findDynamicRoute(string $method, string $uri): ?array
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $route => $handler) {
            $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);
                return ['handler' => $handler, 'params' => $params];
            }
        }

        return null;
    }

    /**
     * Call the route handler
     */
    private function callHandler(string|array $handler, array $params): void
    {
        if (is_string($handler)) {
            // Handler is a closure or function name
            if (strpos($handler, '@') !== false) {
                // Handler is Controller@method
                [$controller, $method] = explode('@', $handler);
                $controllerClass = "App\\Controllers\\{$controller}";
                
                if (!class_exists($controllerClass)) {
                    throw new RuntimeException("Controller {$controllerClass} not found");
                }
                
                $controllerInstance = new $controllerClass();
                
                if (!method_exists($controllerInstance, $method)) {
                    throw new RuntimeException("Method {$method} not found in {$controllerClass}");
                }
                
                call_user_func_array([$controllerInstance, $method], $params);
            } else {
                // Handler is a function
                call_user_func($handler);
            }
        } elseif (is_array($handler)) {
            // Handler is [Controller::class, 'method']
            call_user_func_array($handler, $params);
        }
    }

    /**
     * Render error page
     */
    private function renderError(string $message): void
    {
        http_response_code(404);
        echo json_encode(['error' => $message]);
    }

    /**
     * Generate URL for a route
     */
    public static function url(string $path): string
    {
        return APP_URL . '/' . ltrim($path, '/');
    }

    /**
     * Redirect to a URL
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }

    /**
     * Get current URI
     */
    public static function currentUri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    }
}
