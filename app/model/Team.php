<?php

require_once "User.php";

class Team extends User
{
    private $teamName;
    private $district;
    private $contactNumber;
    private $rating;

    // Constructor
    public function __construct(
        $userId = null,
        $email = null,
        $password = null,
        $role = null,
        $status = null,
        $profilePicture = null,
        $approvedBy = null,
        $approvedDate = null,
        $lastLogin = null,
        $createdAt = null,

        $teamName = null,
        $district = null,
        $contactNumber = null,
        $rating = null
    )
    {
        parent::__construct(
            $userId,
            $email,
            $password,
            $role,
            $status,
            $profilePicture,
            $approvedBy,
            $approvedDate,
            $lastLogin,
            $createdAt
        );

        $this->teamName = $teamName;
        $this->district = $district;
        $this->contactNumber = $contactNumber;
        $this->rating = $rating;
    }

    // Getters

    public function getTeamName()
    {
        return $this->teamName;
    }

    public function getDistrict()
    {
        return $this->district;
    }

    public function getContactNumber()
    {
        return $this->contactNumber;
    }

    public function getRating()
    {
        return $this->rating;
    }

    // Setters

    public function setTeamName($teamName)
    {
        $this->teamName = $teamName;
    }

    public function setDistrict($district)
    {
        $this->district = $district;
    }

    public function setContactNumber($contactNumber)
    {
        $this->contactNumber = $contactNumber;
    }

    public function setRating($rating)
    {
        $this->rating = $rating;
    }
}
