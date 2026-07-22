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
                $stmt = $conn->prepare("INSERT INTO tournament_referee_requests (tournament_id, referee_user_id, status, request_date) VALUES (?, ?, 'APPROVED', NOW())");
                foreach ($request->refereeUserIds as $rid) {
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
                       t.title AS tournament_title, t.location, t.start_date, t.end_date, t.tournament_held_date,
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
            
            $stmt = $conn->prepare("UPDATE tournament_sponsor_requests SET status = ? WHERE tournament_id = ? AND sponsor_user_id = ?");
            $stmt->execute([$status, $tournamentId, $sponsorUserId]);

            return ["success" => true, "message" => "Request updated successfully"];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getSponsorIncomingRequests(int $sponsorUserId): array
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("
                SELECT t.tournament_id, t.title, t.description, t.location, t.tournament_held_date,
                       tsr.status, tsr.request_date
                FROM tournament_sponsor_requests tsr
                JOIN tournaments t ON tsr.tournament_id = t.tournament_id
                WHERE tsr.sponsor_user_id = ? AND tsr.initiated_by = 'ORGANIZER'
                ORDER BY tsr.request_date DESC
            ");
            $stmt->execute([$sponsorUserId]);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ["success" => true, "data" => $requests];
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

    public function sendRefereeRequest(int $tournamentId, int $refereeUserId, string $initiatedBy = 'ORGANIZER'): array
    {
        try {
            $conn = Database::getConnection();

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
                           COALESCE(o.organization_name, 'Elle Sports Association') AS organizer_name,
                           COALESCE(o.contact_number, 'Available on Request') AS contact_number
                    FROM tournament_referee_requests r
                    JOIN tournaments t ON r.tournament_id = t.tournament_id
                    LEFT JOIN organizers o ON t.organizer_id = o.user_id
                    WHERE r.referee_user_id = ?
                    ORDER BY r.request_date DESC";
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$refereeUserId]);
            return ["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
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
            
            // Count completed tournaments officiated by this referee
            $sql = "SELECT COUNT(DISTINCT r.tournament_id) AS completed_count
                    FROM tournament_referee_requests r
                    JOIN tournaments t ON r.tournament_id = t.tournament_id
                    WHERE r.referee_user_id = ?
                      AND r.status IN ('ACCEPTED', 'APPROVED')
                      AND UPPER(t.status) = 'COMPLETED'";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$refereeUserId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $completedCount = (int) ($row['completed_count'] ?? 0);

            // Calculation Formula:
            // Base Rating: 5.0 (out of 10.0 max)
            // Each completed tournament officiated adds +0.5 points
            // Maximum cap: 10.0
            $rating = 5.0 + ($completedCount * 0.5);
            if ($rating > 10.0) {
                $rating = 10.0;
            }
            $rating = round($rating, 1);

            // Update in referees table
            $stmtRef = $conn->prepare("UPDATE referees SET referee_rating = ? WHERE user_id = ?");
            $stmtRef->execute([$rating, $refereeUserId]);

            // Update in users table if column exists
            $stmtUsers = $conn->prepare("UPDATE users SET referee_rating = ? WHERE user_id = ?");
            $stmtUsers->execute([$rating, $refereeUserId]);

            return $rating;
        } catch (Exception $e) {
            return 5.0;
        }
    }
}

