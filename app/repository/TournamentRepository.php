<?php
require_once __DIR__ . "/../model/Tournament.php";
require_once __DIR__ . "/../../config/Database.php";

class TournamentRepository{
    private PDO $connection;

    public function __construct(){
        $this->connection = Database::getConnection();
    }

    /**
     * Save a tournament and return the generated id
     * Uses named parameters and PDO prepared statements
     */
    public function save(Tournament $tournament): int{
        $sql = "INSERT INTO tournaments (
            organizer_id,
            title,
            description,
            location,
            start_date,
            end_date,
            maximum_team_limit,
            rules,
            prize_details,
            status,
            created_at
        ) VALUES (
            :organizer_id,
            :title,
            :description,
            :location,
            :start_date,
            :end_date,
            :maximum_team_limit,
            :rules,
            :prize_details,
            :status,
            NOW()
        )";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(":organizer_id", $tournament->getOrganizerId(), PDO::PARAM_INT);
        $statement->bindValue(":title", $tournament->getTitle());
        $statement->bindValue(":description", $tournament->getDescription());
        $statement->bindValue(":location", $tournament->getLocation());
        $statement->bindValue(":start_date", $tournament->getStartDate());
        $statement->bindValue(":end_date", $tournament->getEndDate());
        $statement->bindValue(":maximum_team_limit", $tournament->getMaximumTeamLimit(), PDO::PARAM_INT);
        $statement->bindValue(":rules", $tournament->getRules());
        $statement->bindValue(":prize_details", $tournament->getPrizeDetails());
        $statement->bindValue(":status", $tournament->getStatus());

        $statement->execute();

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Find tournaments by status.
     * Reusable for PENDING, APPROVED, REJECTED, etc.
     */
    public function findByStatus(string $status): array
    {
        $sql = "SELECT * FROM tournaments WHERE status = :status";
        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":status", $status);
        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }


//    Update the Status(Cancel)
    public function updateStatus(int $tournamentId, string $status): bool
    {
        $sql = "UPDATE tournaments
            SET status = :status
            WHERE tournament_id = :tournament_id";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(
            ":status",
            $status
        );

        $statement->bindValue(
            ":tournament_id",
            $tournamentId,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->rowCount() > 0;
    }
}

