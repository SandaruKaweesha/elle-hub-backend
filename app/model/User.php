<?php
class User{
    private $userId;
    private $email;
    private $password;
    private $role;
    private $status;
    private $profilePicture;
    private $approvedBy;
    private $approvedDate;
    private $lastLogin;
    private $createdAt;

    // Default Constructor
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
        $createdAt = null
    )
    {
        $this->userId = $userId;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->status = $status;
        $this->profilePicture = $profilePicture;
        $this->approvedBy = $approvedBy;
        $this->approvedDate = $approvedDate;
        $this->lastLogin = $lastLogin;
        $this->createdAt = $createdAt;
    }


    // Getters

    public function getUserId()
    {
        return $this->userId;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getProfilePicture()
    {
        return $this->profilePicture;
    }

    public function getApprovedBy()
    {
        return $this->approvedBy;
    }

    public function getApprovedDate()
    {
        return $this->approvedDate;
    }

    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    // Setters

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setProfilePicture($profilePicture)
    {
        $this->profilePicture = $profilePicture;
    }

    public function setApprovedBy($approvedBy)
    {
        $this->approvedBy = $approvedBy;
    }

    public function setApprovedDate($approvedDate)
    {
        $this->approvedDate = $approvedDate;
    }

    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
}