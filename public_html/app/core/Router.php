<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\Authenticate;
use App\Middleware\ForcePasswordReset;

/**
 * Simple router with named routes, parameter binding, and middleware.
 *
 * Usage:
 *   $router->get('/', [HomeController::class, 'index'], 'home');
 *   $router->get('/dashboard', [DashboardController::class, 'index'], 'dashboard',
 *       [Authenticate::class, ForcePasswordReset::class]);
 */
final class Router
{
    /** @var array<int, array{method: string, path: string, handler: callable|array{class-string, string}, name?: string, middleware?: array<class-string>}> */
    private array $routes = [];

    /** @var array<string, string> */
    private array $namedRoutes = [];

    public function __construct(private ?Container $container = null) {}

    public function get(
        string $path,
        callable|array $handler,
        ?string $name = null,
        array $middleware = []
    ): self {
        return $this->addRoute('GET', $path, $handler, $name, $middleware);
    }

    public function post(
        string $path,
        callable|array $handler,
        ?string $name = null,
        array $middleware = []
    ): self {
        return $this->addRoute('POST', $path, $handler, $name, $middleware);
    }

    private function addRoute(
        string $method,
        string $path,
        callable|array $handler,
        ?string $name,
        array $middleware
    ): self {
        $this->routes[] = [
            'method'     => $method,
            'path'       => $path,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];

        if ($name !== null) {
            $this->namedRoutes[$name] = $path;
        }

        return $this;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->getCurrentUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertPathToRegex($route['path']);
            if (!preg_match($pattern, $uri, $matches)) {
                continue;
            }

            $params = array_filter($matches, fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);

            // Run middleware before handler. Supports both:
            //   [Authenticate::class]                    => no params
            //   [Authorize::class => 'admin']            => pass role string
            foreach ($route['middleware'] ?? [] as $key => $mwClass) {
                if (is_string($key)) {
                    // Form: [Class::class => $param]
                    $param = $mwClass;
                    $mwClass = $key;
                    $mw = $this->container
                        ? $this->container->makeWithParam($mwClass, $param)
                        : new $mwClass($this->resolveAuth(), $param);
                } else {
                    $mw = $this->container ? $this->container->make($mwClass) : new $mwClass();
                }
                $mw->handle();
            }

            $this->runHandler($route['handler'], $params);
            return;
        }

        if (!headers_sent()) {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            header($protocol . ' 404 Not Found', true, 404);
        }
        http_response_code(404);
        View::render('errors/404', [
            'title'  => 'Halaman Tidak Dijumpai',
            'layout' => 'layouts/auth',
        ]);
    }

    private function getCurrentUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $uri = rawurldecode($uri);

        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '' && $scriptName !== '/') {
            $uri = preg_replace('#^' . preg_quote($scriptName, '#') . '#', '', $uri) ?? $uri;
        }

        return '/' . ltrim($uri, '/');
    }

    private function convertPathToRegex(string $path): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * @param callable|array{class-string, string} $handler
     * @param array<string, string> $params
     */
    private function runHandler(callable|array $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, array_values($params));
            return;
        }

        [$class, $method] = $handler;
        $controller = $this->container ? $this->container->make($class) : new $class();
        // Pass route parameters positionally so the controller method's own
        // parameter names do not have to match the route placeholder names.
        call_user_func_array([$controller, $method], array_values($params));
    }

    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route named '{$name}' not found.");
        }

        $path = $this->namedRoutes[$name];
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', urlencode((string) $value), $path);
        }

        return $path;
    }

    /**
     * Resolve the AuthService for middleware that need it when no container
     * is available (fallback path).
     */
    private function resolveAuth(): object
    {
        return $this->container ? $this->container->make(\App\Services\AuthService::class) : new \App\Services\AuthService(
            new \App\Repositories\UserRepository(),
            new \App\Repositories\PasswordResetRepository()
        );
    }
}
