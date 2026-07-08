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


    public function registerUser(User $user){
        if ($this->userRepository->existsByEmail($user->getEmail())) {
            return [
                "success" => false,
                "message" => "Email already exists."
            ];
        }

        $user->setStatus("PENDING");
        $user->setPassword(password_hash(
            $user->getPassword(),
            PASSWORD_DEFAULT
        ));
        $userId = $this->userRepository->save($user);
        //Temapary Code
        //echo "Generated User ID: ";
        //var_dump($userId);


        $user->setUserId($userId);
        //Temapary Code
        //echo "<br>User ID inside Team object: ";
//        var_dump($user->getUserId());

        if ($user instanceof Team) {
            $this->teamRepository->save($user);
        }elseif ($user instanceof Organizer) {
            $this->organizerRepository->save($user);
        }elseif ($user instanceof Sponsor) {
            $this->sponsorRepository->save($user);
        }elseif ($user instanceof Playground) {
            $this->playgroundRepository->save($user);
        }elseif ($user instanceof Referee) {
            $this->refereeRepository->save($user);
        }

        // 5. Return a response
        return [
            "success" => true,
            "message" => "Registration successful."
        ];
    }
}
