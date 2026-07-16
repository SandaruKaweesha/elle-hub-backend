<?php

class TournamentTeamRequest
{
    private $tournamentId;
    private $teamUserId;
    private $requestDate;
    private $status;

    public function __construct(
        $tournamentId = null,
        $teamUserId = null,
        $requestDate = null,
        $status = null
    ) {
        $this->tournamentId = $tournamentId;
        $this->teamUserId = $teamUserId;
        $this->requestDate = $requestDate;
        $this->status = $status;
    }

    public function getTournamentId()
    {
        return $this->tournamentId;
    }

    public function setTournamentId($tournamentId)
    {
        $this->tournamentId = $tournamentId;
    }

    public function getTeamUserId()
    {
        return $this->teamUserId;
    }

    public function setTeamUserId($teamUserId)
    {
        $this->teamUserId = $teamUserId;
    }

    public function getRequestDate()
    {
        return $this->requestDate;
    }

    public function setRequestDate($requestDate)
    {
        $this->requestDate = $requestDate;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }
}
