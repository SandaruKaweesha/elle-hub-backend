<?php
require_once __DIR__ . '/JWT.php';
require_once __DIR__ . '/../../config/Config.php';

class AuthMiddleware
{
    /**
     * Extracts the JWT token from the Authorization header and verifies it.
     * Returns the decoded payload if valid, otherwise responds with 401 and exits.
     */
    public static function authenticate(): array
    {
        $headers = self::getAuthorizationHeader();
        
        if (!$headers || !preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            self::unauthorized("Token not provided");
        }

        $token = $matches[1];
        $payload = JWT::decode($token, Config::JWT_SECRET);

        if (!$payload) {
            self::unauthorized("Invalid or expired token");
        }

        return $payload;
    }

    /**
     * Authenticates and checks if the user has one of the allowed roles.
     * Returns the payload if successful, otherwise responds with 401/403 and exits.
     */
    public static function requireRole(array $allowedRoles): array
    {
        $payload = self::authenticate();

        if (!isset($payload['role']) || !in_array($payload['role'], $allowedRoles)) {
            self::forbidden("Access denied: You do not have the required role");
        }

        return $payload;
    }

    /**
     * Helper to get authorization header across different server environments
     */
    private static function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    private static function unauthorized(string $message)
    {
        http_response_code(401);
        header("Content-Type: application/json");
        echo json_encode(["success" => false, "message" => $message]);
        exit();
    }

    private static function forbidden(string $message)
    {
        http_response_code(403);
        header("Content-Type: application/json");
        echo json_encode(["success" => false, "message" => $message]);
        exit();
    }
}
