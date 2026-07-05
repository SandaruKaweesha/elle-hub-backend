<?php

require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../service/UserService.php";
class UserController{
    private  $userService;
    public function __construct(){
        $this->userService = new UserService();
    }
    public function registerUser(){
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $user = new User();

        $user->setEmail($requestObject->email);
        $user->setPassword($requestObject->password);
        $user->setRole($requestObject->role);
        $result=$this->userService->registerUser($user);

        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }
}
