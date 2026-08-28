<?php

require_once __DIR__ . "/../app/controller/UserController.php";
require_once __DIR__ . "/../app/controller/NotificationController.php";
require_once __DIR__ . "/../app/controller/TeamController.php";


// GET http://localhost/elle-hub-backend/teams/rankings
$router->get(
    "/teams/rankings",
    [TeamController::class, "getTeamRankings"]
);

// GET http://localhost/elle-hub-backend/team/{id}/stats
$router->get(
    "/team/{id}/stats",
    [TeamController::class, "getTeamStats"]
);



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

// GET http://localhost/elle-hub-backend/user/rankings
$router->get(
    "/user/rankings",
    [UserController::class, "getTeamRankings"]
);


// GET http://localhost/elle-hub-backend/user/search
$router->get(
    "/user/search",
    [UserController::class, "searchUser"]
);

// GET http://localhost/elle-hub-backend/user/profile
$router->get(
    "/user/profile",
    [UserController::class, "getCurrentUserProfile"]
);

//GET http://localhost/elle-hub-backend/user/5
$router->get(
    "/user/{id}",
    [UserController::class, "getUserById"]
);

// PUT http://localhost/elle-hub-backend/user/update
$router->put(
    "/user/update",
    [UserController::class, "updateUser"]
);

// PUT http://localhost/elle-hub-backend/user/updatePassword
$router->put(
    "/user/updatePassword",
    [UserController::class, "updatePassword"]
);

// DELETE http://localhost/elle-hub-backend/user/delete/5
$router->delete(
    "/user/delete/{id}",
    [UserController::class, "deleteUser"]
);

// POST & PUT http://localhost/elle-hub-backend/user/approve/5
$router->post(
    "/user/approve/{id}",
    [UserController::class, "approveUser"]
);

$router->put(
    "/user/approve/{id}",
    [UserController::class, "approveUser"]
);

// POST & PUT http://localhost/elle-hub-backend/user/reject/5
$router->post(
    "/user/reject/{id}",
    [UserController::class, "rejectUser"]
);

$router->put(
    "/user/reject/{id}",
    [UserController::class, "rejectUser"]
);

// PUT http://localhost/elle-hub-backend/user/updateStatus/5
$router->put(
    "/user/updateStatus/{id}",
    [UserController::class, "updateStatus"]
);

// POST http://localhost/elle-hub-backend/user/request-deletion
$router->post(
    "/user/request-deletion",
    [UserController::class, "requestDeletion"]
);

// --- NOTIFICATION ENDPOINTS ---
$router->get(
    "/user/{id}/notifications",
    [NotificationController::class, "getUserNotifications"]
);

$router->put(
    "/user/{id}/notifications/{notifId}/read",
    [NotificationController::class, "markAsRead"]
);

$router->put(
    "/user/{id}/notifications/read-all",
    [NotificationController::class, "markAllAsRead"]
);
