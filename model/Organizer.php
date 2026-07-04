<?php

require_once "User.php";

class Organizer extends User
{
    private $organizationName;
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

        $organizationName = null,
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

        $this->organizationName = $organizationName;
        $this->address = $address;
        $this->contactNumber = $contactNumber;
    }

    // Getters

    public function getOrganizationName()
    {
        return $this->organizationName;
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

    public function setOrganizationName($organizationName)
    {
        $this->organizationName = $organizationName;
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