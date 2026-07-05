<?php

namespace model;

require_once "User.php";

class Referee extends User
{
    private $fullName;
    private $experienceYears;
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

        $fullName = null,
        $experienceYears = null,
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

        $this->fullName = $fullName;
        $this->experienceYears = $experienceYears;
        $this->contactNumber = $contactNumber;
        $this->rating = $rating;
    }

    // Getters

    public function getFullName()
    {
        return $this->fullName;
    }

    public function getExperienceYears()
    {
        return $this->experienceYears;
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

    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }

    public function setExperienceYears($experienceYears)
    {
        $this->experienceYears = $experienceYears;
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