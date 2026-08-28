<?php

require_once __DIR__ . "/../app/controller/UserController.php";

// GET http://localhost/elle-hub-backend/teams/rankings
$router->get(
    "/teams/rankings",
    [UserController::class, "getTeamRankings"]
);

// GET http://localhost/elle-hub-backend/teams
$router->get(
    "/teams",
    [UserController::class, "getTeamRankings"]
);
