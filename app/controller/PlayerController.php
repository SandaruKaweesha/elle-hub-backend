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
        $payload = AuthMiddleware::requireRole(['TEAM']);
        $teamUserId = (int)$payload['userId'];

        $requestBody = file_get_contents("php://input");
        $data = json_decode($requestBody, true);

        $result = $this->playerService->createPlayer($teamUserId, $data ?: []);

        http_response_code($result['code']);
        header("Content-Type: application/json");
        echo json_encode(["success" => $result['success'], "message" => $result['message']]);
    }

    public function updatePlayer($playerId)
    {
        $payload = AuthMiddleware::requireRole(['TEAM']);
        $teamUserId = (int)$payload['userId'];

        $requestBody = file_get_contents("php://input");
        $data = json_decode($requestBody, true);

        $result = $this->playerService->updatePlayer((int)$playerId, $teamUserId, $data ?: []);

        http_response_code($result['code']);
        header("Content-Type: application/json");
        echo json_encode(["success" => $result['success'], "message" => $result['message']]);
    }

    public function deletePlayer($playerId)
    {
        $payload = AuthMiddleware::requireRole(['TEAM']);
        $teamUserId = (int)$payload['userId'];

        $result = $this->playerService->deletePlayer((int)$playerId, $teamUserId);

        http_response_code($result['code']);
        header("Content-Type: application/json");
        echo json_encode(["success" => $result['success'], "message" => $result['message']]);
    }
}
