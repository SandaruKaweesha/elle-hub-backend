<?php
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../core/JWT.php';
require_once __DIR__ . '/../../config/Config.php';

class AuthService
{
    private $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return [
                "success" => false,
                "message" => "Invalid email or password"
            ];
        }

        if (!password_verify($password, $user->getPassword())) {
            return [
                "success" => false,
                "message" => "Invalid email or password"
            ];
        }

        // Generate Token
        $payload = [
            'userId' => $user->getUserId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'iat' => time(),
            'exp' => time() + (86400 * 7) // 7 days expiration
        ];

        $token = JWT::encode($payload, Config::JWT_SECRET);

        return [
            "success" => true,
            "message" => "Login successful",
            "token" => $token,
            "user" => [
                "userId" => $user->getUserId(),
                "email" => $user->getEmail(),
                "role" => $user->getRole(),
                "status" => $user->getStatus()
            ]
        ];
    }
}
