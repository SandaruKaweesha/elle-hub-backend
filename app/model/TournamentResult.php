<?php
class TournamentResult {
    private $resultId;
    private $tournamentId;
    private $awardType;
    private $recipientName;
    private $recipientTeam;
    private $createdAt;

    public function __construct(
        $resultId = null,
        $tournamentId = null,
        $awardType = null,
        $recipientName = null,
        $recipientTeam = null,
        $createdAt = null
    ) {
        $this->resultId = $resultId;
        $this->tournamentId = $tournamentId;
        $this->awardType = $awardType;
        $this->recipientName = $recipientName;
        $this->recipientTeam = $recipientTeam;
        $this->createdAt = $createdAt;
    }

    public function getResultId() { return $this->resultId; }
    public function getTournamentId() { return $this->tournamentId; }
    public function getAwardType() { return $this->awardType; }
    public function getRecipientName() { return $this->recipientName; }
    public function getRecipientTeam() { return $this->recipientTeam; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setResultId($resultId) { $this->resultId = $resultId; }
    public function setTournamentId($tournamentId) { $this->tournamentId = $tournamentId; }
    public function setAwardType($awardType) { $this->awardType = $awardType; }
    public function setRecipientName($recipientName) { $this->recipientName = $recipientName; }
    public function setRecipientTeam($recipientTeam) { $this->recipientTeam = $recipientTeam; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }
}
