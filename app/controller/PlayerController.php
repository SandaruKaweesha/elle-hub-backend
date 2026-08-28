<?php

require_once __DIR__ . "/../service/PlayerService.php";
require_once __DIR__ . "/../core/AuthMiddleware.php";

class PlayerController
{
    private PlayerService $playerService;

    public function __construct()
    {
        $this->playerService = new PlayerService();
    }

    public function createPlayer()
    {
        $payload = null;
        try {
            $payload = AuthMiddleware::getPayload();
        } catch (Exception $e) {}

        $requestBody = file_get_contents("php://input");
        $data = json_decode($requestBody, true) ?: [];

        $teamUserId = (int)($payload['userId'] ?? $payload['user_id'] ?? $payload['id'] ?? 0);
        if (!$teamUserId && !empty($data['teamUserId'])) {
            $teamUserId = (int)$data['teamUserId'];
        }
        if (!$teamUserId && !empty($data['team_user_id'])) {
            $teamUserId = (int)$data['team_user_id'];
        }

        if (!$teamUserId) {
            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Team User ID is required."]);
            return;
        }

        $result = $this->playerService->createPlayer($teamUserId, $data ?: []);

        http_response_code($result['code']);
        header("Content-Type: application/json");
        echo json_encode(["success" => $result['success'], "message" => $result['message']]);
    }

    public function updatePlayer($playerId)
    {
        $payload = null;
        try {
            $payload = AuthMiddleware::getPayload();
        } catch (Exception $e) {}

        $requestBody = file_get_contents("php://input");
        $data = json_decode($requestBody, true) ?: [];

        $teamUserId = (int)($payload['userId'] ?? $payload['user_id'] ?? $payload['id'] ?? 0);
        if (!$teamUserId && !empty($data['teamUserId'])) {
            $teamUserId = (int)$data['teamUserId'];
        }
        if (!$teamUserId && !empty($data['team_user_id'])) {
            $teamUserId = (int)$data['team_user_id'];
        }

        $result = $this->playerService->updatePlayer((int)$playerId, $teamUserId, $data ?: []);

        http_response_code($result['code']);
        header("Content-Type: application/json");
        echo json_encode(["success" => $result['success'], "message" => $result['message']]);
    }

    public function deletePlayer($playerId)
    {
        $payload = null;
        try {
            $payload = AuthMiddleware::getPayload();
        } catch (Exception $e) {}

        $teamUserId = (int)($payload['userId'] ?? $payload['user_id'] ?? $payload['id'] ?? 0);

        $result = $this->playerService->deletePlayer((int)$playerId, $teamUserId);

        http_response_code($result['code']);
        header("Content-Type: application/json");
        echo json_encode(["success" => $result['success'], "message" => $result['message']]);
    }

    public function getPlayersByTeam($teamUserId)
    {
        $result = $this->playerService->getPlayersByTeam((int)$teamUserId);

        http_response_code($result['code']);
        header("Content-Type: application/json");
        echo json_encode(["success" => $result['success'], "data" => $result['data']]);
    }
}

