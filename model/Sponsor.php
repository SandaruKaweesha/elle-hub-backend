<?php

require_once "User.php";

class Sponsor extends User
{
    private $companyName;
    private $contactPerson;
    private $address;
    private $contactNumber;

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

        $companyName = null,
        $contactPerson = null,
        $address = null,
        $contactNumber = null
    ) {
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

        $this->companyName = $companyName;
        $this->contactPerson = $contactPerson;
        $this->address = $address;
        $this->contactNumber = $contactNumber;
    }

    // Getters

    public function getCompanyName()
    {
        return $this->companyName;
    }

    public function getContactPerson()
    {
        return $this->contactPerson;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getContactNumber()
    {
        return $this->contactNumber;
    }

    // Setters

    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;
    }

    public function setContactPerson($contactPerson)
    {
        $this->contactPerson = $contactPerson;
    }

    public function setAddress($address)
    {
        $this->address = $address;
    }

    public function setContactNumber($contactNumber)
    {
        $this->contactNumber = $contactNumber;
    }
}