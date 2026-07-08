<?php
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../../config/Database.php";
class UserRepository{
    private PDO $connection;


    public  function __construct(){
        $this->connection = Database::getConnection();
    }
    public function existsByEmail(string $email): bool  {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(":email", $email);
        $statement->execute();
        $count = $statement->fetchColumn();

        return $count > 0;
    }

    public function save(User $user){
        $sql = "INSERT INTO users (email, password, role, status)
                VALUES (:email, :password, :role, :status)";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(":email", $user->getEmail());
        $statement->bindValue(":password", $user->getPassword());
        $statement->bindValue(":role", $user->getRole());
        $statement->bindValue(":status", $user->getStatus());

        return $statement->execute();
    }
}
