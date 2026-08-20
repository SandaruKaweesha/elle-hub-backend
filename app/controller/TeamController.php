<?php

require_once __DIR__ . "/../service/TeamService.php";

class TeamController
{
    private TeamService $teamService;

    public function __construct()
    {
        $this->teamService = new TeamService();
    }

    public function getTeamRankings()
    {
        header("Content-Type: application/json");
        $result = $this->teamService->getTeamRankings();
        echo json_encode($result);
    }

    public function getTeamStats($userId)
    {
        header("Content-Type: application/json");
        $result = $this->teamService->getTeamStats((int) $userId);
        echo json_encode($result);
    }
}

