<?php

require_once __DIR__ . "/../service/TournamentService.php";
require_once __DIR__ . "/../model/Tournament.php";
class TournamentController{
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

        header("Content-Type: application/json");
        echo json_encode($result);
    }


//    In here We are geting the Pending tournaments that show insdie of the Admin Side
    public function getPendingTournaments()
    {
        $result = $this->tournamentService->getPendingTournaments();

        header("Content-Type: application/json");
        echo json_encode($result);
    }


//    Cancel Tournament In here We are not going to the dalete the record from the database we are just going to change the status of the tournament to canceled
    public function cancelTournament($tournamentId)
    {
        $result = $this->tournamentService
            ->cancelTournament((int) $tournamentId);

        header("Content-Type: application/json");

        echo json_encode($result);
    }
}

