<?php

require_once __DIR__ . "/../app/controller/AuthController.php";

$router->post(
    "/auth/login",
    [AuthController::class, "login"]
);

$router->post(
    "/auth/logout",
    [AuthController::class, "logout"]
);
