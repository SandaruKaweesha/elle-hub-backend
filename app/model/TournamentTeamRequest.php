<?php

class TournamentTeamRequest
{
    private $tournamentId;
    private $teamUserId;
    private $requestDate;
    private $status;
    private $initiatedBy;

    public function __construct(
        $tournamentId = null,
        $teamUserId = null,
        $requestDate = null,
        $status = null,
        $initiatedBy = 'TEAM'
    ) {
        $this->tournamentId = $tournamentId;
        $this->teamUserId = $teamUserId;
        $this->requestDate = $requestDate;
        $this->status = $status;
        $this->initiatedBy = $initiatedBy;
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

    public function getInitiatedBy()
    {
        return $this->initiatedBy;
    }

    public function setInitiatedBy($initiatedBy)
    {
        $this->initiatedBy = $initiatedBy;
    }
}
