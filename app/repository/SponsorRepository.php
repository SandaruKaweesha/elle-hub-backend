<?php
require_once __DIR__ . "/../model/Sponsor.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../../config/Database.php";
class SponsorRepository{
    private PDO $connection;

    public function __construct(){
        $this->connection = Database::getConnection();
    }

    public function save(Sponsor $sponsor): bool
    {
        $sql = "INSERT INTO sponsors
                (user_id, company_name,contact_person ,contact_number, address)
                VALUES
                (:user_id, :company_name,:contact_person, :contact_number, :address)";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(
            ":user_id",
            $sponsor->getUserId()
        );

        $statement->bindValue(
            ":company_name",
            $sponsor->getCompanyName()
        );

        $statement->bindValue(
            ":contact_person",
            $sponsor->getContactPerson()
        );

        $statement->bindValue(
            ":contact_number",
            $sponsor->getContactNumber()
        );

        $statement->bindValue(
            ":address",
            $sponsor->getAddress()
        );

        return $statement->execute();
    }
}