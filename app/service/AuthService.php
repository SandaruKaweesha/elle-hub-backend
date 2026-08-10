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

        $role = $user->getRole();
        if (empty($role)) {
            $emailLower = strtolower($user->getEmail());
            if (str_contains($emailLower, 'admin')) {
                $role = 'ADMIN';
            } else if (str_contains($emailLower, 'organizer')) {
                $role = 'ORGANIZER';
            } else if (str_contains($emailLower, 'referee')) {
                $role = 'REFEREE';
            } else if (str_contains($emailLower, 'playground') || str_contains($emailLower, 'ground')) {
                $role = 'PLAYGROUND';
            } else if (str_contains($emailLower, 'sponsor')) {
                $role = 'SPONSOR';
            } else {
                $role = 'TEAM';
            }
        }

        // Generate Token
        $payload = [
            'userId' => $user->getUserId(),
            'email' => $user->getEmail(),
            'role' => $role,
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
                "user_id" => $user->getUserId(),
                "id" => $user->getUserId(),
                "email" => $user->getEmail(),
                "role" => $role,
                "status" => $user->getStatus(),
                "profilePicture" => $user->getProfilePicture(),
                "profile_picture" => $user->getProfilePicture()
            ]
        ];
    }
}
