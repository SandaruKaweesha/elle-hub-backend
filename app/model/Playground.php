<?php


require_once "User.php";

class Playground extends User
{
    private $playgroundName;
    private $location;
    private $address;
    private $contactNumber;
    private $capacity;

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
        $playgroundName = null,
        $location = null,
        $address = null,
        $contactNumber = null,
        $capacity = null
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

        $this->playgroundName = $playgroundName;
        $this->location = $location;
        $this->address = $address;
        $this->contactNumber = $contactNumber;
        $this->capacity = $capacity;
    }

    // Getters

    public function getPlaygroundName()
    {
        return $this->playgroundName;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getContactNumber()
    {
        return $this->contactNumber;
    }

    public function getCapacity()
    {
        return $this->capacity;
    }

    // Setters

    public function setPlaygroundName($playgroundName)
    {
        $this->playgroundName = $playgroundName;
    }

    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function setAddress($address)
    {
        $this->address = $address;
    }

    public function setContactNumber($contactNumber)
    {
        $this->contactNumber = $contactNumber;
    }

    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;
    }
}