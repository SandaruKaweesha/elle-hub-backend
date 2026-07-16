<?php

require_once __DIR__ . "/../model/Player.php";
require_once __DIR__ . "/../repository/PlayerRepository.php";

class PlayerService
{
    private PlayerRepository $playerRepository;

    public function __construct()
    {
        $this->playerRepository = new PlayerRepository();
    }

    public function createPlayer(int $teamUserId, array $data): array
    {
        if (empty($data['playerName'])) {
            return ["success" => false, "message" => "Player name is required", "code" => 400];
        }

        $player = new Player(
            null,
            $teamUserId,
            $data['playerName'],
            isset($data['age']) ? (int)$data['age'] : null,
            $data['position'] ?? null,
            $data['contactNumber'] ?? null
        );

        $saved = $this->playerRepository->save($player);

        if ($saved) {
            return ["success" => true, "message" => "Player created successfully", "code" => 201];
        }
        return ["success" => false, "message" => "Failed to save player record", "code" => 500];
    }

    public function updatePlayer(int $playerId, int $teamUserId, array $data): array
    {
        $existingPlayer = $this->playerRepository->findById($playerId);
        if (!$existingPlayer) {
            return ["success" => false, "message" => "Player not found", "code" => 404];
        }

        // Verify ownership
        if ((int)$existingPlayer['team_user_id'] !== $teamUserId) {
            return ["success" => false, "message" => "Unauthorized access to player record", "code" => 403];
        }

        if (empty($data['playerName'])) {
            return ["success" => false, "message" => "Player name is required", "code" => 400];
        }

        $player = new Player(
            $playerId,
            $teamUserId,
            $data['playerName'],
            isset($data['age']) ? (int)$data['age'] : null,
            $data['position'] ?? null,
            $data['contactNumber'] ?? null
        );

        $updated = $this->playerRepository->update($player);

        if ($updated) {
            return ["success" => true, "message" => "Player updated successfully", "code" => 200];
        }
        return ["success" => false, "message" => "Failed to update player record", "code" => 500];
    }

    public function deletePlayer(int $playerId, int $teamUserId): array
    {
        $existingPlayer = $this->playerRepository->findById($playerId);
        if (!$existingPlayer) {
            return ["success" => false, "message" => "Player not found", "code" => 404];
        }

        // Verify ownership
        if ((int)$existingPlayer['team_user_id'] !== $teamUserId) {
            return ["success" => false, "message" => "Unauthorized access to player record", "code" => 403];
        }

        $deleted = $this->playerRepository->delete($playerId);

        if ($deleted) {
            return ["success" => true, "message" => "Player deleted successfully", "code" => 200];
        }
        return ["success" => false, "message" => "Failed to delete player record", "code" => 500];
    }
}
