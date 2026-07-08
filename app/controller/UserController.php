<?php

require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../model/Team.php";
require_once __DIR__ . "/../model/Sponsor.php";

require_once __DIR__ . "/../model/Referee.php";
require_once __DIR__ . "/../model/Organizer.php";
require_once __DIR__ . "/../model/Playground.php";
require_once __DIR__ . "/../service/UserService.php";
class UserController{
    private  $userService;
    public function __construct(){
        $this->userService = new UserService();
    }
    public function registerUser(){
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        switch ($requestObject->role){
            case "TEAM":
                $user = new Team();
//              this going ot the team
                $user->setTeamName($requestObject->teamName);
                $user->setDistrict($requestObject->district);
                $user->setContactNumber($requestObject->contactNumber);

                break;

            case  "ORGANIZER":
                $user = new Organizer();
//              This going to the Organizer
                $user->setOrganizationName($requestObject->organizationName);
                $user->setContactNumber($requestObject->contactNumber);
                $user->setAddress($requestObject->address);
                break;

            case "SPONSOR":
                $user = new Sponsor();
                $user->setCompanyName($requestObject->companyName);
                $user->setContactPerson($requestObject->contactPerson);
                $user->setContactNumber($requestObject->contactNumber);
                $user->setAddress($requestObject->address);
                break;

            case "PLAYGROUND":
                $user = new Playground();
                $user->setPlaygroundName($requestObject->playgroundName);
                $user->setLocation($requestObject->location);
                $user->setAddress($requestObject->address);
                $user->setContactNumber($requestObject->contactNumber);
                $user->setCapacity($requestObject->capacity);
                break;

            case "REFEREE":
                $user = new Referee();
                $user->setFullName($requestObject->fullName);
                $user->setExperienceYears($requestObject->experienceYears);
                $user->setContactNumber($requestObject->contactNumber);
                $user->setRating($requestObject->rating);
                break;

            default:
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid role"
                ]);
                return;

        }

        $user->setEmail($requestObject->email);
        $user->setPassword($requestObject->password);
        $user->setRole($requestObject->role);

        $result=$this->userService->registerUser($user);

        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }
}
