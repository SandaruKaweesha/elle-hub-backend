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

// GET http://localhost/elle-hub-backend/user/stats
$router->get(
    "/user/stats",
    [UserController::class, "getStats"]
);

// GET http://localhost/elle-hub-backend/user/search
$router->get(
    "/user/search",
    [UserController::class, "searchUser"]
);

//GET http://localhost/elle-hub-backend/user/5
$router->get(
    "/user/{id}",
    [UserController::class, "getUserById"]);


// PUT http://localhost/elle-hub-backend/user/update
$router->put(
    "/user/update",
    [UserController::class, "updateUser"]
);


// DELETE http://localhost/elle-hub-backend/user/delete/5
$router->delete(
    "/user/delete/{id}",
    [UserController::class, "deleteUser"]
);
