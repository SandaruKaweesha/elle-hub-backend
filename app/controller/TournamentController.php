<?php

require_once __DIR__ . "/../service/TournamentService.php";
require_once __DIR__ . "/../model/Tournament.php";
require_once __DIR__ . "/../core/AuthMiddleware.php";

class TournamentController{
    private const JSON_HEADER = "Content-Type: application/json";
    private $tournamentService;

    public function __construct(){
        $this->tournamentService = new TournamentService();
    }

    public function createTournament(){
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $tournament = new Tournament();

        $tournament->setOrganizerId($requestObject->organizerId ?? $requestObject->organizerUserId ?? $requestObject->organizer_id ?? null);
        $tournament->setTitle($requestObject->title ?? null);
        $tournament->setDescription($requestObject->description ?? null);
        $tournament->setLocation($requestObject->location ?? null);
        $tournament->setStartDate($requestObject->startDate ?? null);
        $tournament->setEndDate($requestObject->endDate ?? null);
        $tournament->setTournamentHeldDate($requestObject->tournamentHeldDate ?? null);
        $tournament->setMaximumTeamLimit($requestObject->maximumTeamLimit ?? null);
        $tournament->setMaximumRefereeLimit($requestObject->maximumRefereeLimit ?? $requestObject->requiredReferees ?? 2);
        $imageUrl = $requestObject->imageUrl ?? $requestObject->image_url ?? null;
        if (empty($imageUrl) || trim((string)$imageUrl) === '') {
            $randomNum = rand(1, 5);
            $imageUrl = "/images/elle{$randomNum}.jpeg";
        }
        $tournament->setImageUrl($imageUrl);

        $tournament->setRules($requestObject->rules ?? null);
        $tournament->setPrizeDetails($requestObject->prizeDetails ?? null);
        if (isset($requestObject->imageUrl) || isset($requestObject->image_url)) {
            $tournament->setImageUrl($requestObject->imageUrl ?? $requestObject->image_url);
        }


        $result = $this->tournamentService->createTournament($tournament);

        if ($result["success"]) {
            http_response_code(201);
        } else {
            http_response_code(500);
        }

        header(self::JSON_HEADER);
        echo json_encode($result);
    }

    public function getPendingTournaments()
    {
        $result = $this->tournamentService->getPendingTournaments();
        header(self::JSON_HEADER);
        echo json_encode($result);
    }

    public function getApprovedTournaments()
    {
        $search = $_GET["search"] ?? "";
        $result = $this->tournamentService->getApprovedTournaments($search);
        header(self::JSON_HEADER);
        echo json_encode($result);
    }

    public function updateTournamentStatus($tournamentId)
    {
        $requestBody = file_get_contents("php://input");
        $request = json_decode($requestBody);

        if (!isset($request->status)) {
            echo json_encode([
                "success" => false,
                "message" => "Tournament status is required."
            ]);
            return;
        }

        $result = $this->tournamentService->updateTournamentStatus(
            (int)$tournamentId,
            strtoupper($request->status)
        );

        header(self::JSON_HEADER);
        echo json_encode($result);
    }

