<?php
require_once __DIR__ . "/../../config/Database.php";
require_once __DIR__ . "/../model/Playground.php";
require_once __DIR__ . "/../model/User.php";
class PlaygroundRepository {
    private PDO $connection;
    public function __construct()
    {
        $this->connection = Database::getConnection();
    }
    public function save(Playground $playground): bool
    {
        $sql = "INSERT INTO playgrounds
                (
                    user_id,
                    playground_name,
                    location,
                    address,
                    contact_number,
                    capacity
                )
                VALUES
                (
                    :user_id,
                    :playground_name,
                    :location,
                    :address,
                    :contact_number,
                    :capacity
                )";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(":user_id", $playground->getUserId());
        $statement->bindValue(":playground_name", $playground->getPlaygroundName());
        $statement->bindValue(":location", $playground->getLocation());
        $statement->bindValue(":address", $playground->getAddress());
        $statement->bindValue(":contact_number", $playground->getContactNumber());
        $statement->bindValue(":capacity", $playground->getCapacity());

        return $statement->execute();
    }
}