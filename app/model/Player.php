<?php

class Player
{
    private $playerId;
    private $teamUserId;
    private $playerName;
    private $age;
    private $position;
    private $contactNumber;

    // Constructor
    public function __construct(
        $playerId = null,
        $teamUserId = null,
        $playerName = null,
        $age = null,
        $position = null,
        $contactNumber = null
    ) {
        $this->playerId = $playerId;
        $this->teamUserId = $teamUserId;
        $this->playerName = $playerName;
        $this->age = $age;
        $this->position = $position;
        $this->contactNumber = $contactNumber;
    }

    // Getters
    public function getPlayerId()
    {
        return $this->playerId;
    }

    public function getTeamUserId()
    {
        return $this->teamUserId;
    }

    public function getPlayerName()
    {
        return $this->playerName;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getContactNumber()
    {
        return $this->contactNumber;
    }

    // Setters
    public function setPlayerId($playerId)
    {
        $this->playerId = $playerId;
    }

    public function setTeamUserId($teamUserId)
    {
        $this->teamUserId = $teamUserId;
    }

    public function setPlayerName($playerName)
    {
        $this->playerName = $playerName;
    }

    public function setAge($age)
    {
        $this->age = $age;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function setContactNumber($contactNumber)
    {
        $this->contactNumber = $contactNumber;
    }
}
