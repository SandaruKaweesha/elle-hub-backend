<?php
require_once __DIR__ . '/../service/AuthService.php';

class AuthController
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function login()
    {
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        if (!isset($requestObject->email) || !isset($requestObject->password)) {
            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Email and password are required"]);
            return;
        }

        $result = $this->authService->login($requestObject->email, $requestObject->password);

        if ($result["success"]) {
            http_response_code(200);
        } else {
            http_response_code(401);
        }

        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function logout()
    {
        // JWT is stateless, so we just instruct the client to delete it.
        http_response_code(200);
        header("Content-Type: application/json");
        echo json_encode([
            "success" => true, 
            "message" => "Logged out successfully. Please clear your token on the client side."
        ]);
    }
}
