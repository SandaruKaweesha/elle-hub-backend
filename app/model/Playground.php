<?php


require_once "User.php";

class Playground extends User
{
    private $playgroundName;
    private $locatedDistrict;
    private $location;
    private $address;
    private $contactNumber;
    private $area;

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
        $locatedDistrict = null,
        $location = null,
        $address = null,
        $contactNumber = null,
        $area = null
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
        $this->locatedDistrict = $locatedDistrict;
        $this->location = $location;
        $this->address = $address;
        $this->contactNumber = $contactNumber;
        $this->area = $area;
    }

    // Getters

    public function getPlaygroundName()
    {
        return $this->playgroundName;
    }

    public function getLocatedDistrict()
    {
        return $this->locatedDistrict;
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

    public function getArea()
    {
        return $this->area;
    }

    // Setters

    public function setPlaygroundName($playgroundName)
    {
        $this->playgroundName = $playgroundName;
    }

    public function setLocatedDistrict($locatedDistrict)
    {
        $this->locatedDistrict = $locatedDistrict;
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

    public function setArea($area)
    {
        $this->area = $area;
    }
}