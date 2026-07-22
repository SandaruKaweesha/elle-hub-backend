<?php

require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../model/Admin.php";
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
            case "ADMIN":
                $user = new Admin();
                $user->setFullName($requestObject->fullName);
                break;

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
                $user->setArea($requestObject->area ?? $requestObject->capacity ?? '500 Sq. Ft');
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

        echo json_encode($result);
    }

//Get All users
    public function getAllUsers()
    {
        $result = $this->userService->getAllUsers();

        header("Content-Type: application/json");

        echo json_encode($result);
    }


//    Search by the id
    public function getUserById($userId)
    {
        $result = $this->userService->getUserById((int) $userId);

        header("Content-Type: application/json");

        echo json_encode($result);
    }

//Delete user by the id
    public function deleteUser($userId)
    {
        $result = $this->userService->deleteUser((int) $userId);

        header("Content-Type: application/json");

        if ($result["success"]) {
            http_response_code(200);
        } else {
            http_response_code(404);
        }

        echo json_encode($result);
    }

    public function getStats()
    {
        $result = $this->userService->getUserStats();
        header("Content-Type: application/json");
        http_response_code(200);
        echo json_encode($result);
    }

    public function updateUser()
    {
        require_once __DIR__ . "/../core/AuthMiddleware.php";
        $payload = AuthMiddleware::authenticate();
        $userId = $payload['userId'];
        $role = $payload['role'];

        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody, true);

        if (!$requestObject) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid request body"]);
            return;
        }

        $result = $this->userService->updateProfile($userId, $role, $requestObject);
        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function updatePassword()
    {
        require_once __DIR__ . "/../core/AuthMiddleware.php";
        $payload = AuthMiddleware::authenticate();
        $userId = $payload['userId'];

        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        if (!isset($requestObject->password) || empty($requestObject->password)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Password is required"]);
            return;
        }

        $result = $this->userService->updatePassword($userId, $requestObject->password);
        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function approveUser($userId)
    {
        require_once __DIR__ . "/../core/AuthMiddleware.php";
        AuthMiddleware::requireRole(['ADMIN']);

        $result = $this->userService->updateUserStatus((int) $userId, 'APPROVED');

        header("Content-Type: application/json");
        if ($result["success"]) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        echo json_encode($result);
    }

    public function requestDeletion()
    {
        require_once __DIR__ . "/../core/AuthMiddleware.php";
        $payload = AuthMiddleware::authenticate();
        $userId = (int)$payload['userId'];

        $result = $this->userService->updateUserStatus($userId, 'DELETION_PENDING');

        header("Content-Type: application/json");
        if ($result["success"]) {
            http_response_code(200);
            $result['message'] = "Account deletion request submitted successfully.";
        } else {
            http_response_code(400);
        }

        echo json_encode($result);
    }
}
