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
