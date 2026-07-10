<?php

require_once __DIR__ . "/../app/controller/TournamentController.php";

$router->post(
    "/organizer/tournament/create",
    [TournamentController::class, "createTournament"]
);

$router->get(
    "/admin/tournaments/pending",
    [TournamentController::class, "getPendingTournaments"]
);

$router->get(
    "/tournaments",
    [TournamentController::class, "getApprovedTournaments"]
);
$router->put(
    "/organizer/tournament/{id}/status",
    [TournamentController::class, "updateTournamentStatus"]
);

$router->put(
    "/admin/tournament/{id}/approvalStatus",
    [TournamentController::class, "updateApprovalStatus"]
);
//Filter by the status
$router->get(
    "/tournaments/filter",
    [TournamentController::class, "filterTournamentsByStatus"]
);