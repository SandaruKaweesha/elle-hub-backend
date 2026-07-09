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

            if (
                $route["method"] === $requestMethod &&
                $route["path"] === $requestUri
            ) {

                $controllerClass = $route["handler"][0];

                $controllerMethod = $route["handler"][1];


                $controller = new $controllerClass();


                $controller->$controllerMethod();


                return;
            }
        }


        http_response_code(404);


        echo json_encode([
            "success" => false,
            "message" => "API Not Found"
        ]);
    }
}