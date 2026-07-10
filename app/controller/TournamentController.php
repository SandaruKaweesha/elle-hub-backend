<?php

require_once __DIR__ . "/../service/TournamentService.php";
require_once __DIR__ . "/../model/Tournament.php";
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
        $tournament->setMaximumTeamLimit($requestObject->maximumTeamLimit ?? null);
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
        $requestBody = file_get_contents("php://input");

        $request = json_decode($requestBody);

        if (!isset($request->approvalStatus)) {

            echo json_encode([
                "success" => false,
                "message" => "Approval status is required."
            ]);

            return;
        }

        $result = $this->tournamentService->updateApprovalStatus(
            (int)$tournamentId,
            strtoupper($request->approvalStatus)
        );

        header("Content-Type: application/json");

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

}

