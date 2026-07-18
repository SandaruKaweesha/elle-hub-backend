<?php
require_once __DIR__ . "/../model/User.php";

require_once __DIR__ . "/../repository/AdminRepository.php";
require_once __DIR__ . "/../repository/OrganizerRepository.php";
require_once __DIR__ . "/../repository/UserRepository.php";
require_once __DIR__ . "/../repository/TeamRepository.php";
require_once __DIR__ . "/../repository/RefereeRepository.php";
require_once __DIR__ . "/../repository/SponsorRepository.php";
require_once __DIR__ . "/../repository/PlaygroundRepository.php";
class UserService{
    private $adminRepository=null;
    private $userRepository=null;
    private $teamRepository=null;
    private $organizerRepository=null;
    private $sponsorRepository=null;
    private $playgroundRepository=null;
    private $refereeRepository=null;
    
    public function __construct(){
        $this->adminRepository=new AdminRepository();
        $this->userRepository=new UserRepository();
        $this->teamRepository=new TeamRepository();
        $this->organizerRepository=new OrganizerRepository();
        $this->sponsorRepository=new SponsorRepository();
        $this->playgroundRepository=new PlaygroundRepository();
        $this->refereeRepository=new RefereeRepository();
    }

    public function registerUser(User $user)
    {
        // Check whether the email already exists
        if ($this->userRepository->existsByEmail($user->getEmail())) {
            return [
                "success" => false,
                "message" => "Email already exists."
            ];
        }

        // Set default values
        $user->setStatus("PENDING");

        // Hash the password
        $user->setPassword(
            password_hash(
                $user->getPassword(),
                PASSWORD_DEFAULT
            )
        );

        try {
            // Start Transaction
            Database::beginTransaction();
            // Save common user information
            $userId = $this->userRepository->save($user);

            // Set generated user ID
            $user->setUserId($userId);
            // Save role-specific information
            if ($user instanceof Admin) {
                $this->adminRepository->save($user);
            } elseif ($user instanceof Team) {
                $this->teamRepository->save($user);
            } elseif ($user instanceof Organizer) {
                $this->organizerRepository->save($user);
            } elseif ($user instanceof Sponsor) {
                $this->sponsorRepository->save($user);
            } elseif ($user instanceof Playground) {
                $this->playgroundRepository->save($user);
            } elseif ($user instanceof Referee) {
                $this->refereeRepository->save($user);

            }

            // Everything succeeded
            Database::commit();

            return [
                "success" => true,
                "message" => "Registration successful."
            ];

        } catch (Exception $e) {

            // Something failed, undo everything
            Database::rollback();

            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    public function getAllUsers(): array
    {
        $users = $this->userRepository->findAll();
        return [
            "success" => true,
            "message" => "Users retrieved successfully.",
            "data" => $users
        ];
    }

    public function getUserById(int $userId): array
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            return [
                "success" => false,
                "message" => "User not found."
            ];
        }

        return [
            "success" => true,
            "message" => "User retrieved successfully.",
            "data" => $user
        ];
    }


//    Delete the user
    public function deleteUser(int $userId): array
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            return [
                "success" => false,
                "message" => "User not found."
            ];
        }

        $deleted = $this->userRepository->deleteById($userId);

        if (!$deleted) {
            return [
                "success" => false,
                "message" => "Failed to delete user."
            ];
        }

        return [
            "success" => true,
            "message" => "User deleted successfully."
        ];
    }

    public function getUserStats(): array
    {
        $counts = $this->userRepository->getCountsByRole();
        
        return [
            "success" => true,
            "message" => "User stats retrieved successfully.",
            "data" => $counts
        ];
    }

    public function updateProfile(int $userId, string $role, array $data): array
    {
        $updated = $this->userRepository->updateProfile($userId, $role, $data);
        if ($updated) {
            return [
                "success" => true,
                "message" => "Profile updated successfully."
            ];
        }
        return [
            "success" => false,
            "message" => "Failed to update profile details."
        ];
    }

    public function updatePassword(int $userId, string $password): array
    {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $updated = $this->userRepository->updatePassword($userId, $hashed);
        if ($updated) {
            return [
                "success" => true,
                "message" => "Password updated successfully."
            ];
        }
        return [
            "success" => false,
            "message" => "Failed to update password."
        ];
    }

    public function updateUserStatus(int $userId, string $status): array
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            return [
                "success" => false,
                "message" => "User not found."
            ];
        }

        $updated = $this->userRepository->updateStatus($userId, $status);
        if ($updated) {
            return [
                "success" => true,
                "message" => "User status updated to {$status} successfully."
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to update user status."
        ];
    }
}
