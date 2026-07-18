<?php

require_once __DIR__ . "/../../config/Database.php";

class TournamentTeamRequestRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function save(int $tournamentId, int $teamUserId): bool
    {
        $sql = "INSERT INTO tournament_team_requests 
                (tournament_id, team_user_id, request_date, status)
                VALUES 
                (:tournament_id, :team_user_id, NOW(), 'PENDING')";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":tournament_id", $tournamentId, PDO::PARAM_INT);
        $statement->bindValue(":team_user_id", $teamUserId, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function findByTeamId(int $teamUserId): array
    {
        $sql = "SELECT r.tournament_id, r.team_user_id, r.request_date, r.status,
                       t.title AS tournament_title, t.location, t.tournament_held_date, t.status AS tournament_status
                FROM tournament_team_requests r
                JOIN tournaments t ON r.tournament_id = t.tournament_id
                WHERE r.team_user_id = :team_user_id";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":team_user_id", $teamUserId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByKeys(int $tournamentId, int $teamUserId): ?array
    {
        $sql = "SELECT r.tournament_id, r.team_user_id, r.request_date, r.status,
                       t.status AS tournament_status
                FROM tournament_team_requests r
                JOIN tournaments t ON r.tournament_id = t.tournament_id
                WHERE r.tournament_id = :tournament_id AND r.team_user_id = :team_user_id";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":tournament_id", $tournamentId, PDO::PARAM_INT);
        $statement->bindValue(":team_user_id", $teamUserId, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : null;
    }

    public function updateStatus(int $tournamentId, int $teamUserId, string $status): bool
    {
        $sql = "UPDATE tournament_team_requests 
                SET status = :status 
                WHERE tournament_id = :tournament_id AND team_user_id = :team_user_id";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":status", $status);
        $statement->bindValue(":tournament_id", $tournamentId, PDO::PARAM_INT);
        $statement->bindValue(":team_user_id", $teamUserId, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function deleteRequest(int $tournamentId, int $teamUserId): bool
    {
        $sql = "DELETE FROM tournament_team_requests 
                WHERE tournament_id = :tournament_id AND team_user_id = :team_user_id";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":tournament_id", $tournamentId, PDO::PARAM_INT);
        $statement->bindValue(":team_user_id", $teamUserId, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function findByOrganizerId(int $organizerId): array
    {
        $sql = "SELECT r.tournament_id, r.team_user_id, r.request_date, r.status,
                       t.title AS tournament_title,
                       tm.team_name, tm.contact_number, tm.rating, tm.district
                FROM tournament_team_requests r
                JOIN tournaments t ON r.tournament_id = t.tournament_id
                JOIN teams tm ON r.team_user_id = tm.user_id
                WHERE t.organizer_id = :organizer_id
                ORDER BY r.request_date DESC";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":organizer_id", $organizerId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
