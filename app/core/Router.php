<?php

class Router
{
    private array $routes = [];

    public function post($path, $handler)
    {
        $this->addRoute("POST", $path, $handler);
    }

    public function get($path, $handler)
    {
        $this->addRoute("GET", $path, $handler);
    }

    public function put($path, $handler)
    {
        $this->addRoute("PUT", $path, $handler);
    }

    public function delete($path, $handler)
    {
        $this->addRoute("DELETE", $path, $handler);
    }

    private function addRoute($method, $path, $handler)
    {
        $this->routes[] = [
            "method" => $method,
            "path" => $path,
            "handler" => $handler
        ];
    }

    public function dispatch($requestUri, $requestMethod)
    {
        foreach ($this->routes as $route) {

            // First check the HTTP method
            if ($route["method"] !== $requestMethod) {
                continue;
            }

            // Convert dynamic parameters like {id} into regex
            $pattern = preg_replace(
                '/\{[^\/]+\}/',
                '([^\/]+)',
                $route["path"]
            );

            // Create complete regex pattern
            $pattern = "#^" . $pattern . "$#";

            // Check whether the request URI matches the route
            if (preg_match($pattern, $requestUri, $matches)) {

                // Remove the full matched URL
                array_shift($matches);

                $controllerClass = $route["handler"][0];
                $controllerMethod = $route["handler"][1];

                // Create Controller object
                $controller = new $controllerClass();

                // Call Controller method and pass URL parameters
                call_user_func_array(
                    [$controller, $controllerMethod],
                    $matches
                );

                return;
            }
        }

        http_response_code(404);

        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "API Not Found"
        ]);
    }
}