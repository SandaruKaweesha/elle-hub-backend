<?php

require_once __DIR__ . "/../app/controller/PlayerController.php";

// POST http://localhost/elle-hub-backend/player
$router->post(
    "/player",
    [PlayerController::class, "createPlayer"]
);

// PUT http://localhost/elle-hub-backend/player/5
$router->put(
    "/player/{id}",
    [PlayerController::class, "updatePlayer"]
);

// DELETE http://localhost/elle-hub-backend/player/5
$router->delete(
    "/player/{id}",
    [PlayerController::class, "deletePlayer"]
);
