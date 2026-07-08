<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../app/core/Router.php";

$router = new Router();

require_once __DIR__ . "/../routes/api.php";

$requestUri = parse_url(
    $_SERVER["REQUEST_URI"],
    PHP_URL_PATH
);

$requestUri = str_replace(
    "/elle-hub-backend",
    "",
    $requestUri
);

$requestMethod = $_SERVER["REQUEST_METHOD"];

$router->dispatch(
    $requestUri,
    $requestMethod
);