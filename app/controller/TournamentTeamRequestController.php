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

    public function getTeamRequests($teamUserId = null)
    {
        $authPayload = AuthMiddleware::requireRole(['TEAM']);
        $authenticatedId = (int)$authPayload['userId'];

        $idToQuery = $teamUserId !== null ? (int)$teamUserId : $authenticatedId;

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
}
