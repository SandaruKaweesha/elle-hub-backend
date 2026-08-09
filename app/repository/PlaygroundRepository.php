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
                    located_district,
                    location,
                    address,
                    contact_number,
                    area
                )
                VALUES
                (
                    :user_id,
                    :playground_name,
                    :located_district,
                    :location,
                    :address,
                    :contact_number,
                    :area
                )";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(":user_id", $playground->getUserId());
        $statement->bindValue(":playground_name", $playground->getPlaygroundName());
        $statement->bindValue(":located_district", $playground->getLocatedDistrict() ?: $playground->getLocation() ?: 'General');
        $statement->bindValue(":location", $playground->getLocation() ?: 'N/A');
        $statement->bindValue(":address", $playground->getAddress() ?: 'N/A');
        $statement->bindValue(":contact_number", $playground->getContactNumber() ?: 'N/A');
        $statement->bindValue(":area", $playground->getArea() ?: '500 Sq. Ft');

        return $statement->execute();
    }

}