<?php
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../repository/UserRepository.php";
class UserService{
    private $userRepository=null;

    public function __construct(){
        $this->userRepository=new UserRepository();
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
        $this->userRepository->save($user);

        // 5. Return a response
        return [
            "success" => true,
            "message" => "Registration successful."
        ];
    }
}
