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
            tournament_held_date,
            maximum_team_limit,
            maximum_referee_limit,
            rules,
            prize_details,
            approval_status,
            created_at
        ) VALUES (
            :organizer_id,
            :title,
            :description,
            :location,
            :start_date,
            :end_date,
            :tournament_held_date,
            :maximum_team_limit,
            :maximum_referee_limit,
            :rules,
            :prize_details,
            :approval_status,
            NOW()
        )";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(":organizer_id", $tournament->getOrganizerId(), PDO::PARAM_INT);
        $statement->bindValue(":title", $tournament->getTitle());
        $statement->bindValue(":description", $tournament->getDescription());
        $statement->bindValue(":location", $tournament->getLocation());
        $statement->bindValue(":start_date", $tournament->getStartDate());
        $statement->bindValue(":end_date", $tournament->getEndDate());
        $statement->bindValue(":tournament_held_date", $tournament->getTournamentHeldDate());
        $statement->bindValue(":maximum_team_limit", $tournament->getMaximumTeamLimit(), PDO::PARAM_INT);
        $statement->bindValue(":maximum_referee_limit", $tournament->getMaximumRefereeLimit() ?? 2, PDO::PARAM_INT);
        $statement->bindValue(":rules", $tournament->getRules());
        $statement->bindValue(":prize_details", $tournament->getPrizeDetails());
        $statement->bindValue(":approval_status", $tournament->getApprovalStatus());

        $statement->execute();

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Find tournaments by status.
     * Reusable for PENDING, APPROVED, REJECTED, etc.
     */
    public function findByStatus(string $approval_status): array
    {
        $sql = "SELECT * FROM tournaments WHERE approval_status = :approval_status";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(
            ":approval_status",
            $approval_status
        );

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find approved tournaments, optionally filtered by search text.
     * Uses the existing `title` column from the current project codebase.
     */
    public function findApprovedTournaments(string $search): array
    {
        if ($search === "") {
            $sql = "SELECT * FROM tournaments WHERE approval_status = 'APPROVED'";
            $statement = $this->connection->prepare($sql);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        $sql = "SELECT * FROM tournaments WHERE approval_status = 'APPROVED' AND title LIKE :search";
        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":search", "%" . $search . "%");
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

//  Find By the ID
    public function findById(int $tournamentId): ?array
    {
        $sql = "SELECT * FROM tournaments WHERE tournament_id = :tournament_id";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(
            ":tournament_id",
            $tournamentId,
            PDO::PARAM_INT
        );

        $statement->execute();

        $tournament = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$tournament) {
            return null;
        }

        return $tournament;
    }


//    Update the Status
    public function updateStatus(
        int $tournamentId,
        string $status
    ): bool
    {
        $sql = "
        UPDATE tournaments
        SET status = :status
        WHERE tournament_id = :tournament_id
    ";

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



//    Update the Approval Status by the Admin
    public function updateApprovalStatus(
        int $tournamentId,
        string $approvalStatus,
        int $adminId
    ): bool
    {
        $sql = "UPDATE tournaments
            SET approval_status = :approval_status,
                status = 'ACTIVE',
                approved_by = :approved_by,
                approved_date = NOW(),
                start_date = NOW()
            WHERE tournament_id = :tournament_id";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(
            ":approval_status",
            $approvalStatus
        );

        $statement->bindValue(
            ":approved_by",
            $adminId,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ":tournament_id",
            $tournamentId,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->rowCount() > 0;
    }

// Filtering tournaments by lifecycle status
    public function filterByStatus(string $status): array
    {
        $sql = "SELECT * FROM tournaments WHERE status = :status";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(
            ":status",
            $status
        );

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

//    Update the tournament
    public function update(
        int $tournamentId,
        object $request
    ): bool
    {
        $sql = "UPDATE tournaments
            SET title = :title,
                description = :description,
                location = :location,
                start_date = :start_date,
                end_date = :end_date,
                tournament_held_date = :tournament_held_date,
                maximum_team_limit = :maximum_team_limit,
                rules = :rules,
                prize_details = :prize_details
            WHERE tournament_id = :tournament_id";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(
            ":title",
            $request->title
        );

        $statement->bindValue(
            ":description",
            $request->description
        );

        $statement->bindValue(
            ":location",
            $request->location
        );

        $statement->bindValue(
            ":start_date",
            $request->startDate
        );

        $statement->bindValue(
            ":end_date",
            $request->endDate
        );

        $statement->bindValue(
            ":tournament_held_date",
            $request->tournamentHeldDate ?? null
        );

        $statement->bindValue(
            ":maximum_team_limit",
            $request->maximumTeamLimit,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ":rules",
            $request->rules
        );

        $statement->bindValue(
            ":prize_details",
            $request->prizeDetails
        );

        $statement->bindValue(
            ":tournament_id",
            $tournamentId,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->rowCount() > 0;
    }

//    Find tournaments by organizer ID, ordered by creation date descending
    public function findByOrganizerId(int $organizerId): array
    {
        $sql = "SELECT *
            FROM tournaments
            WHERE organizer_id = :organizer_id
            ORDER BY created_at DESC";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(
            ":organizer_id",
            $organizerId,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll(): array
    {
        $sql = "SELECT t.*, o.organization_name AS organizer_name
                FROM tournaments t
                LEFT JOIN organizers o ON t.organizer_id = o.user_id
                ORDER BY t.created_at DESC";

        $statement = $this->connection->prepare($sql);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

