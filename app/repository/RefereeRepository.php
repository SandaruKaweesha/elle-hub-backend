<?php

require_once __DIR__ . "/../../config/Database.php";
require_once __DIR__ . "/../model/Referee.php";
require_once __DIR__ . "/../model/User.php";
class RefereeRepository{
    private PDO $connection;

    public function __construct(){
        $this->connection = Database::getConnection();
    }
    public function save(Referee $referee): bool
    {
        $sql = "INSERT INTO referees
                (
                    user_id,
                    full_name,
                    experience_years,
                    contact_number,
                    rating
                )
                VALUES
                (
                    :user_id,
                    :full_name,
                    :experience_years,
                    :contact_number,
                    :rating
                )";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(":user_id", $referee->getUserId());
        $statement->bindValue(":full_name", $referee->getFullName());
        $statement->bindValue(":experience_years", $referee->getExperienceYears());
        $statement->bindValue(":contact_number", $referee->getContactNumber());
        $statement->bindValue(":rating", $referee->getRating());

        return $statement->execute();
    }
}