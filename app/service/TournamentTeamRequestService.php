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
        $conn = Database::getConnection();

        // 0. Check tournament approval status by Admin
        $stmtApp = $conn->prepare("SELECT approval_status FROM tournaments WHERE tournament_id = ?");
        $stmtApp->execute([$tournamentId]);
        $appStatus = $stmtApp->fetchColumn();

        if (strtoupper((string)$appStatus) !== 'APPROVED') {
            return [
                "success" => false,
                "message" => "Tournament is pending admin approval. Requests and invitations cannot be processed until the tournament is approved by an admin."
            ];
        }

        // 0b. Check team user account approval status from users table
        $stmtStatus = $conn->prepare("SELECT status FROM users WHERE user_id = ?");
        $stmtStatus->execute([$teamUserId]);
        $userStatus = $stmtStatus->fetchColumn();

        if (strtoupper((string)$userStatus) !== 'APPROVED') {
            return [
                "success" => false,
                "message" => "Your team account registration is currently pending admin approval. You cannot apply for tournaments until an admin approves your account."
            ];
        }

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
            // Trigger: Send notification to Organizer on new Team Join Request
            try {
                require_once __DIR__ . "/NotificationService.php";
                $notifService = new NotificationService();
                $tStmt = $conn->prepare("SELECT title, organizer_id FROM tournaments WHERE tournament_id = ?");
                $tStmt->execute([$tournamentId]);
                $tRow = $tStmt->fetch(PDO::FETCH_ASSOC);
                $title = $tRow['title'] ?? 'Tournament';
                $orgId = (int)($tRow['organizer_id'] ?? 0);

                $teamNameStmt = $conn->prepare("SELECT COALESCE(t.team_name, u.email) FROM users u LEFT JOIN teams t ON u.user_id = t.user_id WHERE u.user_id = ?");
                $teamNameStmt->execute([$teamUserId]);
                $teamName = $teamNameStmt->fetchColumn() ?: 'Team';

                if ($orgId > 0) {
                    $notifService->sendToUser(
                        $orgId,
                        'New Team Entry Request 👥',
                        "Team '{$teamName}' submitted a join request for your tournament '{$title}'.",
                        'TOURNAMENT'
                    );
                }
            } catch (Exception $e) {}

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
        $conn = Database::getConnection();

        // 0. Check tournament approval status by Admin
        $stmtApp = $conn->prepare("SELECT approval_status FROM tournaments WHERE tournament_id = ?");
        $stmtApp->execute([$tournamentId]);
        $appStatus = $stmtApp->fetchColumn();

        if (strtoupper((string)$appStatus) !== 'APPROVED') {
            return [
                "success" => false,
                "message" => "Tournament is pending admin approval. Requests and invitations cannot be processed until the tournament is approved by an admin."
            ];
        }

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
        $conn = Database::getConnection();

        // 1. Fetch tournament details and check finalization status
        $stmtT = $conn->prepare("SELECT title, organizer_id, status, is_finalized, is_draw_finalized FROM tournaments WHERE tournament_id = ?");
        $stmtT->execute([$tournamentId]);
        $tRow = $stmtT->fetch(PDO::FETCH_ASSOC);

        if (!$tRow) {
            return ["success" => false, "message" => "Tournament not found."];
        }

        $isFinalized = (int)($tRow['is_finalized'] ?? 0) === 1 || 
                       (int)($tRow['is_draw_finalized'] ?? 0) === 1 || 
                       in_array(strtoupper((string)($tRow['status'] ?? '')), ['FINALIZED', 'COMPLETED', 'FINISHED']);

        if ($isFinalized) {
            return [
                "success" => false,
                "message" => "Cannot leave: The organizer has finalized the tournament. Participant list is locked."
            ];
        }

        $existing = $this->repository->findByKeys($tournamentId, $teamUserId);
        if (!$existing) {
            return [
                "success" => false,
                "message" => "You are not registered in this tournament."
            ];
        }

        if (strtoupper($existing['status']) !== 'APPROVED' && strtoupper($existing['status']) !== 'ACCEPTED') {
            return [
                "success" => false,
                "message" => "You can only leave a tournament if your registration was approved."
            ];
        }

        // Fetch team name for notification
        $stmtTeamName = $conn->prepare("SELECT COALESCE(t.team_name, u.email, 'Team') AS team_name FROM users u LEFT JOIN teams t ON u.user_id = t.user_id WHERE u.user_id = ?");
        $stmtTeamName->execute([$teamUserId]);
        $teamName = $stmtTeamName->fetchColumn() ?: 'Team';

        // Delete team request record
        $deleted = $this->repository->deleteRequest($tournamentId, $teamUserId);
        if ($deleted) {
            // Trigger Notification to Organizer
            try {
                require_once __DIR__ . "/NotificationService.php";
                $notif = new NotificationService();
                $orgId = (int)($tRow['organizer_id'] ?? 0);
                $tTitle = $tRow['title'] ?? 'Tournament';
                if ($orgId > 0) {
                    $notif->sendToUser(
                        $orgId,
                        "Team Withdrew from Tournament 🚪",
                        "Team '{$teamName}' has withdrawn from your tournament '{$tTitle}'.",
                        "TOURNAMENT"
                    );
                }
            } catch (Exception $e) {
                error_log("Failed to send team leave notification: " . $e->getMessage());
            }

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
            // Send notification to Team User on Application Approval
            try {
                require_once __DIR__ . "/NotificationService.php";
                $notifService = new NotificationService();
                $tStmt = $conn->prepare("SELECT title FROM tournaments WHERE tournament_id = ?");
                $tStmt->execute([$tournamentId]);
                $title = $tStmt->fetchColumn() ?: 'Tournament';

                $notifService->sendToUser(
                    $teamUserId,
                    'Application Approved! 🎉',
                    "Your team application for tournament '{$title}' has been APPROVED by the organizer!",
                    'TOURNAMENT'
                );
            } catch (Exception $e) {}

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
            // Send notification to Team User on Application Rejection
            try {
                require_once __DIR__ . "/NotificationService.php";
                $notifService = new NotificationService();
                $conn = Database::getConnection();
                $tStmt = $conn->prepare("SELECT title FROM tournaments WHERE tournament_id = ?");
                $tStmt->execute([$tournamentId]);
                $title = $tStmt->fetchColumn() ?: 'Tournament';

                $notifService->sendToUser(
                    $teamUserId,
                    'Application Declined',
                    "Your team application for tournament '{$title}' was declined by the organizer.",
                    'TOURNAMENT'
                );
            } catch (Exception $e) {}

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
