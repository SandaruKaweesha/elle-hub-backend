<?php

require_once __DIR__ . "/../service/TournamentTeamRequestService.php";
require_once __DIR__ . "/../core/AuthMiddleware.php";

class TournamentTeamRequestController
{
    private TournamentTeamRequestService $service;

    public function __construct()
    {
        $this->service = new TournamentTeamRequestService();
    }

    public function submitRequest()
    {
        $authPayload = AuthMiddleware::requireRole(['TEAM']);
        $teamUserId = (int)$authPayload['userId'];

        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $tournamentId = isset($requestObject->tournamentId) ? (int)$requestObject->tournamentId : null;

        if (!$tournamentId) {
            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Tournament ID is required."]);
            return;
        }

        $result = $this->service->submitRequest($tournamentId, $teamUserId);
        
        if ($result["success"]) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }

        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function inviteTeam()
    {
        $authPayload = AuthMiddleware::requireRole(['ORGANIZER']);

        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $tournamentId = isset($requestObject->tournamentId) ? (int)$requestObject->tournamentId : null;
        $teamUserId = isset($requestObject->teamUserId) ? (int)$requestObject->teamUserId : null;

        if (!$tournamentId || !$teamUserId) {
            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Tournament ID and Team User ID are required."]);
            return;
        }

        $result = $this->service->sendOrganizerInvitation($tournamentId, $teamUserId);

        if ($result["success"]) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }

        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function getTeamRequests($teamUserId = null)
    {
        $authPayload = AuthMiddleware::requireRole(['TEAM', 'ADMIN']);
        $authenticatedId = (int)$authPayload['userId'];

        if ($teamUserId === null || !is_numeric($teamUserId) || (int)$teamUserId <= 0) {
            $idToQuery = $authenticatedId;
        } else {
            $idToQuery = (int)$teamUserId;
        }

        // Verify authorization
        if ($idToQuery !== $authenticatedId) {
            http_response_code(403);
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Unauthorized access to request records."]);
            return;
        }

        $result = $this->service->getTeamRequests($idToQuery);

        http_response_code(200);
        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function cancelRequest()
    {
        $authPayload = AuthMiddleware::requireRole(['TEAM']);
        $teamUserId = (int)$authPayload['userId'];

        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $tournamentId = isset($requestObject->tournamentId) ? (int)$requestObject->tournamentId : null;

        if (!$tournamentId) {
            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Tournament ID is required."]);
            return;
        }

        $result = $this->service->cancelRequest($tournamentId, $teamUserId);

        if ($result["success"]) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function leaveTournament()
    {
        $authPayload = AuthMiddleware::requireRole(['TEAM']);
        $teamUserId = (int)$authPayload['userId'];

        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $tournamentId = isset($requestObject->tournamentId) ? (int)$requestObject->tournamentId : null;

        if (!$tournamentId) {
            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Tournament ID is required."]);
            return;
        }

        $result = $this->service->leaveTournament($tournamentId, $teamUserId);

        if ($result["success"]) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function getOrganizerTeamRequests($organizerId = null)
    {
        $authPayload = AuthMiddleware::requireRole(['ORGANIZER', 'ADMIN']);
        $authenticatedId = (int)$authPayload['userId'];

        $idToQuery = $organizerId !== null ? (int)$organizerId : $authenticatedId;

        $result = $this->service->getOrganizerTeamRequests($idToQuery);

        http_response_code(200);
        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function getTournamentTeamRequests($tournamentId)
    {
        AuthMiddleware::requireRole(['ORGANIZER', 'ADMIN', 'TEAM']);
        $result = $this->service->getTournamentTeamRequests((int)$tournamentId);

        http_response_code(200);
        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function approveRequest()
    {
        $authPayload = AuthMiddleware::requireRole(['ORGANIZER', 'TEAM']);
        
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $tournamentId = isset($requestObject->tournamentId) ? (int)$requestObject->tournamentId : null;
        $teamUserId = isset($requestObject->teamUserId) ? (int)$requestObject->teamUserId : null;

        if (!$tournamentId || !$teamUserId) {
            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Tournament ID and Team User ID are required."]);
            return;
        }

        $result = $this->service->approveRequest($tournamentId, $teamUserId);

        if ($result["success"]) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function rejectRequest()
    {
        $authPayload = AuthMiddleware::requireRole(['ORGANIZER', 'TEAM']);
        
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $tournamentId = isset($requestObject->tournamentId) ? (int)$requestObject->tournamentId : null;
        $teamUserId = isset($requestObject->teamUserId) ? (int)$requestObject->teamUserId : null;

        if (!$tournamentId || !$teamUserId) {
            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Tournament ID and Team User ID are required."]);
            return;
        }

        $result = $this->service->rejectRequest($tournamentId, $teamUserId);

        if ($result["success"]) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function respondToTeamRequest($tournamentId)
    {
        $authPayload = AuthMiddleware::requireRole(['ORGANIZER', 'TEAM', 'ADMIN']);

        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $teamUserId = isset($requestObject->teamUserId) ? (int)$requestObject->teamUserId : null;
        $status = isset($requestObject->status) ? strtoupper($requestObject->status) : null;

        if (!$tournamentId || !$teamUserId || !$status) {
            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Tournament ID, Team User ID, and status are required."]);
            return;
        }

        if ($status === 'APPROVED' || $status === 'ACCEPTED') {
            $result = $this->service->approveRequest((int)$tournamentId, $teamUserId);
        } else {
            $result = $this->service->rejectRequest((int)$tournamentId, $teamUserId);
        }

        http_response_code(200);
        header("Content-Type: application/json");
        echo json_encode($result);
    }
}
