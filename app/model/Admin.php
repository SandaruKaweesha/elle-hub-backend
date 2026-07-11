<?php

namespace model;

require_once "User.php";

class Admin extends User
{
    private $fullName;

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

        $fullName = null
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
    }

    // Getter

    public function getFullName()
    {
        return $this->fullName;
    }

    // Setter

    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }
}