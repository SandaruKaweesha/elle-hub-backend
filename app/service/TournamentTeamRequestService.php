<?php

require_once __DIR__ . "/../repository/TournamentTeamRequestRepository.php";

class TournamentTeamRequestService
{
    private TournamentTeamRequestRepository $repository;

    public function __construct()
    {
        $this->repository = new TournamentTeamRequestRepository();
    }

    public function submitRequest(int $tournamentId, int $teamUserId): array
    {
        // 1. Check if a request already exists
        $existing = $this->repository->findByKeys($tournamentId, $teamUserId);
        if ($existing) {
            return [
                "success" => false,
                "message" => "You have already submitted a join request for this tournament."
            ];
        }

        // 2. Save the request
        $saved = $this->repository->save($tournamentId, $teamUserId);
        if ($saved) {
            return [
                "success" => true,
                "message" => "Join request submitted successfully."
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to submit join request. Please try again later."
        ];
    }

    public function sendOrganizerInvitation(int $tournamentId, int $teamUserId): array
    {
        // 1. Check if a request/invitation already exists
        $existing = $this->repository->findByKeys($tournamentId, $teamUserId);
        if ($existing) {
            return [
                "success" => false,
                "message" => "An entry request or invitation already exists for this team in this tournament."
            ];
        }

        // 2. Check maximum team limit capacity for the tournament
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT maximum_team_limit, status FROM tournaments WHERE tournament_id = ?");
        $stmt->execute([$tournamentId]);
        $tournamentRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tournamentRow) {
            return [
                "success" => false,
                "message" => "Tournament not found."
            ];
        }

        $maxLimit = (int)($tournamentRow['maximum_team_limit'] ?? 0);
        if ($maxLimit > 0) {
            $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM tournament_team_requests WHERE tournament_id = ? AND status = 'APPROVED'");
            $stmtCount->execute([$tournamentId]);
            $countRow = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $currentApprovedCount = (int)($countRow['total'] ?? 0);

            if ($currentApprovedCount >= $maxLimit) {
                return [
                    "success" => false,
                    "message" => "Cannot send invitation: Maximum team capacity limit of {$maxLimit} teams reached for this tournament."
                ];
            }
        }

        // 3. Save invitation with initiated_by = 'ORGANIZER'
        $saved = $this->repository->save($tournamentId, $teamUserId, 'ORGANIZER');
        if ($saved) {
            return [
                "success" => true,
                "message" => "Tournament invitation sent to team successfully."
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to send tournament invitation."
        ];
    }

    public function getTeamRequests(int $teamUserId): array
    {
        $requests = $this->repository->findByTeamId($teamUserId);
        return [
            "success" => true,
            "data" => $requests
        ];
    }

    public function cancelRequest(int $tournamentId, int $teamUserId): array
    {
        $existing = $this->repository->findByKeys($tournamentId, $teamUserId);
        if (!$existing) {
            return [
                "success" => false,
                "message" => "No join request found to cancel."
            ];
        }

        if (strtoupper($existing['status']) !== 'PENDING') {
            return [
                "success" => false,
                "message" => "Only pending requests can be cancelled."
            ];
        }

        $deleted = $this->repository->deleteRequest($tournamentId, $teamUserId);
        if ($deleted) {
            return [
                "success" => true,
                "message" => "Join request cancelled successfully."
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to cancel join request."
        ];
    }

    public function leaveTournament(int $tournamentId, int $teamUserId): array
    {
        $existing = $this->repository->findByKeys($tournamentId, $teamUserId);
        if (!$existing) {
            return [
                "success" => false,
                "message" => "You are not registered in this tournament."
            ];
        }

        if (strtoupper($existing['status']) !== 'APPROVED') {
            return [
                "success" => false,
                "message" => "You can only leave a tournament if your registration was approved."
            ];
        }

        // Check if tournament is finalized (status is not ACTIVE)
        $tournamentStatus = strtoupper($existing['tournament_status'] ?? 'ACTIVE');
        if ($tournamentStatus !== 'ACTIVE') {
            return [
                "success" => false,
                "message" => "Cannot leave: The tournament setup has been finalized by the organizer."
            ];
        }

        $deleted = $this->repository->deleteRequest($tournamentId, $teamUserId);
        if ($deleted) {
            return [
                "success" => true,
                "message" => "You have left the tournament successfully."
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to leave the tournament."
        ];
    }

    public function getOrganizerTeamRequests(int $organizerId): array
    {
        try {
            $data = $this->repository->findByOrganizerId($organizerId);
            return [
                "success" => true,
                "data" => $data
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ];
        }
    }

    public function getTournamentTeamRequests(int $tournamentId): array
    {
        try {
            $data = $this->repository->findByTournamentId($tournamentId);
            return [
                "success" => true,
                "data" => $data
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ];
        }
    }

    public function approveRequest(int $tournamentId, int $teamUserId): array
    {
        $existing = $this->repository->findByKeys($tournamentId, $teamUserId);
        if (!$existing) {
            return [
                "success" => false,
                "message" => "Request not found."
            ];
        }

        if ($existing['status'] !== 'PENDING') {
            return [
                "success" => false,
                "message" => "Only pending requests can be approved."
            ];
        }

        // Check maximum team limit capacity
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT maximum_team_limit FROM tournaments WHERE tournament_id = ?");
        $stmt->execute([$tournamentId]);
        $tournamentRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $maxLimit = (int)($tournamentRow['maximum_team_limit'] ?? 0);

        if ($maxLimit > 0) {
            $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM tournament_team_requests WHERE tournament_id = ? AND status = 'APPROVED'");
            $stmtCount->execute([$tournamentId]);
            $countRow = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $currentApprovedCount = (int)($countRow['total'] ?? 0);

            if ($currentApprovedCount >= $maxLimit) {
                return [
                    "success" => false,
                    "message" => "Cannot approve team: Maximum capacity of {$maxLimit} teams has been reached for this tournament."
                ];
            }
        }

        $updated = $this->repository->updateStatus($tournamentId, $teamUserId, 'APPROVED');
        if ($updated) {
            return [
                "success" => true,
                "message" => "Team request approved successfully."
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to approve team request."
        ];
    }

    public function rejectRequest(int $tournamentId, int $teamUserId): array
    {
        $existing = $this->repository->findByKeys($tournamentId, $teamUserId);
        if (!$existing) {
            return [
                "success" => false,
                "message" => "Request not found."
            ];
        }

        if ($existing['status'] !== 'PENDING') {
            return [
                "success" => false,
                "message" => "Only pending requests can be rejected."
            ];
        }

        $updated = $this->repository->updateStatus($tournamentId, $teamUserId, 'REJECTED');
        if ($updated) {
            return [
                "success" => true,
                "message" => "Team request rejected successfully."
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to reject team request."
        ];
    }
}
