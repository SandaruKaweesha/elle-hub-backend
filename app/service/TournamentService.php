<?php
require_once __DIR__ . "/../model/Tournament.php";
require_once __DIR__ . "/../repository/TournamentRepository.php";
require_once __DIR__ . "/../../config/Database.php";

class TournamentService{
    private $tournamentRepository;
    public function __construct(){
        $this->tournamentRepository = new TournamentRepository();
    }

    /**
     * Create a tournament. Sets default status to PENDING and saves.
     */
    public function createTournament(Tournament $tournament): array{
        // Default status: PENDING (requires admin approval)
        $tournament->setApprovalStatus("PENDING");


        try{
            Database::beginTransaction();

            $tournamentId = $this->tournamentRepository->save($tournament);
            $tournament->setTournamentId($tournamentId);

            Database::commit();

            return [
                "success" => true,
                "message" => "Tournament created successfully.",
                "data" => ["tournamentId" => $tournamentId]
            ];
        } catch (Exception $e) {
            Database::rollback();
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve tournaments that match status = PENDING
     */
    public function getPendingTournaments(): array
    {
        $rows = $this->tournamentRepository->findByStatus("PENDING");

        if (empty($rows)) {
            return [
                "success" => true,
                "message" => "No pending tournaments found.",
                "data" => []
            ];
        }

        return [
            "success" => true,
            "message" => "Pending tournaments retrieved successfully.",
            "data" => $rows
        ];
    }

    /**
     * Retrieve approved tournaments, optionally filtered by search text.
     */
    public function getApprovedTournaments(string $search): array
    {
        $rows = $this->tournamentRepository->findApprovedTournaments($search);

        return [
            "success" => true,
            "message" => "Approved tournaments retrieved successfully.",
            "data" => $rows
        ];
    }

//    Update the Status
    public function updateTournamentStatus(
        int $tournamentId,
        string $status
    ): array
    {
        $allowedStatus = [
            "UPCOMING",
            "ONGOING",
            "COMPLETED",
            "CANCELLED"
        ];

        if (!in_array($status, $allowedStatus)) {

            return [
                "success" => false,
                "message" => "Invalid tournament status."
            ];
        }

        $tournament = $this->tournamentRepository->findById($tournamentId);

        if (!$tournament) {

            return [
                "success" => false,
                "message" => "Tournament not found."
            ];
        }

        if ($tournament["status"] == $status) {

            return [
                "success" => false,
                "message" => "Tournament is already in this status."
            ];
        }

        $updated = $this->tournamentRepository
            ->updateStatus($tournamentId, $status);

        if (!$updated) {

            return [
                "success" => false,
                "message" => "Failed to update tournament status."
            ];
        }

        return [
            "success" => true,
            "message" => "Tournament status updated successfully."
        ];
    }


//    Update the Approval Status by the admin
    public function updateApprovalStatus(
        int $tournamentId,
        string $approvalStatus,
        int $adminId
    ): array
    {
        $allowedStatuses = [
            "git ",
            "REJECTED"
        ];

        if (!in_array($approvalStatus, $allowedStatuses, true)) {
            return [
                "success" => false,
                "message" => "Invalid approval status."
            ];
        }

        $tournament = $this->tournamentRepository
            ->findById($tournamentId);

        if ($tournament === null) {
            return [
                "success" => false,
                "message" => "Tournament not found."
            ];
        }

        if ($tournament["approval_status"] !== "PENDING") {
            return [
                "success" => false,
                "message" => "Only pending tournaments can be approved or rejected."
            ];
        }

        $updated = $this->tournamentRepository
            ->updateApprovalStatus(
                $tournamentId,
                $approvalStatus,
                $adminId
            );

        if (!$updated) {
            return [
                "success" => false,
                "message" => "Failed to update tournament approval status."
            ];
        }

        return [
            "success" => true,
            "message" => "Tournament approval status updated successfully."
        ];
    }


//    Filtering by the Status
    public function filterTournamentsByStatus(string $status): array
    {
        $allowedStatuses = [
            "UPCOMING",
            "ONGOING",
            "COMPLETED",
            "CANCELLED"
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            return [
                "success" => false,
                "message" => "Invalid tournament status."
            ];
        }

        $tournaments = $this->tournamentRepository
            ->filterByStatus($status);

        if (empty($tournaments)) {
            return [
                "success" => true,
                "message" => "No tournaments found for this status.",
                "data" => []
            ];
        }

        return [
            "success" => true,
            "message" => "Tournaments retrieved successfully.",
            "data" => $tournaments
        ];
    }

    public function getTournamentById(int $tournamentId): array
    {
        $tournament = $this->tournamentRepository
            ->findById($tournamentId);

        if ($tournament === null) {
            return [
                "success" => false,
                "message" => "Tournament not found."
            ];
        }

        return [
            "success" => true,
            "message" => "Tournament retrieved successfully.",
            "data" => $tournament
        ];
    }


// Update the tournament details
    public function updateTournament(
        int $tournamentId,
        object $request
    ): array
    {
        $tournament = $this->tournamentRepository
            ->findById($tournamentId);

        if ($tournament === null) {
            return [
                "success" => false,
                "message" => "Tournament not found."
            ];
        }

        $updated = $this->tournamentRepository->update(
            $tournamentId,
            $request
        );

        if (!$updated) {
            return [
                "success" => false,
                "message" => "Tournament details were not updated."
            ];
        }

        return [
            "success" => true,
            "message" => "Tournament updated successfully."
        ];
    }

//   Get all tournaments for a specific organizer
    public function getOrganizerTournaments(int $organizerId): array
    {
        $tournaments = $this->tournamentRepository
            ->findByOrganizerId($organizerId);

        if (empty($tournaments)) {
            return [
                "success" => true,
                "message" => "No tournaments found for this organizer.",
                "data" => []
            ];
        }

        return [
            "success" => true,
            "message" => "Organizer tournaments retrieved successfully.",
            "data" => $tournaments
        ];
    }
}

