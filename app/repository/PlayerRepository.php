<?php

require_once __DIR__ . "/../model/Player.php";
require_once __DIR__ . "/../../config/Database.php";

class PlayerRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function save(Player $player): bool
    {
        $sql = "INSERT INTO players 
                (team_user_id, player_name, age, position, contact_number)
                VALUES 
                (:team_user_id, :player_name, :age, :position, :contact_number)";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(":team_user_id", $player->getTeamUserId(), PDO::PARAM_INT);
        $statement->bindValue(":player_name", $player->getPlayerName());
        $statement->bindValue(":age", $player->getAge(), PDO::PARAM_INT);
        $statement->bindValue(":position", $player->getPosition());
        $statement->bindValue(":contact_number", $player->getContactNumber());

        return $statement->execute();
    }

    public function update(Player $player): bool
    {
        $sql = "UPDATE players 
                SET player_name = :player_name, 
                    age = :age, 
                    position = :position, 
                    contact_number = :contact_number
                WHERE player_id = :player_id";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(":player_name", $player->getPlayerName());
        $statement->bindValue(":age", $player->getAge(), PDO::PARAM_INT);
        $statement->bindValue(":position", $player->getPosition());
        $statement->bindValue(":contact_number", $player->getContactNumber());
        $statement->bindValue(":player_id", $player->getPlayerId(), PDO::PARAM_INT);

        return $statement->execute();
    }

    public function delete(int $playerId): bool
    {
        $sql = "DELETE FROM players WHERE player_id = :player_id";
        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":player_id", $playerId, PDO::PARAM_INT);
        
        return $statement->execute();
    }

    public function findById(int $playerId): ?array
    {
        $sql = "SELECT * FROM players WHERE player_id = :player_id";
        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":player_id", $playerId, PDO::PARAM_INT);
        $statement->execute();
        
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
