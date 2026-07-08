<?php


require_once __DIR__ . "/../model/Team.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../../config/Database.php";
class TeamRepository{
    private PDO $connection;

    public function __construct(){
        $this->connection = Database::getConnection();
    }
    public function save(Team $team):bool{
        $sql = "INSERT INTO teams 
                (user_id, team_name, district, contact_number, rating)
                VALUES 
                (:user_id, :team_name, :district, :contact_number, :rating)";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(":user_id", $team->getUserId());
        $statement->bindValue(":team_name", $team->getTeamName());
        $statement->bindValue(":district", $team->getDistrict());
        $statement->bindValue(":contact_number", $team->getContactNumber());
        $statement->bindValue(":rating", $team->getRating());

        return $statement->execute();
    }



}