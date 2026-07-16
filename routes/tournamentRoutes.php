<?php

require_once __DIR__ . "/../app/controller/TournamentController.php";
require_once __DIR__ . "/../app/controller/TournamentTeamRequestController.php";

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

//Get tournament by the ID
$router->get(
    "/tournaments/{id}",
    [TournamentController::class, "getTournamentById"]
);

//Update tournament by the ID
$router->put(
    "/organizer/tournament/{id}",
    [TournamentController::class, "updateTournament"]
);
$router->get(
    "/organizer/{id}/tournaments",
    [TournamentController::class, "getOrganizerTournaments"]
);

// Tournament Join Participation Requests
$router->post(
    "/tournament/request",
    [TournamentTeamRequestController::class, "submitRequest"]
);

$router->get(
    "/team/{id}/requests",
    [TournamentTeamRequestController::class, "getTeamRequests"]
);

$router->post(
    "/tournament/request/cancel",
    [TournamentTeamRequestController::class, "cancelRequest"]
);

$router->post(
    "/tournament/request/leave",
    [TournamentTeamRequestController::class, "leaveTournament"]
);