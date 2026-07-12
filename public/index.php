<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
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