    public function updateApprovalStatus($tournamentId)
    {
        header("Content-Type: application/json");
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        if (
            !isset($requestObject->approvalStatus) ||
            !isset($requestObject->adminId)
        ) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Approval status and Admin ID are required."
            ]);
            return;
        }

        $result = $this->tournamentService->updateApprovalStatus(
            (int) $tournamentId,
            strtoupper($requestObject->approvalStatus),
            (int) $requestObject->adminId
        );

        echo json_encode($result);
    }

    public function filterTournamentsByStatus()
    {
        header("Content-Type: application/json");
        $status = $_GET["status"] ?? null;

        if ($status === null) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Tournament status is required."
            ]);
            return;
        }

        $result = $this->tournamentService->filterTournamentsByStatus(strtoupper($status));
        echo json_encode($result);
    }

    public function getTournamentById($tournamentId)
    {
        header("Content-Type: application/json");
        $result = $this->tournamentService->getTournamentById((int) $tournamentId);
        echo json_encode($result);
    }

    public function updateTournament($tournamentId)
    {
        header("Content-Type: application/json");
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        if ($requestObject === null) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON request body."
            ]);
            return;
        }

        $result = $this->tournamentService->updateTournament(
            (int) $tournamentId,
            $requestObject
        );

        echo json_encode($result);
    }

    public function getOrganizerTournaments($organizerId = null)
    {
        header("Content-Type: application/json");
        $authPayload = AuthMiddleware::requireRole(['ORGANIZER', 'ADMIN', 'TEAM', 'REFEREE', 'SPONSOR', 'PLAYGROUND']);
        $authenticatedId = (int)($authPayload['userId'] ?? 0);

        if ($organizerId === null || !is_numeric($organizerId) || (int)$organizerId <= 0) {
            $targetId = $authenticatedId;
        } else {
            $targetId = (int)$organizerId;
        }

        $result = $this->tournamentService->getOrganizerTournaments($targetId);
        echo json_encode($result);
    }


    public function getAllTournaments()
    {
        AuthMiddleware::requireRole(['ADMIN']);
        header("Content-Type: application/json");
        $result = $this->tournamentService->getAllTournaments();
        echo json_encode($result);
    }

    public function getTournamentAssignments($tournamentId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getTournamentAssignments((int) $tournamentId);
        echo json_encode($result);
    }

    public function saveTournamentAssignments($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);
        
        if ($requestObject === null) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid JSON payload"]);
            return;
        }

        $result = $this->tournamentService->saveTournamentAssignments((int) $tournamentId, $requestObject);
        echo json_encode($result);
    }

    public function finalizeTournament($tournamentId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->finalizeTournament((int) $tournamentId);
        echo json_encode($result);
    }

    public function getTournamentDraw($tournamentId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getTournamentDraw((int) $tournamentId);
        echo json_encode($result);
    }

    public function saveTournamentDraw($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);
        $result = $this->tournamentService->saveTournamentDraw((int) $tournamentId, $requestObject);
        echo json_encode($result);
    }

    public function shuffleTournamentDraw($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);
        $mode = isset($requestObject->mode) ? $requestObject->mode : 'RANDOM';
        $result = $this->tournamentService->shuffleTournamentDraw((int) $tournamentId, $mode);
        echo json_encode($result);
    }

    public function completeTournament($tournamentId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->completeTournament((int) $tournamentId);
        echo json_encode($result);
    }

    // Playground Requests
    public function getPlaygroundRequests($tournamentId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getPlaygroundRequests((int) $tournamentId);
        echo json_encode($result);
    }

    public function sendPlaygroundRequest($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $playgroundUserId = $requestObject->playgroundUserId ?? $requestObject->playground_user_id ?? $requestObject->playgroundOwnerId ?? $requestObject->user_id ?? $requestObject->userId ?? 0;
        $initiatedBy = $requestObject->initiatedBy ?? $requestObject->initiated_by ?? 'ORGANIZER';

        if (!$playgroundUserId) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing playgroundUserId field"]);
            return;
        }

        $result = $this->tournamentService->sendPlaygroundRequest((int) $tournamentId, (int) $playgroundUserId, $initiatedBy);
        echo json_encode($result);
    }

    public function respondToPlaygroundRequest($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $playgroundUserId = $requestObject->playgroundUserId ?? $requestObject->playground_user_id ?? $requestObject->playgroundOwnerId ?? $requestObject->user_id ?? $requestObject->userId ?? 0;
        $status = $requestObject->status ?? '';

        if (!$playgroundUserId || !$status) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            return;
        }

        $result = $this->tournamentService->respondToPlaygroundRequest((int) $tournamentId, (int) $playgroundUserId, $status);
        echo json_encode($result);
    }

    public function getOrganizerPlaygroundRequests($organizerId = null)
    {
        header(self::JSON_HEADER);
        $authPayload = AuthMiddleware::getPayload();
        $authenticatedId = (int)($authPayload['userId'] ?? 0);

        if ($organizerId === null || !is_numeric($organizerId) || (int)$organizerId <= 0) {
            $targetId = $authenticatedId;
        } else {
            $targetId = (int)$organizerId;
        }

        $result = $this->tournamentService->getOrganizerPlaygroundRequests($targetId);
        echo json_encode($result);
    }


    public function getPlaygroundIncomingRequests($playgroundUserId = null)
    {
        header(self::JSON_HEADER);
        $authPayload = AuthMiddleware::getPayload();
        $authenticatedId = (int)($authPayload['userId'] ?? 0);

        if ($playgroundUserId === null || !is_numeric($playgroundUserId) || (int)$playgroundUserId <= 0) {
            $targetId = $authenticatedId;
        } else {
            $targetId = (int)$playgroundUserId;
        }

        $result = $this->tournamentService->getPlaygroundIncomingRequests($targetId);
        echo json_encode($result);
    }

    public function cancelPlaygroundRequest()
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        if (!isset($requestObject->tournamentId) || !isset($requestObject->playgroundUserId)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing tournamentId or playgroundUserId"]);
            return;
        }

        $result = $this->tournamentService->cancelPlaygroundRequest((int) $requestObject->tournamentId, (int) $requestObject->playgroundUserId);
        echo json_encode($result);
    }

    // Sponsor Requests
    public function getSponsorRequests($tournamentId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getSponsorRequests((int) $tournamentId);
        echo json_encode($result);
    }

    public function getOrganizerSponsorRequests($organizerId = null)
    {
        header(self::JSON_HEADER);
        $authPayload = AuthMiddleware::getPayload();
        $authenticatedId = (int)($authPayload['userId'] ?? 0);

        if ($organizerId === null || !is_numeric($organizerId) || (int)$organizerId <= 0) {
            $targetId = $authenticatedId;
        } else {
            $targetId = (int)$organizerId;
        }

        $result = $this->tournamentService->getOrganizerSponsorRequests($targetId);
        echo json_encode($result);
    }

    public function sendSponsorRequest($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $sponsorUserId = $requestObject->sponsorUserId ?? $requestObject->sponsor_user_id ?? $requestObject->sponsorId ?? $requestObject->user_id ?? $requestObject->userId ?? 0;
        $initiatedBy = $requestObject->initiatedBy ?? $requestObject->initiated_by ?? 'ORGANIZER';

        if (!$sponsorUserId) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing sponsorUserId field"]);
            return;
        }

        $result = $this->tournamentService->sendSponsorRequest((int) $tournamentId, (int) $sponsorUserId, $initiatedBy);
        echo json_encode($result);
    }

    public function respondToSponsorRequest($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $sponsorUserId = $requestObject->sponsorUserId ?? $requestObject->sponsor_user_id ?? $requestObject->sponsorId ?? $requestObject->user_id ?? $requestObject->userId ?? 0;
        $status = $requestObject->status ?? '';

        if (!$sponsorUserId || !$status) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            return;
        }

        $result = $this->tournamentService->respondToSponsorRequest((int) $tournamentId, (int) $sponsorUserId, $status);
        echo json_encode($result);
    }

    public function cancelSponsorRequest()
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $tournamentId = $requestObject->tournamentId ?? $requestObject->tournament_id ?? 0;
        $sponsorUserId = $requestObject->sponsorUserId ?? $requestObject->sponsor_user_id ?? 0;

        if (!$tournamentId || !$sponsorUserId) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            return;
        }

        $result = $this->tournamentService->respondToSponsorRequest((int) $tournamentId, (int) $sponsorUserId, 'CANCELLED');
        echo json_encode($result);
    }

    public function getSponsorIncomingRequests($sponsorUserId = null)
    {
        header(self::JSON_HEADER);
        $authPayload = AuthMiddleware::getPayload();
        $authenticatedId = (int)($authPayload['userId'] ?? 0);

        if ($sponsorUserId === null || !is_numeric($sponsorUserId) || (int)$sponsorUserId <= 0) {
            $targetId = $authenticatedId;
        } else {
            $targetId = (int)$sponsorUserId;
        }

        $result = $this->tournamentService->getSponsorIncomingRequests($targetId);
        echo json_encode($result);
    }

    // Referee Requests
    public function getRefereeIncomingRequests($refereeUserId = null)
    {
        header(self::JSON_HEADER);
        $authPayload = AuthMiddleware::getPayload();
        $authenticatedId = (int)($authPayload['userId'] ?? 0);

        if ($refereeUserId === null || !is_numeric($refereeUserId) || (int)$refereeUserId <= 0) {
            $targetId = $authenticatedId;
        } else {
            $targetId = (int)$refereeUserId;
        }

        $result = $this->tournamentService->getRefereeIncomingRequests($targetId);
        echo json_encode($result);
    }

    public function getOrganizerRefereeRequests($organizerId = null)
    {
        header(self::JSON_HEADER);
        $authPayload = AuthMiddleware::getPayload();
        $authenticatedId = (int)($authPayload['userId'] ?? 0);

        if ($organizerId === null || !is_numeric($organizerId) || (int)$organizerId <= 0) {
            $targetId = $authenticatedId;
        } else {
            $targetId = (int)$organizerId;
        }

        $result = $this->tournamentService->getOrganizerRefereeRequests($targetId);
        echo json_encode($result);
    }

    public function getRefereeRequests($tournamentId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getRefereeRequests((int) $tournamentId);
        echo json_encode($result);
    }

    public function sendRefereeRequest($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $refereeUserId = $requestObject->refereeUserId ?? $requestObject->referee_user_id ?? $requestObject->refereeId ?? $requestObject->user_id ?? $requestObject->userId ?? 0;
        $initiatedBy = $requestObject->initiatedBy ?? $requestObject->initiated_by ?? 'ORGANIZER';

        if (!$refereeUserId) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing refereeUserId field"]);
            return;
        }

        $result = $this->tournamentService->sendRefereeRequest((int) $tournamentId, (int) $refereeUserId, $initiatedBy);
        echo json_encode($result);
    }

    public function respondToRefereeRequest($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $refereeUserId = $requestObject->refereeUserId ?? $requestObject->referee_user_id ?? $requestObject->refereeId ?? $requestObject->user_id ?? $requestObject->userId ?? 0;
        $status = $requestObject->status ?? '';

        if (!$refereeUserId || !$status) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            return;
        }

        $result = $this->tournamentService->respondToRefereeRequest((int) $tournamentId, (int) $refereeUserId, $status);
        echo json_encode($result);
    }

    public function cancelRefereeRequest()
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        if (!isset($requestObject->tournamentId) || !isset($requestObject->refereeUserId)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing tournamentId or refereeUserId"]);
            return;
        }

        $result = $this->tournamentService->cancelRefereeRequest((int) $requestObject->tournamentId, (int) $requestObject->refereeUserId);
        echo json_encode($result);
    }

    public function saveRefereeAvailability()
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        if (!isset($requestObject->refereeUserId) || !isset($requestObject->availableDate) || !isset($requestObject->status)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing refereeUserId, availableDate, or status"]);
            return;
        }

        $result = $this->tournamentService->saveRefereeAvailability((int) $requestObject->refereeUserId, $requestObject->availableDate, $requestObject->status);
        echo json_encode($result);
    }

    public function getPlaygroundAvailabilityCalendar($playgroundUserId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getPlaygroundAvailabilityCalendar((int) $playgroundUserId);
        echo json_encode($result);
    }

    public function savePlaygroundAvailability()
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        if (!isset($requestObject->playgroundUserId) || !isset($requestObject->availableDate) || !isset($requestObject->status)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing playgroundUserId, availableDate, or status"]);
            return;
        }

        $result = $this->tournamentService->savePlaygroundAvailability((int) $requestObject->playgroundUserId, $requestObject->availableDate, $requestObject->status);
        echo json_encode($result);
    }

    public function getPlaygroundHostingHistory($playgroundUserId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getPlaygroundHistory((int) $playgroundUserId);
        echo json_encode($result);
    }

    public function getRefereeOfficiatingHistory($refereeUserId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getRefereeOfficiatingHistory((int) $refereeUserId);
        echo json_encode($result);
    }


    public function getSponsorHistory($sponsorUserId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getSponsorHistory((int) $sponsorUserId);
        echo json_encode($result);
    }

    public function getTeamTournamentHistory($teamUserId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getTeamTournamentHistory((int) $teamUserId);
        echo json_encode($result);
    }

    public function getOrganizerHistory($organizerId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getOrganizerHistory((int) $organizerId);
        echo json_encode($result);
    }

    public function requestTournamentDeletion($tournamentId)
    {
        require_once __DIR__ . "/../core/AuthMiddleware.php";
        $payload = AuthMiddleware::requireRole(['ORGANIZER']);
        $organizerId = (int)$payload['userId'];

        header("Content-Type: application/json");
        $result = $this->tournamentService->requestTournamentDeletion((int)$tournamentId, $organizerId);
        http_response_code($result["success"] ? 200 : 400);
        echo json_encode($result);
    }

    public function approveTournamentDeletion($tournamentId)
    {
        require_once __DIR__ . "/../core/AuthMiddleware.php";
        $payload = AuthMiddleware::requireRole(['ADMIN']);
        $adminId = (int)$payload['userId'];

        header("Content-Type: application/json");
        $result = $this->tournamentService->approveTournamentDeletion((int)$tournamentId, $adminId);
        http_response_code($result["success"] ? 200 : 400);
        echo json_encode($result);
    }

    public function rejectTournamentDeletion($tournamentId)
    {
        require_once __DIR__ . "/../core/AuthMiddleware.php";
        $payload = AuthMiddleware::requireRole(['ADMIN']);
        $adminId = (int)$payload['userId'];

        header("Content-Type: application/json");
        $result = $this->tournamentService->rejectTournamentDeletion((int)$tournamentId, $adminId);
        http_response_code($result["success"] ? 200 : 400);
        echo json_encode($result);
    }
}