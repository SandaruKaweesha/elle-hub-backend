<?php

require_once __DIR__ . "/../app/controller/UserController.php";


// POST http://localhost/elle-hub-backend/user/register
$router->post(
    "/user/register",
    [UserController::class, "registerUser"]
);


// GET http://localhost/elle-hub-backend/user/getAllUsers
$router->get(
    "/user/getAllUsers",
    [UserController::class, "getAllUsers"]
);


// GET http://localhost/elle-hub-backend/user/search
$router->get(
    "/user/search",
    [UserController::class, "searchUser"]
);


// PUT http://localhost/elle-hub-backend/user/update
$router->put(
    "/user/update",
    [UserController::class, "updateUser"]
);


// DELETE http://localhost/elle-hub-backend/user/delete
$router->delete(
    "/user/delete",
    [UserController::class, "deleteUser"]
);