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

            // Trigger #5: Send notification to Admins for new tournament creation
            try {
                require_once __DIR__ . "/NotificationService.php";
                $notifService = new NotificationService();
                $notifService->sendToRole(
                    'admin',
                    'New Tournament Approval Request',
                    "A new tournament '{$tournament->getTitle()}' has been created and requires admin review & approval.",
                    'TOURNAMENT'
                );
            } catch (Exception $e) {}

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
            "ACTIVE",
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

        // Recalculate referee rating if status updated to COMPLETED
        if (strtoupper($status) === 'COMPLETED') {
            $this->recalculateRefereesRatingForTournament($tournamentId);
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
            "APPROVED",
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

        // Trigger: Send notification to Organizer and broadcast to All Users on Tournament Approval/Rejection
        try {
            require_once __DIR__ . "/NotificationService.php";
            $notifService = new NotificationService();
            $title = $tournament['title'] ?? 'Elle Championship';
            $location = $tournament['location'] ?? 'Central Grounds';
            $organizerUserId = (int) ($tournament['organizer_id'] ?? $tournament['organizer_user_id'] ?? $tournament['user_id'] ?? 0);

            if (strtoupper($approvalStatus) === 'APPROVED') {
                if ($organizerUserId > 0) {
                    $notifService->sendToUser(
                        $organizerUserId,
                        'Tournament Approved! 🏆',
                        "Your tournament '{$title}' has been officially approved by Admin and is now live for registrations.",
                        'TOURNAMENT'
                    );
                }

                $notifService->sendToAll(
                    "New Championship Announced! 🏆",
                    "The '{$title}' tournament at {$location} has been officially approved and is now open for registration!",
                    "TOURNAMENT"
                );
            } elseif (strtoupper($approvalStatus) === 'REJECTED') {
                if ($organizerUserId > 0) {
                    $notifService->sendToUser(
                        $organizerUserId,
                        'Tournament Review Status Update',
                        "Your tournament '{$title}' submission review status was set to Rejected by Admin.",
                        'TOURNAMENT'
                    );
                }
            }
        } catch (Exception $e) {}

        return [
            "success" => true,
            "message" => "Tournament approval status updated successfully."
        ];

    }


