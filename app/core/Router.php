<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = $basePath;
    }

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->routes['GET'][$path] = ['handler' => $handler, 'middleware' => $middleware];
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->routes['POST'][$path] = ['handler' => $handler, 'middleware' => $middleware];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        if ($this->basePath !== '' && ($path === $this->basePath || strpos($path, $this->basePath . '/') === 0)) {
            $path = substr($path, strlen($this->basePath));
        }

        $path = '/' . trim($path, '/');
        // También permite abrir explícitamente public/index.php en XAMPP.
        if ($path === '/index.php') {
            $path = '/';
        }

        $lookupMethod = $method === 'HEAD' ? 'GET' : $method;
        $route = $this->routes[$lookupMethod][$path] ?? null;

        if ($route === null) {
            $allowed = [];
            foreach ($this->routes as $registeredMethod => $paths) {
                if (isset($paths[$path])) {
                    $allowed[] = $registeredMethod;
                    if ($registeredMethod === 'GET') {
                        $allowed[] = 'HEAD';
                    }
                }
            }
            if ($allowed !== []) {
                http_response_code(405);
                header('Allow: ' . implode(', ', $allowed));
                if($this->isApiUri($uri)){ApiResponse::error(405,'method_not_allowed','Método no permitido.');return;}
                (new Controller())->render('errors/405', ['title' => 'Método no permitido']);
                return;
            }

            http_response_code(404);
            if($this->isApiUri($uri)){ApiResponse::error(404,'not_found','Recurso no encontrado.');return;}
            (new Controller())->render('errors/404', ['title' => 'Página no encontrada']);
            return;
        }

        foreach ($route['middleware'] as $middleware) {
            if (!(new $middleware())->handle()) {
                return;
            }
        }
        [$controller, $action] = $route['handler'];
        (new $controller())->$action();
    }

    public function isApiUri(string $uri): bool
    {
        $path=parse_url($uri,PHP_URL_PATH)?:'/';
        if($this->basePath!==''&&strpos($path,$this->basePath.'/')===0){$path=substr($path,strlen($this->basePath));}
        return $path==='/api'||strpos($path,'/api/')===0;
    }
}
