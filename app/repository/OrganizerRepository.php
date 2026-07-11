<?php

require_once __DIR__ . "/../../config/Database.php";
require_once __DIR__ . "/../model/Organizer.php";
require_once __DIR__ . "/../model/User.php";
class OrganizerRepository{
    private PDO $connection;

    public function __construct(){
        $this->connection = Database::getConnection();
    }

    public function save(Organizer $organizer): bool
    {
        $sql = "INSERT INTO organizers
                (user_id, organization_name, contact_number, address)
                VALUES
                (:user_id, :organization_name, :contact_number, :address)";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(
            ":user_id",
            $organizer->getUserId()
        );

        $statement->bindValue(
            ":organization_name",
            $organizer->getOrganizationName()
        );

        $statement->bindValue(
            ":contact_number",
            $organizer->getContactNumber()
        );

        $statement->bindValue(
            ":address",
            $organizer->getAddress()
        );

        return $statement->execute();
    }
}
