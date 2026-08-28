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
        $playerName = $data['playerName'] ?? $data['player_name'] ?? null;
        if (empty($playerName)) {
            return ["success" => false, "message" => "Player name is required", "code" => 400];
        }

        $player = new Player(
            null,
            $teamUserId,
            $playerName,
            (isset($data['age']) && $data['age'] !== '') ? (int)$data['age'] : null,
            $data['position'] ?? null,
            $data['contactNumber'] ?? $data['contact_number'] ?? null
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

        // Verify ownership if teamUserId is available
        if ($teamUserId > 0 && (int)$existingPlayer['team_user_id'] !== $teamUserId) {
            return ["success" => false, "message" => "Unauthorized access to player record", "code" => 403];
        }

        $playerName = $data['playerName'] ?? $data['player_name'] ?? null;
        if (empty($playerName)) {
            return ["success" => false, "message" => "Player name is required", "code" => 400];
        }

        $player = new Player(
            $playerId,
            (int)($existingPlayer['team_user_id'] ?? $teamUserId),
            $playerName,
            (isset($data['age']) && $data['age'] !== '') ? (int)$data['age'] : null,
            $data['position'] ?? null,
            $data['contactNumber'] ?? $data['contact_number'] ?? null
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

        // Verify ownership if teamUserId is available
        if ($teamUserId > 0 && (int)$existingPlayer['team_user_id'] !== $teamUserId) {
            return ["success" => false, "message" => "Unauthorized access to player record", "code" => 403];
        }


        $deleted = $this->playerRepository->delete($playerId);

        if ($deleted) {
            return ["success" => true, "message" => "Player deleted successfully", "code" => 200];
        }
        return ["success" => false, "message" => "Failed to delete player record", "code" => 500];
    }

    public function getPlayersByTeam(int $teamUserId): array
    {
        $players = $this->playerRepository->findByTeamUserId($teamUserId);
        return ["success" => true, "data" => $players, "code" => 200];
    }
}
