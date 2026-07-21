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

        // NOTE: organizerId should later be obtained from the authenticated JWT token.
        // For now (temporary) we accept organizerId from the request body as provided.
        $tournament->setOrganizerId($requestObject->organizerId ?? null);
        $tournament->setTitle($requestObject->title ?? null);
        $tournament->setDescription($requestObject->description ?? null);
        $tournament->setLocation($requestObject->location ?? null);
        $tournament->setStartDate($requestObject->startDate ?? null);
        $tournament->setEndDate($requestObject->endDate ?? null);
        $tournament->setTournamentHeldDate($requestObject->tournamentHeldDate ?? null);
        $tournament->setMaximumTeamLimit($requestObject->maximumTeamLimit ?? null);
        $tournament->setMaximumRefereeLimit($requestObject->maximumRefereeLimit ?? $requestObject->requiredReferees ?? 2);
        $tournament->setRules($requestObject->rules ?? null);
        $tournament->setPrizeDetails($requestObject->prizeDetails ?? null);

        $result = $this->tournamentService->createTournament($tournament);

        if ($result["success"]) {
            http_response_code(201);
        } else {
            http_response_code(500);
        }

        header(self::JSON_HEADER);
        echo json_encode($result);
    }


//    In here We are geting the Pending tournaments that show insdie of the Admin Side
    public function getPendingTournaments()
    {
        $result = $this->tournamentService->getPendingTournaments();

        header(self::JSON_HEADER);
        echo json_encode($result);
    }

//    Search by Name Only get the Approved Tournaments that show inside of the Admin Side
    public function getApprovedTournaments()
    {
        $search = $_GET["search"] ?? "";

        $result = $this->tournamentService->getApprovedTournaments($search);

        header(self::JSON_HEADER);
        echo json_encode($result);
    }

//    Update the Status By the Organizer
    public function updateTournamentStatus($tournamentId)
    {
        $requestBody = file_get_contents("php://input");
        $request = json_decode($requestBody);

        if (!isset($request->status)) {

            echo json_encode([
                "success" => false,
                "message" => "Tournament status is required."
            ]);

            return ;
        }

        $result = $this->tournamentService->updateTournamentStatus(
            (int)$tournamentId,
            strtoupper($request->status)
        );

        header(self::JSON_HEADER);

        echo json_encode($result);
    }



//    Update the Approval Status By the Admin
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

//    Filter by the Status
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

//    Get Tournament by ID (aslo Searching we can do)
    public function getTournamentById($tournamentId)
    {
        header("Content-Type: application/json");

        $result = $this->tournamentService->getTournamentById((int) $tournamentId);

        echo json_encode($result);
    }

//    Update the tournament Details
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

//    Organizer's own tournaments
    public function getOrganizerTournaments($organizerId)
    {
        header("Content-Type: application/json");

        $result = $this->tournamentService
            ->getOrganizerTournaments((int) $organizerId);

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
        $result = $this->tournamentService->updateTournamentStatus((int) $tournamentId, "ONGOING");
        echo json_encode($result);
    }

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

        if (!isset($requestObject->playgroundUserId) || !isset($requestObject->initiatedBy)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            return;
        }

        $result = $this->tournamentService->sendPlaygroundRequest((int) $tournamentId, (int) $requestObject->playgroundUserId, $requestObject->initiatedBy);
        echo json_encode($result);
    }

    public function respondToPlaygroundRequest($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        if (!isset($requestObject->playgroundUserId) || !isset($requestObject->status)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            return;
        }

        $result = $this->tournamentService->respondToPlaygroundRequest((int) $tournamentId, (int) $requestObject->playgroundUserId, $requestObject->status);
        echo json_encode($result);
    }

    // Sponsor Requests
    public function getSponsorRequests($tournamentId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getSponsorRequests((int) $tournamentId);
        echo json_encode($result);
    }

    public function sendSponsorRequest($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        if (!isset($requestObject->sponsorUserId) || !isset($requestObject->initiatedBy)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            return;
        }

        $result = $this->tournamentService->sendSponsorRequest((int) $tournamentId, (int) $requestObject->sponsorUserId, $requestObject->initiatedBy);
        echo json_encode($result);
    }

    public function respondToSponsorRequest($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        if (!isset($requestObject->sponsorUserId) || !isset($requestObject->status)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            return;
        }

        $result = $this->tournamentService->respondToSponsorRequest((int) $tournamentId, (int) $requestObject->sponsorUserId, $requestObject->status);
        echo json_encode($result);
    }

    public function getSponsorIncomingRequests($sponsorUserId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getSponsorIncomingRequests((int) $sponsorUserId);
        echo json_encode($result);
    }

    // Referee Requests
    public function getOrganizerRefereeRequests($organizerId)
    {
        header(self::JSON_HEADER);
        $result = $this->tournamentService->getOrganizerRefereeRequests((int) $organizerId);
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

        if (!isset($requestObject->refereeUserId) || !isset($requestObject->initiatedBy)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            return;
        }

        $result = $this->tournamentService->sendRefereeRequest((int) $tournamentId, (int) $requestObject->refereeUserId, $requestObject->initiatedBy);
        echo json_encode($result);
    }

    public function respondToRefereeRequest($tournamentId)
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        if (!isset($requestObject->refereeUserId) || !isset($requestObject->status)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            return;
        }

        $result = $this->tournamentService->respondToRefereeRequest((int) $tournamentId, (int) $requestObject->refereeUserId, $requestObject->status);
        echo json_encode($result);
    }
}
