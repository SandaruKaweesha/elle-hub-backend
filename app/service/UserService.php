<?php
require_once __DIR__ . "/../model/User.php";

require_once __DIR__ . "/../repository/OrganizerRepository.php";
require_once __DIR__ . "/../repository/UserRepository.php";
require_once __DIR__ . "/../repository/TeamRepository.php";
require_once __DIR__ . "/../repository/RefereeRepository.php";
require_once __DIR__ . "/../repository/SponsorRepository.php";
require_once __DIR__ . "/../repository/PlaygroundRepository.php";
class UserService{
    private $userRepository=null;
    private $teamRepository=null;
    private $organizerRepository=null;
    private $sponsorRepository=null;
    private $playgroundRepository=null;
    private $refereeRepository=null;
    public function __construct(){
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
            if ($user instanceof Team) {
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


}
