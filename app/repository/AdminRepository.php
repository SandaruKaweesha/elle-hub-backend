<?php

require_once __DIR__ . "/../model/Admin.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../../config/Database.php";

class AdminRepository {
    private PDO $connection;

    public function __construct(){
        $this->connection = Database::getConnection();
    }
    
    public function save(Admin $admin): bool {
        $sql = "INSERT INTO admins (user_id, full_name) VALUES (:user_id, :full_name)";
        $statement = $this->connection->prepare($sql);
        
        $statement->bindValue(":user_id", $admin->getUserId());
        $statement->bindValue(":full_name", $admin->getFullName());
        
        return $statement->execute();
    }
}
