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
}

