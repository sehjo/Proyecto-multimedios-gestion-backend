<?php

class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, array $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $this->toRegex($pattern),
            'handler' => $handler,
        ];
    }

    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, array $handler): void
    {
        $this->add('PUT', $pattern, $handler);
        $this->add('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, array $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    public function resource(string $name, string $controllerClass): void
    {
        $this->get("/$name", [$controllerClass, 'index']);
        $this->post("/$name", [$controllerClass, 'store']);
        $this->get("/$name/{id}", [$controllerClass, 'show']);
        $this->put("/$name/{id}", [$controllerClass, 'update']);
        $this->delete("/$name/{id}", [$controllerClass, 'destroy']);
    }

    private function toRegex(string $pattern): string
    {
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?<$1>[^/]+)', $pattern);

        return '#^' . $regex . '$#';
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            if (!preg_match($route['pattern'], $request->uri(), $matches)) {
                continue;
            }

            $params = array_filter(
                $matches,
                fn ($key) => !is_int($key),
                ARRAY_FILTER_USE_KEY
            );

            [$controllerClass, $action] = $route['handler'];
            $controller = new $controllerClass();

            $controller->$action($request, ...array_values($params));

            return;
        }

        Response::json(['message' => 'Recurso no encontrado.'], 404);
    }
}
