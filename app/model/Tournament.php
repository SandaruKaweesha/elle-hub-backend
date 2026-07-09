<?php

class Tournament
{
    private $tournamentId;
    private $organizerId;
    private $title;
    private $description;
    private $location;
    private $startDate;
    private $endDate;
    private $maximumTeamLimit;
    private $rules;
    private $prizeDetails;
    private $status;
    private $approvalStatus;
    private $approvedBy;
    private $approvedDate;
    private $createdAt;

    // Constructor
    public function __construct(
        $tournamentId = null,
        $organizerId = null,
        $title = null,
        $description = null,
        $location = null,
        $startDate = null,
        $endDate = null,
        $maximumTeamLimit = null,
        $rules = null,
        $prizeDetails = null,
        $status = null,
        $approvalStatus = null,
        $approvedBy = null,
        $approvedDate = null,
        $createdAt = null
    )
    {

        $this->tournamentId = $tournamentId;
        $this->organizerId = $organizerId;
        $this->title = $title;
        $this->description = $description;
        $this->location = $location;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->maximumTeamLimit = $maximumTeamLimit;
        $this->rules = $rules;
        $this->prizeDetails = $prizeDetails;
        $this->status = $status;
        $this->approvalStatus = $approvalStatus;
        $this->approvedBy = $approvedBy;
        $this->approvedDate = $approvedDate;
        $this->createdAt = $createdAt;
    }

    // Getters

    public function getTournamentId()
    {
        return $this->tournamentId;
    }

    public function getOrganizerId()
    {
        return $this->organizerId;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function getEndDate()
    {
        return $this->endDate;
    }

    public function getMaximumTeamLimit()
    {
        return $this->maximumTeamLimit;
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function getPrizeDetails()
    {
        return $this->prizeDetails;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getApprovalStatus()
    {
        return $this->approvalStatus;
    }

    public function getApprovedBy()
    {
        return $this->approvedBy;
    }

    public function getApprovedDate()
    {
        return $this->approvedDate;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    // Setters

    public function setTournamentId($tournamentId)
    {
        $this->tournamentId = $tournamentId;
    }

    public function setOrganizerId($organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    public function setMaximumTeamLimit($maximumTeamLimit)
    {
        $this->maximumTeamLimit = $maximumTeamLimit;
    }

    public function setRules($rules)
    {
        $this->rules = $rules;
    }

    public function setPrizeDetails($prizeDetails)
    {
        $this->prizeDetails = $prizeDetails;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setApprovalStatus($approvalStatus)
    {
        $this->approvalStatus = $approvalStatus;
    }

    public function setApprovedBy($approvedBy)
    {
        $this->approvedBy = $approvedBy;
    }

    public function setApprovedDate($approvedDate)
    {
        $this->approvedDate = $approvedDate;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
}