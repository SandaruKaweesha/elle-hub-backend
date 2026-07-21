<?php

require_once __DIR__ . "/../../config/Database.php";

class TournamentTeamRequestRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function save(int $tournamentId, int $teamUserId, string $initiatedBy = 'TEAM'): bool
    {
        $sql = "INSERT INTO tournament_team_requests 
                (tournament_id, team_user_id, request_date, status, initiated_by)
                VALUES 
                (:tournament_id, :team_user_id, NOW(), 'PENDING', :initiated_by)";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":tournament_id", $tournamentId, PDO::PARAM_INT);
        $statement->bindValue(":team_user_id", $teamUserId, PDO::PARAM_INT);
        $statement->bindValue(":initiated_by", $initiatedBy);

        return $statement->execute();
    }

    public function findByTeamId(int $teamUserId): array
    {
        $sql = "SELECT r.tournament_id, r.team_user_id, r.request_date, r.status, r.initiated_by,
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
        $sql = "SELECT r.tournament_id, r.team_user_id, r.request_date, r.status, r.initiated_by,
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
        $sql = "SELECT r.tournament_id, r.team_user_id, r.request_date, r.status, r.initiated_by,
                       t.title AS tournament_title,
                       COALESCE(tm.team_name, u.display_name, u.full_name, 'Team') AS team_name,
                       COALESCE(tm.contact_number, u.phone, 'N/A') AS contact_number,
                       tm.rating,
                       COALESCE(tm.district, u.district, 'N/A') AS district,
                       (SELECT COUNT(*) FROM players WHERE team_user_id = r.team_user_id) AS squad_size
                FROM tournament_team_requests r
                JOIN tournaments t ON r.tournament_id = t.tournament_id
                LEFT JOIN users u ON r.team_user_id = u.user_id
                LEFT JOIN teams tm ON r.team_user_id = tm.user_id
                WHERE t.organizer_id = :organizer_id
                ORDER BY r.request_date DESC";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":organizer_id", $organizerId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByTournamentId(int $tournamentId): array
    {
        $sql = "SELECT r.tournament_id, r.team_user_id, r.request_date, r.status, r.initiated_by,
                       t.title AS tournament_title,
                       COALESCE(tm.team_name, u.display_name, u.full_name, 'Team') AS team_name,
                       COALESCE(tm.contact_number, u.phone, 'N/A') AS contact_number,
                       tm.rating,
                       COALESCE(tm.district, u.district, 'N/A') AS district,
                       (SELECT COUNT(*) FROM players WHERE team_user_id = r.team_user_id) AS squad_size
                FROM tournament_team_requests r
                JOIN tournaments t ON r.tournament_id = t.tournament_id
                LEFT JOIN users u ON r.team_user_id = u.user_id
                LEFT JOIN teams tm ON r.team_user_id = tm.user_id
                WHERE r.tournament_id = :tournament_id
                ORDER BY r.request_date DESC";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":tournament_id", $tournamentId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
