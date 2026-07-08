<?php

require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../model/Team.php";
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

//               this going to the user
                $user = new Team();
                $user->setEmail($requestObject->email);
                $user->setPassword($requestObject->password);
                $user->setRole($requestObject->role);

//              this going ot the team
                $user->setTeamName($requestObject->teamName);
                $user->setDistrict($requestObject->district);
                $user->setContactNumber($requestObject->contactNumber);
        }

        $result=$this->userService->registerUser($user);

        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }
}
