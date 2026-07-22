<?php

require_once __DIR__ . "/../app/controller/TournamentController.php";
require_once __DIR__ . "/../app/controller/TournamentTeamRequestController.php";
require_once __DIR__ . "/../app/controller/TournamentResultController.php";

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

$router->post(
    "/tournament/request/invite",
    [TournamentTeamRequestController::class, "inviteTeam"]
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

// Tournament Results (Final Awards)
$router->post(
    "/tournaments/{id}/results",
    [TournamentResultController::class, "saveResults"]
);

$router->get(
    "/tournaments/{id}/results",
    [TournamentResultController::class, "getResults"]
);

$router->get(
    "/organizer/{id}/team-requests",
    [TournamentTeamRequestController::class, "getOrganizerTeamRequests"]
);

$router->get(
    "/tournament/{id}/team-requests",
    [TournamentTeamRequestController::class, "getTournamentTeamRequests"]
);

$router->post(
    "/tournament/request/approve",
    [TournamentTeamRequestController::class, "approveRequest"]
);

$router->post(
    "/tournament/request/reject",
    [TournamentTeamRequestController::class, "rejectRequest"]
);

$router->get(
    "/admin/tournaments",
    [TournamentController::class, "getAllTournaments"]
);

// Tournament Assignments and Finalization
$router->get(
    "/tournament/{id}/assignments",
    [TournamentController::class, "getTournamentAssignments"]
);

$router->post(
    "/tournament/{id}/assignments",
    [TournamentController::class, "saveTournamentAssignments"]
);

$router->post(
    "/tournament/{id}/finalize",
    [TournamentController::class, "finalizeTournament"]
);

// Advanced Playground Request System
$router->get(
    "/tournament/{id}/playground-requests",
    [TournamentController::class, "getPlaygroundRequests"]
);

$router->post(
    "/tournament/{id}/playground-requests/send",
    [TournamentController::class, "sendPlaygroundRequest"]
);

$router->post(
    "/tournament/{id}/playground-requests/respond",
    [TournamentController::class, "respondToPlaygroundRequest"]
);

$router->get(
    "/playground/{id}/requests",
    [TournamentController::class, "getPlaygroundIncomingRequests"]
);

$router->post(
    "/tournament/playground-request/cancel",
    [TournamentController::class, "cancelPlaygroundRequest"]
);

// Advanced Sponsor Request System
$router->get(
    "/tournament/{id}/sponsor-requests",
    [TournamentController::class, "getSponsorRequests"]
);

$router->post(
    "/tournament/{id}/sponsor-requests/send",
    [TournamentController::class, "sendSponsorRequest"]
);

$router->post(
    "/tournament/{id}/sponsor-requests/respond",
    [TournamentController::class, "respondToSponsorRequest"]
);

$router->get(
    "/sponsor/{id}/requests",
    [TournamentController::class, "getSponsorIncomingRequests"]
);

// Advanced Referee Request System
$router->get(
    "/referee/{id}/requests",
    [TournamentController::class, "getRefereeIncomingRequests"]
);

$router->get(
    "/organizer/{id}/referee-requests",
    [TournamentController::class, "getOrganizerRefereeRequests"]
);

$router->get(
    "/tournament/{id}/referee-requests",
    [TournamentController::class, "getRefereeRequests"]
);

$router->post(
    "/tournament/{id}/referee-requests/send",
    [TournamentController::class, "sendRefereeRequest"]
);

$router->post(
    "/tournament/{id}/referee-requests/respond",
    [TournamentController::class, "respondToRefereeRequest"]
);

$router->post(
    "/tournament/referee-request/cancel",
    [TournamentController::class, "cancelRefereeRequest"]
);

$router->get(
    "/referee/{id}/availability-calendar",
    [TournamentController::class, "getRefereeAvailabilityCalendar"]
);

$router->get(
    "/referee/{id}/history",
    [TournamentController::class, "getRefereeOfficiatingHistory"]
);

$router->post(
    "/referee/availability/save",
    [TournamentController::class, "saveRefereeAvailability"]
);

$router->get(
    "/playground/{id}/availability-calendar",
    [TournamentController::class, "getPlaygroundAvailabilityCalendar"]
);

$router->post(
    "/playground/availability/save",
    [TournamentController::class, "savePlaygroundAvailability"]
);

$router->get(
    "/playground/{id}/history",
    [TournamentController::class, "getPlaygroundHostingHistory"]
);