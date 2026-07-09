<?php

require_once __DIR__ . "/../app/controller/TournamentController.php";

$router->post(
    "/organizer/tournament/create",
    [TournamentController::class, "createTournament"]
);