//    Filtering by the Status
    public function filterTournamentsByStatus(string $status): array
    {
        $allowedStatuses = [
            "ACTIVE",
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

    public function getOrganizerHistory(int $organizerId): array
    {
        try {
            $conn = Database::getConnection();
            $sql = "SELECT t.*, 
                           (SELECT COUNT(*) FROM tournament_team_requests WHERE tournament_id = t.tournament_id AND status = 'APPROVED') AS participating_teams_count,
                           (SELECT COUNT(*) FROM tournament_referee_requests WHERE tournament_id = t.tournament_id AND status IN ('ACCEPTED', 'APPROVED')) AS assigned_referees_count,
                           (SELECT COUNT(*) FROM tournament_sponsor_requests WHERE tournament_id = t.tournament_id AND status IN ('ACCEPTED', 'APPROVED')) AS sponsors_count
                    FROM tournaments t
                    WHERE t.organizer_id = ? AND UPPER(t.status) = 'COMPLETED'
                    ORDER BY COALESCE(t.tournament_held_date, t.end_date, t.start_date, t.created_at) DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$organizerId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ["success" => true, "data" => $history];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }


    public function getAllTournaments(): array
    {
        try {
            $data = $this->tournamentRepository->findAll();
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

    public function getTournamentAssignments(int $tournamentId): array
    {
        try {
            $teamUserIds = [];
            $stmt = Database::getConnection()->prepare("SELECT team_user_id FROM tournament_team_requests WHERE tournament_id = ? AND status = 'APPROVED'");
            $stmt->execute([$tournamentId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $teamUserIds[] = $row['team_user_id'];
            }

            $refereeUserIds = [];
            $stmt = Database::getConnection()->prepare("SELECT referee_user_id FROM tournament_referee_requests WHERE tournament_id = ? AND status = 'APPROVED'");
            $stmt->execute([$tournamentId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $refereeUserIds[] = $row['referee_user_id'];
            }

            $sponsorUserIds = [];
            $stmt = Database::getConnection()->prepare("SELECT sponsor_user_id FROM tournament_sponsor_requests WHERE tournament_id = ? AND status = 'APPROVED'");
            $stmt->execute([$tournamentId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $sponsorUserIds[] = $row['sponsor_user_id'];
            }

            $playgroundUserId = null;
            $stmt = Database::getConnection()->prepare("SELECT playground_user_id FROM tournament_playground_requests WHERE tournament_id = ? AND status = 'APPROVED' LIMIT 1");
            $stmt->execute([$tournamentId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $playgroundUserId = $row['playground_user_id'];
            }

            return [
                "success" => true,
                "data" => [
                    "teamUserIds" => $teamUserIds,
                    "refereeUserIds" => $refereeUserIds,
                    "sponsorUserIds" => $sponsorUserIds,
                    "playgroundUserId" => $playgroundUserId
                ]
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function saveTournamentAssignments(int $tournamentId, object $request): array
    {
        try {
            Database::beginTransaction();
            $conn = Database::getConnection();

            // Clear old assignments (overwrite)
            $conn->prepare("DELETE FROM tournament_team_requests WHERE tournament_id = ? AND status = 'APPROVED'")->execute([$tournamentId]);
            $conn->prepare("DELETE FROM tournament_referee_requests WHERE tournament_id = ? AND status = 'APPROVED'")->execute([$tournamentId]);

            if (isset($request->teamUserIds) && is_array($request->teamUserIds)) {
                // Ensure initiated_by is handled if schema requires it, checking ER Diagram: initiated_by ENUM
                $stmt = $conn->prepare("INSERT INTO tournament_team_requests (tournament_id, team_user_id, status, request_date, initiated_by) VALUES (?, ?, 'APPROVED', NOW(), 'ORGANIZER')");
                foreach ($request->teamUserIds as $tid) {
                    $stmt->execute([$tournamentId, $tid]);
                }
            }

            if (isset($request->refereeUserIds) && is_array($request->refereeUserIds)) {
                $stmtRefCheck = $conn->prepare("SELECT user_id FROM referees WHERE user_id = ?");
                $stmtInsRef = $conn->prepare("INSERT INTO referees (user_id) VALUES (?)");
                $stmt = $conn->prepare("INSERT INTO tournament_referee_requests (tournament_id, referee_user_id, status, request_date) VALUES (?, ?, 'APPROVED', NOW())");
                foreach ($request->refereeUserIds as $rid) {
                    $stmtRefCheck->execute([$rid]);
                    if (!$stmtRefCheck->fetch()) {
                        $stmtInsRef->execute([$rid]);
                    }
                    $stmt->execute([$tournamentId, $rid]);
                }
            }
            Database::commit();
            return ["success" => true, "message" => "Assignments updated successfully"];
        } catch (Exception $e) {
            Database::rollback();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getPlaygroundRequests(int $tournamentId): array
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("
                SELECT p.user_id, p.playground_name, p.located_district, p.location, p.area, p.area AS capacity,
                       tpr.status, tpr.initiated_by
                FROM playgrounds p
                LEFT JOIN tournament_playground_requests tpr 
                       ON p.user_id = tpr.playground_user_id AND tpr.tournament_id = ?
            ");
            $stmt->execute([$tournamentId]);
            $playgrounds = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ["success" => true, "data" => $playgrounds];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function sendPlaygroundRequest(int $tournamentId, int $playgroundUserId, string $initiatedBy): array
    {
        try {
            $conn = Database::getConnection();

            // Ensure playground record exists in playgrounds table to satisfy FK constraint fk_playground_request_playground
            $stmtPgCheck = $conn->prepare("SELECT user_id FROM playgrounds WHERE user_id = ?");
            $stmtPgCheck->execute([$playgroundUserId]);
            if (!$stmtPgCheck->fetch()) {
                $stmtU = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
                $stmtU->execute([$playgroundUserId]);
                $uRow = $stmtU->fetch(PDO::FETCH_ASSOC);
                $pgName = $uRow['email'] ?? 'Playground Venue';
                $conn->prepare("INSERT INTO playgrounds (user_id, playground_name, located_district, location, address, contact_number, area) VALUES (?, ?, 'Sri Lanka', 'Sri Lanka', 'Sri Lanka', 'N/A', '500 Sq. Ft')")->execute([$playgroundUserId, $pgName]);
            }



            // Check if playground venue is UNAVAILABLE on the tournament date
            $stmtT = $conn->prepare("SELECT tournament_held_date, start_date FROM tournaments WHERE tournament_id = ?");
            $stmtT->execute([$tournamentId]);
            $tRow = $stmtT->fetch(PDO::FETCH_ASSOC);
            $tDate = !empty($tRow['tournament_held_date']) ? $tRow['tournament_held_date'] : (!empty($tRow['start_date']) ? $tRow['start_date'] : null);

            if ($tDate) {
                $stmtAvail = $conn->prepare("
                    SELECT status FROM playground_availability 
                    WHERE playground_user_id = ? AND available_date = ? AND status = 'UNAVAILABLE'
                ");
                $stmtAvail->execute([$playgroundUserId, $tDate]);
                if ($stmtAvail->fetch()) {
                    return [
                        "success" => false,
                        "message" => "Cannot send booking request: This playground venue is marked UNAVAILABLE on the tournament date ({$tDate})."
                    ];
                }
            }

            // Check if already requested
            $stmt = $conn->prepare("SELECT status FROM tournament_playground_requests WHERE tournament_id = ? AND playground_user_id = ?");
            $stmt->execute([$tournamentId, $playgroundUserId]);
            if ($stmt->fetch()) {
                return ["success" => false, "message" => "Request already exists"];
            }

            $stmt = $conn->prepare("INSERT INTO tournament_playground_requests (tournament_id, playground_user_id, status, initiated_by, request_date) VALUES (?, ?, 'PENDING', ?, NOW())");
            $stmt->execute([$tournamentId, $playgroundUserId, $initiatedBy]);

            return ["success" => true, "message" => "Request sent successfully"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function respondToPlaygroundRequest(int $tournamentId, int $playgroundUserId, string $status): array
    {
        try {
            $conn = Database::getConnection();
            $upperStatus = strtoupper($status);
            $dbStatus = ($upperStatus === 'APPROVED' || $upperStatus === 'ACCEPTED') ? 'ACCEPTED' : $upperStatus;

            $stmt = $conn->prepare("UPDATE tournament_playground_requests SET status = ? WHERE tournament_id = ? AND playground_user_id = ?");
            $stmt->execute([$dbStatus, $tournamentId, $playgroundUserId]);

            if ($dbStatus === 'ACCEPTED' || $dbStatus === 'APPROVED') {
                // Fetch tournament date
                $stmtT = $conn->prepare("SELECT COALESCE(tournament_held_date, start_date) AS t_date FROM tournaments WHERE tournament_id = ?");
                $stmtT->execute([$tournamentId]);
                $tRow = $stmtT->fetch(PDO::FETCH_ASSOC);
                $tDate = $tRow['t_date'] ?? null;

                if ($tDate) {
                    // Update playground_availability to UNAVAILABLE for that date
                    $stmtCheck = $conn->prepare("SELECT availability_id FROM playground_availability WHERE playground_user_id = ? AND available_date = ?");
                    $stmtCheck->execute([$playgroundUserId, $tDate]);
                    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        $stmtUpd = $conn->prepare("UPDATE playground_availability SET status = 'UNAVAILABLE' WHERE availability_id = ?");
                        $stmtUpd->execute([$existing['availability_id']]);
                    } else {
                        $stmtIns = $conn->prepare("INSERT INTO playground_availability (playground_user_id, available_date, start_time, end_time, status) VALUES (?, ?, '08:00:00', '18:00:00', 'UNAVAILABLE')");
                        $stmtIns->execute([$playgroundUserId, $tDate]);
                    }
                }
            }

            return ["success" => true, "message" => "Request updated successfully to {$dbStatus}."];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getPlaygroundIncomingRequests(int $playgroundUserId): array
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("
                SELECT tpr.request_id, tpr.tournament_id, tpr.playground_user_id, tpr.request_date, tpr.status, tpr.initiated_by,
                       t.status AS tournament_status, t.title AS tournament_title, t.location, t.start_date, t.end_date, t.tournament_held_date,
                       t.is_finalized, t.is_draw_finalized,
                       COALESCE(o.organization_name, 'Elle Sports Association') AS organizer_name,
                       COALESCE(o.contact_number, 'Available on Request') AS contact_number
                FROM tournament_playground_requests tpr
                JOIN tournaments t ON tpr.tournament_id = t.tournament_id
                LEFT JOIN organizers o ON t.organizer_id = o.user_id
                WHERE tpr.playground_user_id = ?
                ORDER BY tpr.request_date DESC
            ");
            $stmt->execute([$playgroundUserId]);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($requests as &$r) {
                $r['is_finalized'] = (int)($r['is_finalized'] ?? 0);
                $r['is_draw_finalized'] = (int)($r['is_draw_finalized'] ?? 0);
                $r['isFinalized'] = $r['is_finalized'] === 1;
                $r['isDrawFinalized'] = $r['is_draw_finalized'] === 1;

                // If tournament is finalized by organizer, auto-confirm pending playground booking
                if (($r['is_finalized'] === 1 || $r['is_draw_finalized'] === 1) && strtoupper((string)$r['status']) === 'PENDING') {
                    $r['status'] = 'ACCEPTED';
                    $conn->prepare("UPDATE tournament_playground_requests SET status = 'ACCEPTED' WHERE request_id = ?")->execute([$r['request_id']]);
                }
            }

            return ["success" => true, "data" => $requests];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function cancelPlaygroundRequest(int $tournamentId, int $playgroundUserId): array
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("DELETE FROM tournament_playground_requests WHERE tournament_id = ? AND playground_user_id = ?");
            $stmt->execute([$tournamentId, $playgroundUserId]);
            return ["success" => true, "message" => "Request cancelled successfully"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getOrganizerPlaygroundRequests(int $organizerId): array
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("
                SELECT tpr.request_id, tpr.tournament_id, tpr.playground_user_id, tpr.request_date, tpr.status, tpr.initiated_by,
                       t.title AS tournament_title,
                       COALESCE(p.playground_name, u.email, 'Playground Venue') AS display_name,
                       COALESCE(p.location, p.located_district, 'Sri Lanka') AS district,
                       COALESCE(p.contact_number, u.email, 'N/A') AS contact_number,
                       p.area AS capacity
                FROM tournament_playground_requests tpr
                JOIN tournaments t ON tpr.tournament_id = t.tournament_id
                JOIN users u ON tpr.playground_user_id = u.user_id
                LEFT JOIN playgrounds p ON tpr.playground_user_id = p.user_id
                WHERE t.organizer_id = ?
                ORDER BY tpr.request_date DESC
            ");
            $stmt->execute([$organizerId]);
            return ["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    // Sponsor Requests

    public function getSponsorRequests(int $tournamentId): array
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("
                SELECT u.user_id, COALESCE(s.company_name, 'Sponsor') AS display_name, COALESCE(s.address, 'N/A') AS district,
                       tsr.status, tsr.initiated_by
                FROM users u
                LEFT JOIN sponsors s ON u.user_id = s.user_id
                LEFT JOIN tournament_sponsor_requests tsr 
                       ON u.user_id = tsr.sponsor_user_id AND tsr.tournament_id = ?
                WHERE u.role = 'SPONSOR' AND u.status = 'APPROVED'
            ");
            $stmt->execute([$tournamentId]);
            $sponsors = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ["success" => true, "data" => $sponsors];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function sendSponsorRequest(int $tournamentId, int $sponsorUserId, string $initiatedBy): array
    {
        try {
            $conn = Database::getConnection();

            // Check tournament approval status by Admin
            $stmtApp = $conn->prepare("SELECT approval_status FROM tournaments WHERE tournament_id = ?");
            $stmtApp->execute([$tournamentId]);
            $appStatus = $stmtApp->fetchColumn();

            if (strtoupper((string)$appStatus) !== 'APPROVED') {
                return [
                    "success" => false,
                    "message" => "Tournament is pending admin approval. Requests and invitations cannot be processed until the tournament is approved by an admin."
                ];
            }

            // Ensure sponsor record exists in sponsors table to satisfy FK constraint fk_sponsor_request_sponsor
            $stmtSpCheck = $conn->prepare("SELECT user_id FROM sponsors WHERE user_id = ?");
            $stmtSpCheck->execute([$sponsorUserId]);
            if (!$stmtSpCheck->fetch()) {
                $stmtU = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
                $stmtU->execute([$sponsorUserId]);
                $uRow = $stmtU->fetch(PDO::FETCH_ASSOC);
                $spName = $uRow['email'] ?? 'Corporate Sponsor';
                $conn->prepare("INSERT INTO sponsors (user_id, company_name, contact_person, address, contact_number) VALUES (?, ?, 'Sponsor Rep', 'Sri Lanka', 'N/A')")->execute([$sponsorUserId, $spName]);
            }



            // Check if already requested
            $stmt = $conn->prepare("SELECT status FROM tournament_sponsor_requests WHERE tournament_id = ? AND sponsor_user_id = ?");
            $stmt->execute([$tournamentId, $sponsorUserId]);
            if ($stmt->fetch()) {
                return ["success" => false, "message" => "Request already exists"];
            }

            $stmt = $conn->prepare("INSERT INTO tournament_sponsor_requests (tournament_id, sponsor_user_id, status, initiated_by, request_date) VALUES (?, ?, 'PENDING', ?, NOW())");
            $stmt->execute([$tournamentId, $sponsorUserId, $initiatedBy]);

            return ["success" => true, "message" => "Request sent successfully"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function respondToSponsorRequest(int $tournamentId, int $sponsorUserId, string $status): array
    {
        try {
            $conn = Database::getConnection();
            $upperStatus = strtoupper($status);
            
            if ($upperStatus === 'CANCELLED' || $upperStatus === 'DELETE') {
                $stmt = $conn->prepare("DELETE FROM tournament_sponsor_requests WHERE tournament_id = ? AND sponsor_user_id = ?");
                $stmt->execute([$tournamentId, $sponsorUserId]);
                return ["success" => true, "message" => "Sponsorship request cancelled and removed successfully"];
            }

            $dbStatus = ($upperStatus === 'APPROVED' || $upperStatus === 'ACCEPTED') ? 'ACCEPTED' : 'REJECTED';

            $stmt = $conn->prepare("UPDATE tournament_sponsor_requests SET status = ? WHERE tournament_id = ? AND sponsor_user_id = ?");
            $stmt->execute([$dbStatus, $tournamentId, $sponsorUserId]);

            return ["success" => true, "message" => "Request updated successfully to {$dbStatus}"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }


    public function getSponsorIncomingRequests(int $sponsorUserId): array
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("
                SELECT tsr.request_id, tsr.tournament_id, tsr.sponsor_user_id, tsr.request_date, tsr.status, tsr.initiated_by,
                       t.title AS tournament_title, t.location, t.start_date, t.end_date, t.tournament_held_date, t.status AS tournament_status,
                       COALESCE(o.organization_name, 'Elle Sports Association') AS organizer_name,
                       COALESCE(o.contact_number, 'Available on Request') AS contact_number
                FROM tournament_sponsor_requests tsr
                JOIN tournaments t ON tsr.tournament_id = t.tournament_id
                LEFT JOIN organizers o ON t.organizer_id = o.user_id
                WHERE tsr.sponsor_user_id = ?
                ORDER BY tsr.request_date DESC
            ");
            $stmt->execute([$sponsorUserId]);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ["success" => true, "data" => $requests];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getOrganizerSponsorRequests(int $organizerId): array
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("
                SELECT tsr.request_id, tsr.tournament_id, tsr.sponsor_user_id, tsr.request_date, tsr.status, tsr.initiated_by,
                       t.title AS tournament_title,
                       COALESCE(s.company_name, u.email, 'Official Sponsor') AS company_name,
                       u.email
                FROM tournament_sponsor_requests tsr
                JOIN tournaments t ON tsr.tournament_id = t.tournament_id
                JOIN users u ON tsr.sponsor_user_id = u.user_id
                LEFT JOIN sponsors s ON u.user_id = s.user_id
                WHERE t.organizer_id = ?
                ORDER BY tsr.request_date DESC
            ");
            $stmt->execute([$organizerId]);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ["success" => true, "data" => $requests];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getSponsorHistory(int $sponsorUserId): array
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("
                SELECT tsr.request_id, tsr.tournament_id, tsr.sponsor_user_id, tsr.request_date, tsr.status AS request_status,
                       t.title AS tournament_title, t.location, t.tournament_held_date, t.start_date, t.end_date, t.status AS tournament_status,
                       COALESCE(o.organization_name, 'Elle Sports Association') AS organizer_name,
                       COALESCE(o.contact_number, 'Available on Request') AS contact_number
                FROM tournament_sponsor_requests tsr
                JOIN tournaments t ON tsr.tournament_id = t.tournament_id
                LEFT JOIN organizers o ON t.organizer_id = o.user_id
                WHERE tsr.sponsor_user_id = ? 
                  AND tsr.status IN ('APPROVED', 'ACCEPTED') 
                  AND UPPER(t.status) = 'COMPLETED'
                ORDER BY COALESCE(t.tournament_held_date, t.start_date, tsr.request_date) DESC
            ");
            $stmt->execute([$sponsorUserId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ["success" => true, "data" => $history];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }


    // Referee Requests
    public function getRefereeRequests(int $tournamentId): array
    {
        try {
            $sql = "SELECT r.request_id, r.tournament_id, r.referee_user_id, r.request_date, r.status, r.initiated_by,
                           COALESCE(rf.full_name, 'Referee') AS display_name, COALESCE(rf.contact_number, 'N/A') AS phone, rf.rating, rf.experience_years
                    FROM tournament_referee_requests r
                    JOIN users u ON r.referee_user_id = u.user_id
                    LEFT JOIN referees rf ON r.referee_user_id = rf.user_id
                    WHERE r.tournament_id = ?
                    ORDER BY r.request_date DESC";
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$tournamentId]);
            return ["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function sendRefereeRequest(int $tournamentId, int $refereeUserId, string $initiatedBy): array
    {
        try {
            $conn = Database::getConnection();

            // 0. Check tournament approval status by Admin
            $stmtApp = $conn->prepare("SELECT approval_status, tournament_held_date, start_date FROM tournaments WHERE tournament_id = ?");
            $stmtApp->execute([$tournamentId]);
            $tRow = $stmtApp->fetch(PDO::FETCH_ASSOC);

            if (strtoupper((string)($tRow['approval_status'] ?? '')) !== 'APPROVED') {
                return [
                    "success" => false,
                    "message" => "Tournament is pending admin approval. Requests and invitations cannot be processed until the tournament is approved by an admin."
                ];
            }

            // 0b. Check if referee is UNAVAILABLE on the tournament date
            $tDate = !empty($tRow['tournament_held_date']) ? $tRow['tournament_held_date'] : (!empty($tRow['start_date']) ? $tRow['start_date'] : null);
            if ($tDate) {
                $stmtAvail = $conn->prepare("
                    SELECT status FROM referee_availability 
                    WHERE referee_user_id = ? AND available_date = ? AND status = 'UNAVAILABLE'
                ");
                $stmtAvail->execute([$refereeUserId, $tDate]);
                if ($stmtAvail->fetch()) {
                    return [
                        "success" => false,
                        "message" => "Cannot send invitation: This referee has set themselves as UNAVAILABLE on the tournament date ({$tDate})."
                    ];
                }
            }

            // Check if referee user account is approved by Admin
            $stmtUserStatus = $conn->prepare("SELECT status FROM users WHERE user_id = ?");
            $stmtUserStatus->execute([$refereeUserId]);
            $userStatus = $stmtUserStatus->fetchColumn();

            if (strtoupper((string)$userStatus) !== 'APPROVED') {
                return [
                    "success" => false,
                    "message" => "Your referee account registration is currently pending admin approval. You cannot apply for tournaments until an admin approves your account."
                ];
            }

            // Ensure referee record exists in referees table to satisfy FK constraint fk_ref_request_referee
            $stmtRefCheck = $conn->prepare("SELECT user_id FROM referees WHERE user_id = ?");
            $stmtRefCheck->execute([$refereeUserId]);
            if (!$stmtRefCheck->fetch()) {
                $stmtU = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
                $stmtU->execute([$refereeUserId]);
                $uRow = $stmtU->fetch(PDO::FETCH_ASSOC);
                $refName = $uRow['email'] ?? 'Official Referee';
                $conn->prepare("INSERT INTO referees (user_id, full_name, experience_years, contact_number, availability_status) VALUES (?, ?, 1, 'N/A', 'AVAILABLE')")->execute([$refereeUserId, $refName]);
            }



            // Check if request already exists to prevent duplicate entry exception
            $stmtCheck = $conn->prepare("SELECT status FROM tournament_referee_requests WHERE tournament_id = ? AND referee_user_id = ?");
            $stmtCheck->execute([$tournamentId, $refereeUserId]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $statusText = strtoupper($existing['status']);
                if ($statusText === 'ACCEPTED' || $statusText === 'APPROVED') {
                    return ["success" => true, "message" => "You are already a confirmed referee for this tournament."];
                }
                return ["success" => true, "message" => "Your officiating request is already submitted and pending organizer review."];
            }

            // Check maximum referee limit
            $stmtLimit = $conn->prepare("SELECT maximum_referee_limit FROM tournaments WHERE tournament_id = ?");
            $stmtLimit->execute([$tournamentId]);
            $tRow = $stmtLimit->fetch(PDO::FETCH_ASSOC);
            $maxLimit = (int)($tRow['maximum_referee_limit'] ?? 2);

            if ($maxLimit > 0) {
                $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM tournament_referee_requests WHERE tournament_id = ? AND status IN ('ACCEPTED', 'APPROVED')");
                $stmtCount->execute([$tournamentId]);
                $cRow = $stmtCount->fetch(PDO::FETCH_ASSOC);
                if ((int)($cRow['total'] ?? 0) >= $maxLimit) {
                    return ["success" => false, "message" => "Cannot send request: Maximum limit of {$maxLimit} referees has been reached for this tournament."];
                }
            }

            $stmt = $conn->prepare("INSERT INTO tournament_referee_requests (tournament_id, referee_user_id, status, request_date, initiated_by) VALUES (?, ?, 'PENDING', NOW(), ?)");
            $stmt->execute([$tournamentId, $refereeUserId, $initiatedBy]);
            return ["success" => true, "message" => "Referee request submitted successfully"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getOrganizerRefereeRequests(int $organizerId): array
    {
        try {
            $sql = "SELECT r.request_id, r.tournament_id, r.referee_user_id, r.request_date, r.status, r.initiated_by,
                           t.title AS tournament_title,
                           COALESCE(rf.full_name, 'Official Referee') AS display_name,
                           COALESCE(rf.contact_number, u.email, 'N/A') AS contact_number,
                           rf.rating, rf.experience_years
                    FROM tournament_referee_requests r
                    JOIN tournaments t ON r.tournament_id = t.tournament_id
                    JOIN users u ON r.referee_user_id = u.user_id
                    LEFT JOIN referees rf ON r.referee_user_id = rf.user_id
                    WHERE t.organizer_id = ?
                    ORDER BY r.request_date DESC";
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$organizerId]);
            return ["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getRefereeIncomingRequests(int $refereeUserId): array
    {
        try {
            $sql = "SELECT r.request_id, r.tournament_id, r.referee_user_id, r.request_date, r.status,
                           COALESCE(r.initiated_by, 'REFEREE') AS initiated_by,
                           t.title AS tournament_title, t.location, t.start_date, t.end_date, t.tournament_held_date,
                           t.is_finalized, t.is_draw_finalized,
                           COALESCE(p.playground_name, NULL) AS playground_name,
                           COALESCE(p.address, p.location, NULL) AS playground_address,
                           COALESCE(o.organization_name, 'Elle Sports Association') AS organizer_name,
                           COALESCE(o.contact_number, 'Available on Request') AS contact_number
                    FROM tournament_referee_requests r
                    JOIN tournaments t ON r.tournament_id = t.tournament_id
                    LEFT JOIN organizers o ON t.organizer_id = o.user_id
                    LEFT JOIN tournament_playground_requests pr ON (t.tournament_id = pr.tournament_id AND pr.status IN ('ACCEPTED', 'APPROVED'))
                    LEFT JOIN playgrounds p ON pr.playground_user_id = p.user_id
                    WHERE r.referee_user_id = ?
                    ORDER BY r.request_date DESC";
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$refereeUserId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) {
                $row['is_finalized'] = (int)($row['is_finalized'] ?? 0);
                $row['is_draw_finalized'] = (int)($row['is_draw_finalized'] ?? 0);
                $row['isFinalized'] = $row['is_finalized'] === 1;
                $row['isDrawFinalized'] = $row['is_draw_finalized'] === 1;
            }
            return ["success" => true, "data" => $rows];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function respondToRefereeRequest(int $tournamentId, int $refereeUserId, string $status): array
    {
        try {
            $conn = Database::getConnection();
            $dbStatus = ($status === 'APPROVED' || $status === 'ACCEPTED') ? 'ACCEPTED' : 'REJECTED';

            if ($dbStatus === 'ACCEPTED') {
                $stmtLimit = $conn->prepare("SELECT maximum_referee_limit FROM tournaments WHERE tournament_id = ?");
                $stmtLimit->execute([$tournamentId]);
                $tRow = $stmtLimit->fetch(PDO::FETCH_ASSOC);
                $maxLimit = (int)($tRow['maximum_referee_limit'] ?? 2);

                if ($maxLimit > 0) {
                    $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM tournament_referee_requests WHERE tournament_id = ? AND status IN ('ACCEPTED', 'APPROVED')");
                    $stmtCount->execute([$tournamentId]);
                    $cRow = $stmtCount->fetch(PDO::FETCH_ASSOC);
                    if ((int)($cRow['total'] ?? 0) >= $maxLimit) {
                        return ["success" => false, "message" => "Cannot approve referee: Maximum limit of {$maxLimit} referees has been reached for this tournament."];
                    }
                }
            }

            $stmt = $conn->prepare("UPDATE tournament_referee_requests SET status = ? WHERE tournament_id = ? AND referee_user_id = ?");
            $stmt->execute([$dbStatus, $tournamentId, $refereeUserId]);

            $isAssigned = ($dbStatus === 'ACCEPTED' || $dbStatus === 'APPROVED');
            $this->syncRefereeAvailability($conn, $refereeUserId, $tournamentId, $isAssigned);

            return ["success" => true, "message" => "Referee request updated successfully"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function cancelRefereeRequest(int $tournamentId, int $refereeUserId): array
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("UPDATE tournament_referee_requests SET status = 'CANCELLED' WHERE tournament_id = ? AND referee_user_id = ?");
            $stmt->execute([$tournamentId, $refereeUserId]);

            $this->syncRefereeAvailability($conn, $refereeUserId, $tournamentId, false);
            return ["success" => true, "message" => "Officiating request cancelled successfully."];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    private function syncRefereeAvailability(PDO $conn, int $refereeUserId, int $tournamentId, bool $isAssigned): void
    {
        try {
            $stmt = $conn->prepare("SELECT tournament_held_date, start_date FROM tournaments WHERE tournament_id = ?");
            $stmt->execute([$tournamentId]);
            $t = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$t) return;
            $date = !empty($t['tournament_held_date']) ? $t['tournament_held_date'] : $t['start_date'];
            if (empty($date)) return;

            if ($isAssigned) {
                $stmtCheck = $conn->prepare("SELECT availability_id FROM referee_availability WHERE referee_user_id = ? AND available_date = ?");
                $stmtCheck->execute([$refereeUserId, $date]);
                $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $stmtUpdate = $conn->prepare("UPDATE referee_availability SET status = 'UNAVAILABLE' WHERE availability_id = ?");
                    $stmtUpdate->execute([$existing['availability_id']]);
                } else {
                    $stmtInsert = $conn->prepare("INSERT INTO referee_availability (referee_user_id, available_date, start_time, end_time, status) VALUES (?, ?, '08:00:00', '18:00:00', 'UNAVAILABLE')");
                    $stmtInsert->execute([$refereeUserId, $date]);
                }
            } else {
                $stmtOther = $conn->prepare("SELECT COUNT(*) as total FROM tournament_referee_requests r JOIN tournaments t ON r.tournament_id = t.tournament_id WHERE r.referee_user_id = ? AND r.status IN ('ACCEPTED', 'APPROVED') AND (t.tournament_held_date = ? OR t.start_date = ?)");
                $stmtOther->execute([$refereeUserId, $date, $date]);
                $countRow = $stmtOther->fetch(PDO::FETCH_ASSOC);

                if ((int)($countRow['total'] ?? 0) === 0) {
                    $stmtDelete = $conn->prepare("UPDATE referee_availability SET status = 'AVAILABLE' WHERE referee_user_id = ? AND available_date = ?");
                    $stmtDelete->execute([$refereeUserId, $date]);
                }
            }
        } catch (Exception $e) {
            error_log("syncRefereeAvailability error: " . $e->getMessage());
        }
    }

    public function getRefereeAvailabilityCalendar(int $refereeUserId): array
    {
        try {
            $conn = Database::getConnection();
            
            $stmt = $conn->prepare("SELECT availability_id, available_date, start_time, end_time, status FROM referee_availability WHERE referee_user_id = ?");
            $stmt->execute([$refereeUserId]);
            $explicit = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtT = $conn->prepare("
                SELECT r.tournament_id, t.title AS tournament_title, t.location, 
                       COALESCE(t.tournament_held_date, t.start_date) AS assigned_date
                FROM tournament_referee_requests r
                JOIN tournaments t ON r.tournament_id = t.tournament_id
                WHERE r.referee_user_id = ? AND r.status IN ('ACCEPTED', 'APPROVED')
            ");
            $stmtT->execute([$refereeUserId]);
            $assignedTournaments = $stmtT->fetchAll(PDO::FETCH_ASSOC);

            return [
                "success" => true,
                "data" => [
                    "availability" => $explicit,
                    "assignedTournaments" => $assignedTournaments
                ]
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getRefereeOfficiatingHistory(int $refereeUserId): array
    {
        try {
            $conn = Database::getConnection();
            $sql = "SELECT r.request_id, r.tournament_id, r.referee_user_id, r.request_date, r.status AS request_status,
                           t.title AS tournament_title, t.location, t.start_date, t.end_date, t.tournament_held_date, t.status AS tournament_status,
                           COALESCE(o.organization_name, 'Elle Sports Association') AS organizer_name,
                           COALESCE(o.contact_number, 'N/A') AS contact_number
                    FROM tournament_referee_requests r
                    JOIN tournaments t ON r.tournament_id = t.tournament_id
                    LEFT JOIN organizers o ON t.organizer_id = o.user_id
                    WHERE r.referee_user_id = ?
                      AND r.status IN ('ACCEPTED', 'APPROVED')
                      AND UPPER(t.status) = 'COMPLETED'
                    ORDER BY COALESCE(t.tournament_held_date, t.start_date, r.request_date) DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$refereeUserId]);
            return ["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getPlaygroundHostingHistory(int $playgroundUserId): array
    {
        try {
            $conn = Database::getConnection();
            $sql = "SELECT r.request_id, r.tournament_id, r.playground_user_id, r.request_date, r.status AS request_status,
                           t.title AS tournament_title, t.location, t.start_date, t.end_date, t.tournament_held_date, t.status AS tournament_status,
                           COALESCE(o.organization_name, 'Elle Sports Association') AS organizer_name,
                           COALESCE(o.contact_number, 'N/A') AS contact_number
                    FROM tournament_playground_requests r
                    JOIN tournaments t ON r.tournament_id = t.tournament_id
                    LEFT JOIN organizers o ON t.organizer_id = o.user_id
                    WHERE r.playground_user_id = ?
                      AND r.status IN ('ACCEPTED', 'APPROVED')
                      AND UPPER(t.status) = 'COMPLETED'
                    ORDER BY COALESCE(t.tournament_held_date, t.start_date, r.request_date) DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$playgroundUserId]);
            return ["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getPlaygroundHistory(int $playgroundUserId): array
    {
        return $this->getPlaygroundHostingHistory($playgroundUserId);
    }

    public function getTeamTournamentHistory(int $teamUserId): array
    {
        try {
            $conn = Database::getConnection();
            $sql = "SELECT r.tournament_id, r.team_user_id, r.request_date, r.status AS request_status,
                           t.title AS tournament_title, t.location, t.start_date, t.end_date, t.tournament_held_date, t.status AS tournament_status,
                           t.draw_data, t.prize_details, t.rules,
                           COALESCE(o.organization_name, 'Elle Sports Association') AS organizer_name,
                           COALESCE(o.contact_number, 'N/A') AS contact_number
                    FROM tournament_team_requests r
                    JOIN tournaments t ON r.tournament_id = t.tournament_id
                    LEFT JOIN organizers o ON t.organizer_id = o.user_id
                    WHERE r.team_user_id = ?
                      AND r.status IN ('ACCEPTED', 'APPROVED')
                      AND UPPER(t.status) = 'COMPLETED'
                    ORDER BY COALESCE(t.tournament_held_date, t.start_date, r.request_date) DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$teamUserId]);
            return ["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }



    public function saveRefereeAvailability(int $refereeUserId, string $availableDate, string $status): array
    {
        try {
            $conn = Database::getConnection();
            $dbStatus = (strtoupper($status) === 'UNAVAILABLE') ? 'UNAVAILABLE' : 'AVAILABLE';

            $stmtCheck = $conn->prepare("SELECT availability_id FROM referee_availability WHERE referee_user_id = ? AND available_date = ?");
            $stmtCheck->execute([$refereeUserId, $availableDate]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmtUpdate = $conn->prepare("UPDATE referee_availability SET status = ? WHERE availability_id = ?");
                $stmtUpdate->execute([$dbStatus, $existing['availability_id']]);
            } else {
                $stmtInsert = $conn->prepare("INSERT INTO referee_availability (referee_user_id, available_date, start_time, end_time, status) VALUES (?, ?, '08:00:00', '18:00:00', ?)");
                $stmtInsert->execute([$refereeUserId, $availableDate, $dbStatus]);
            }

            return ["success" => true, "message" => "Availability for date {$availableDate} updated to {$dbStatus}."];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getPlaygroundAvailabilityCalendar(int $playgroundUserId): array
    {
        try {
            $conn = Database::getConnection();
            
            $stmt = $conn->prepare("SELECT availability_id, available_date, start_time, end_time, status FROM playground_availability WHERE playground_user_id = ?");
            $stmt->execute([$playgroundUserId]);
            $explicit = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtT = $conn->prepare("
                SELECT tpr.tournament_id, t.title AS tournament_title, t.location, 
                       COALESCE(t.tournament_held_date, t.start_date) AS assigned_date
                FROM tournament_playground_requests tpr
                JOIN tournaments t ON tpr.tournament_id = t.tournament_id
                WHERE tpr.playground_user_id = ? AND tpr.status IN ('ACCEPTED', 'APPROVED')
            ");
            $stmtT->execute([$playgroundUserId]);
            $assignedTournaments = $stmtT->fetchAll(PDO::FETCH_ASSOC);

            return [
                "success" => true,
                "data" => [
                    "availability" => $explicit,
                    "assignedTournaments" => $assignedTournaments
                ]
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function savePlaygroundAvailability(int $playgroundUserId, string $availableDate, string $status): array
    {
        try {
            $conn = Database::getConnection();
            $dbStatus = (strtoupper($status) === 'UNAVAILABLE') ? 'UNAVAILABLE' : 'AVAILABLE';

            $stmtCheck = $conn->prepare("SELECT availability_id FROM playground_availability WHERE playground_user_id = ? AND available_date = ?");
            $stmtCheck->execute([$playgroundUserId, $availableDate]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmtUpdate = $conn->prepare("UPDATE playground_availability SET status = ? WHERE availability_id = ?");
                $stmtUpdate->execute([$dbStatus, $existing['availability_id']]);
            } else {
                $stmtInsert = $conn->prepare("INSERT INTO playground_availability (playground_user_id, available_date, start_time, end_time, status) VALUES (?, ?, '08:00:00', '18:00:00', ?)");
                $stmtInsert->execute([$playgroundUserId, $availableDate, $dbStatus]);
            }

            return ["success" => true, "message" => "Playground availability for date {$availableDate} updated to {$dbStatus}."];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function recalculateRefereesRatingForTournament(int $tournamentId): void
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("SELECT DISTINCT referee_user_id FROM tournament_referee_requests WHERE tournament_id = ? AND status IN ('ACCEPTED', 'APPROVED')");
            $stmt->execute([$tournamentId]);
            $referees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($referees as $ref) {
                $refereeId = (int) $ref['referee_user_id'];
                $this->recalculateRefereeRating($refereeId);
            }
        } catch (Exception $e) {
            // Silently ignore or log error
        }
    }

    public function recalculateRefereeRating(int $refereeUserId): float
    {
        try {
            $conn = Database::getConnection();
            
            $sql = "SELECT COUNT(DISTINCT r.tournament_id) AS total_count
                    FROM tournament_referee_requests r
                    JOIN tournaments t ON r.tournament_id = t.tournament_id
                    WHERE r.referee_user_id = ?
                      AND r.status IN ('ACCEPTED', 'APPROVED')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$refereeUserId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalCount = (int) ($row['total_count'] ?? 0);

            if ($totalCount === 0) {
                $rating = 0.0;
            } else {
                $rating = min(5.0, round(1.0 + ($totalCount * 0.8), 1));
            }

            $updateStmt = $conn->prepare("UPDATE referees SET rating = ? WHERE user_id = ?");
            $updateStmt->execute([$rating, $refereeUserId]);

            return (float) $rating;
        } catch (Exception $e) {
            return 0.0;
        }
    }



    public function getTournamentDraw(int $tournamentId): array
    {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("SELECT tournament_id, title, location, description, rules, prize_details, start_date, end_date, tournament_held_date, maximum_team_limit, status, is_finalized, is_draw_finalized, draw_data FROM tournaments WHERE tournament_id = ?");
            $stmt->execute([$tournamentId]);
            $tournament = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tournament) {
                return ["success" => false, "message" => "Tournament not found"];
            }

            $teamStmt = $db->prepare("
                SELECT u.user_id, u.email, 
                       COALESCE(t.team_name, u.email) as team_name, 
                       COALESCE(t.district, 'Sri Lanka') as district
                FROM tournament_team_requests ttr
                JOIN users u ON ttr.team_user_id = u.user_id
                LEFT JOIN teams t ON u.user_id = t.user_id
                WHERE ttr.tournament_id = ? AND (ttr.status = 'APPROVED' OR ttr.status = 'ACCEPTED')
            ");
            $teamStmt->execute([$tournamentId]);
            $participatingTeams = $teamStmt->fetchAll(PDO::FETCH_ASSOC);

            $drawData = null;
            if (!empty($tournament['draw_data'])) {
                $drawData = json_decode($tournament['draw_data'], true);
            } else if (!empty($participatingTeams)) {
                $shuffleRes = $this->shuffleTournamentDraw($tournamentId, 'RANDOM');
                if ($shuffleRes['success'] && isset($shuffleRes['data']['drawData'])) {
                    $drawData = $shuffleRes['data']['drawData'];
                }
            }

            return [
                "success" => true,
                "data" => [
                    "tournament" => $tournament,
                    "teams" => $participatingTeams,
                    "isFinalized" => (int) ($tournament['is_finalized'] ?? 0) === 1,
                    "isDrawFinalized" => (int) ($tournament['is_draw_finalized'] ?? 0) === 1,
                    "drawData" => $drawData
                ]
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error fetching tournament draw: " . $e->getMessage()
            ];
        }
    }

    public function finalizeTournament(int $tournamentId): array
    {
        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("SELECT * FROM tournaments WHERE tournament_id = ?");
            $stmt->execute([$tournamentId]);
            $tournament = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tournament) {
                return ["success" => false, "message" => "Tournament not found"];
            }

            // Update status to ACTIVE/ONGOING and is_finalized = 1
            $stmtUpdate = $db->prepare("UPDATE tournaments SET status = 'ACTIVE', is_finalized = 1 WHERE tournament_id = ?");
            $stmtUpdate->execute([$tournamentId]);

            // Auto-generate or fetch match draw
            $drawRes = $this->getTournamentDraw($tournamentId);
            $drawData = $drawRes['data']['drawData'] ?? null;

            if (!$drawData) {
                $shuffleRes = $this->shuffleTournamentDraw($tournamentId, 'SYSTEM');
                if ($shuffleRes['success'] && isset($shuffleRes['data']['drawData'])) {
                    $drawData = $shuffleRes['data']['drawData'];
                }
            }

            return [
                "success" => true,
                "message" => "Tournament setup finalized successfully! Match draw generated.",
                "data" => [
                    "tournament" => $tournament,
                    "drawData" => $drawData
                ]
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error finalizing tournament: " . $e->getMessage()
            ];
        }
    }

    public function shuffleTournamentDraw(int $tournamentId, string $mode = 'RANDOM'): array
    {
        try {
            $db = Database::getConnection();

            // Check if draw is already finalized/locked
            $tCheck = $db->prepare("SELECT is_draw_finalized FROM tournaments WHERE tournament_id = ?");
            $tCheck->execute([$tournamentId]);
            $tRow = $tCheck->fetch(PDO::FETCH_ASSOC);
            if ($tRow && (int)($tRow['is_draw_finalized'] ?? 0) === 1) {
                return [
                    "success" => false,
                    "message" => "Tournament match draw is fixed & locked! Further shuffling is not allowed."
                ];
            }

            $teamStmt = $db->prepare("
                SELECT u.user_id, u.email, 
                       COALESCE(t.team_name, u.email) as team_name, 
                       COALESCE(t.district, 'Sri Lanka') as district
                FROM tournament_team_requests ttr
                JOIN users u ON ttr.team_user_id = u.user_id
                LEFT JOIN teams t ON u.user_id = t.user_id
                WHERE ttr.tournament_id = ? AND (ttr.status = 'APPROVED' OR ttr.status = 'ACCEPTED')
                ORDER BY u.user_id ASC
            ");
            $teamStmt->execute([$tournamentId]);
            $teams = $teamStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($teams)) {
                return ["success" => false, "message" => "No approved participating teams found for match draw."];
            }

            $shuffledTeams = $teams;
            $modeUpper = strtoupper($mode);

            // DYNAMIC SHUFFLING MODES FOR ANY TEAM COUNT (N = 2, 4, 6, 8, 12, 16, 32...):
            // 1. MODE 1 (RANDOM): Standard random shuffle across all N teams
            // 2. MODE 2 (ALTERNATE): Interleaves halves / swaps team pairings dynamically for any N teams
            // 3. MODE 3 (REVERSE): Reverses the team lineup array completely for any N teams

            if ($modeUpper === 'ALTERNATE' || $modeUpper === 'SWAP' || $modeUpper === 'MODE2') {
                $count = count($shuffledTeams);
                if ($count >= 4) {
                    $half = (int) ceil($count / 2);
                    $firstHalf = array_slice($shuffledTeams, 0, $half);
                    $secondHalf = array_slice($shuffledTeams, $half);
                    
                    $shuffledTeams = [];
                    $max = max(count($firstHalf), count($secondHalf));
                    for ($i = 0; $i < $max; $i++) {
                        if (isset($secondHalf[$i])) {
                            $shuffledTeams[] = $secondHalf[$i];
                        }
                        if (isset($firstHalf[$i])) {
                            $shuffledTeams[] = $firstHalf[$i];
                        }
                    }
                } else if ($count >= 2) {
                    $temp = $shuffledTeams[0];
                    $shuffledTeams[0] = $shuffledTeams[$count - 1];
                    $shuffledTeams[$count - 1] = $temp;
                }
            } else if ($modeUpper === 'REVERSE' || $modeUpper === 'MODE3') {
                $shuffledTeams = array_reverse($shuffledTeams);
            } else {
                // Default: Mode 1 (RANDOM)
                shuffle($shuffledTeams);
            }


            // Construct Primary Round Match Pairings
            $primaryRoundMatches = [];
            for ($i = 0; $i < count($shuffledTeams); $i += 2) {
                $team1 = $shuffledTeams[$i];
                $team2 = isset($shuffledTeams[$i + 1]) ? $shuffledTeams[$i + 1] : null;

                $primaryRoundMatches[] = [
                    "matchNumber" => count($primaryRoundMatches) + 1,
                    "stage" => "Primary Round",
                    "team1" => $team1,
                    "team2" => $team2,
                    "winner" => null
                ];
            }

            $drawData = [
                "teams" => $shuffledTeams,
                "primaryRoundMatches" => $primaryRoundMatches,
                "shuffleMode" => $modeUpper,
                "drawFormat" => "knockout",
                "shuffledAt" => date('Y-m-d H:i:s'),
                "bracketWinners" => [],
                "matchScores" => (object)[]
            ];

            $drawDataJson = json_encode($drawData);
            $updateStmt = $db->prepare("UPDATE tournaments SET draw_data = ? WHERE tournament_id = ?");
            $updateStmt->execute([$drawDataJson, $tournamentId]);

            return [
                "success" => true,
                "message" => "Tournament match draw shuffled using " . $modeUpper . " mode!",
                "data" => [
                    "teams" => $shuffledTeams,
                    "drawData" => $drawData,
                    "isDrawFinalized" => false
                ]
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error generating match draw: " . $e->getMessage()
            ];
        }
    }


    public function saveTournamentDraw(int $tournamentId, object $request): array
    {
        try {
            $db = Database::getConnection();
            $drawDataJson = isset($request->drawData) ? json_encode($request->drawData) : json_encode($request);

            $stmt = $db->prepare("UPDATE tournaments SET is_draw_finalized = 1, draw_data = ? WHERE tournament_id = ?");
            $stmt->execute([$drawDataJson, $tournamentId]);

            // Fetch tournament date for match_date
            $tStmt = $db->prepare("SELECT tournament_held_date, start_date FROM tournaments WHERE tournament_id = ?");
            $tStmt->execute([$tournamentId]);
            $tRow = $tStmt->fetch(PDO::FETCH_ASSOC);
            $matchDate = !empty($tRow['tournament_held_date']) ? $tRow['tournament_held_date'] : (!empty($tRow['start_date']) ? $tRow['start_date'] : date('Y-m-d'));
            $matchTime = '09:00:00';

            $rawTeams = isset($request->drawData->teams) ? $request->drawData->teams : (isset($request->teams) ? $request->teams : []);
            if (!empty($rawTeams)) {
                $stmtDelete = $db->prepare("DELETE FROM matches WHERE tournament_id = ?");
                $stmtDelete->execute([$tournamentId]);

                $half = ceil(count($rawTeams) / 2);
                $groupA = array_slice($rawTeams, 0, $half);
                $groupB = array_slice($rawTeams, $half);

                $stmtMatch = $db->prepare("INSERT INTO matches (tournament_id, match_date, match_time, round, status) VALUES (?, ?, ?, ?, 'SCHEDULED')");

                for ($i = 0; $i < count($groupA); $i += 2) {
                    $stmtMatch->execute([$tournamentId, $matchDate, $matchTime, 'Group A - Quarter Final']);
                }
                for ($i = 0; $i < count($groupB); $i += 2) {
                    $stmtMatch->execute([$tournamentId, $matchDate, $matchTime, 'Group B - Quarter Final']);
                }
                $stmtMatch->execute([$tournamentId, $matchDate, $matchTime, 'Semi Final 1']);
                $stmtMatch->execute([$tournamentId, $matchDate, $matchTime, 'Semi Final 2']);
                $stmtMatch->execute([$tournamentId, $matchDate, $matchTime, 'Final Match']);
            }

            // Trigger #7: Send Notification on Tournament Setup Finalization to all participants
            try {
                require_once __DIR__ . "/NotificationService.php";
                $notifService = new NotificationService();
                $tStmt2 = $db->prepare("SELECT title FROM tournaments WHERE tournament_id = ?");
                $tStmt2->execute([$tournamentId]);
                $tTitle = $tStmt2->fetchColumn() ?: "Championship";
                $notifService->sendToTournamentParticipants(
                    $tournamentId,
                    "Match Schedule Live! ⚡",
                    "Setup and match schedule for tournament '{$tTitle}' is finalized. Check your match fixtures!",
                    "TOURNAMENT"
                );
            } catch (Exception $e) {}

            return [
                "success" => true,
                "message" => "Tournament match draw schedule saved and permanently locked successfully! 🔒"
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error saving match draw: " . $e->getMessage()
            ];
        }
    }


    public function completeTournament(int $tournamentId): array
    {
        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("UPDATE tournaments SET status = 'COMPLETED' WHERE tournament_id = ?");
            $stmt->execute([$tournamentId]);

            $stmtMatch = $db->prepare("UPDATE matches SET status = 'COMPLETED' WHERE tournament_id = ?");
            $stmtMatch->execute([$tournamentId]);

            // Auto-reject any remaining PENDING requests for this completed tournament
            $db->prepare("UPDATE tournament_team_requests SET status = 'REJECTED' WHERE tournament_id = ? AND status = 'PENDING'")->execute([$tournamentId]);
            $db->prepare("UPDATE tournament_referee_requests SET status = 'REJECTED' WHERE tournament_id = ? AND status = 'PENDING'")->execute([$tournamentId]);
            $db->prepare("UPDATE tournament_sponsor_requests SET status = 'REJECTED' WHERE tournament_id = ? AND status = 'PENDING'")->execute([$tournamentId]);
            $db->prepare("UPDATE tournament_playground_requests SET status = 'REJECTED' WHERE tournament_id = ? AND status = 'PENDING'")->execute([$tournamentId]);

            // Trigger #8: Broadcast Champion Winner Announcement on Tournament Completion to all participants
            try {
                require_once __DIR__ . "/NotificationService.php";
                $notifService = new NotificationService();
                $tStmt = $db->prepare("SELECT title, draw_data FROM tournaments WHERE tournament_id = ?");
                $tStmt->execute([$tournamentId]);
                $tRow = $tStmt->fetch(PDO::FETCH_ASSOC);
                $title = $tRow['title'] ?? 'Elle Championship';
                $drawData = !empty($tRow['draw_data']) ? json_decode($tRow['draw_data'], true) : [];
                $winner = $drawData['winner'] ?? $drawData['bracketWinners']['champion'] ?? 'Winner Team';

                $notifService->sendToTournamentParticipants(
                    $tournamentId,
                    "Championship Winner Announced! 🏆",
                    "The {$title} championship has concluded! Congratulations to {$winner} for winning the tournament!",
                    "TOURNAMENT"
                );
            } catch (Exception $e) {}

            return [
                "success" => true,
                "message" => "Tournament has been successfully completed and all pending requests closed!"
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error completing tournament: " . $e->getMessage()
            ];
        }
    }

}
