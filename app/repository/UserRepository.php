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

    public function save(User $user):int{
        $sql = "INSERT INTO users (email, password, role, status)
            VALUES (:email, :password, :role, :status)";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(":email", $user->getEmail());
        $statement->bindValue(":password", $user->getPassword());
        $statement->bindValue(":role", $user->getRole());
        $statement->bindValue(":status", $user->getStatus());

        $statement->execute();

        return (int) $this->connection->lastInsertId();
    }
//    Check the Email is exsiting
    public function findByEmail(string $email): ?User
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":email", $email);
        $statement->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row == false) {
            return null;
        }
        $user = new User();
        $user->setUserId($row["user_id"]);
        $user->setEmail($row["email"]);
        $user->setPassword($row["password"]); // hashed password
        $user->setRole($row["role"]);
        $user->setStatus($row["status"]);
        $user->setProfilePicture($row["profile_picture"]);
        $user->setApprovedBy($row["approved_by"]);
        $user->setApprovedDate($row["approved_date"]);
        $user->setLastLogin($row["last_login"]);
        $user->setCreatedAt($row["created_at"]);

        return $user;
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM users";
        $statement = $this->connection->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }


    public function findById(int $userId): ?array
    {
        $sql = "SELECT * FROM users WHERE user_id = :user_id";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(
            ":user_id",
            $userId,
            PDO::PARAM_INT
        );

        $statement->execute();

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        // Fetch role specific data
        $role = $user['role'] ?? null;
        if ($role) {
            $tableName = null;
            if ($role === 'ORGANIZER') $tableName = 'organizers';
            else if ($role === 'REFEREE') $tableName = 'referees';
            else if ($role === 'TEAM') $tableName = 'teams';
            else if ($role === 'SPONSOR') $tableName = 'sponsors';
            else if ($role === 'PLAYGROUND') $tableName = 'playgrounds';

            if ($tableName) {
                $roleSql = "SELECT * FROM {$tableName} WHERE user_id = :user_id";
                $roleStatement = $this->connection->prepare($roleSql);
                $roleStatement->bindValue(":user_id", $userId, PDO::PARAM_INT);
                $roleStatement->execute();
                $roleData = $roleStatement->fetch(PDO::FETCH_ASSOC);
                
                if ($roleData) {
                    $user = array_merge($user, $roleData);
                    
                    // Map snake_case to camelCase for frontend compatibility
                    if (isset($roleData['organization_name'])) $user['organizationName'] = $roleData['organization_name'];
                    if (isset($roleData['full_name'])) $user['fullName'] = $roleData['full_name'];
                    if (isset($roleData['company_name'])) $user['companyName'] = $roleData['company_name'];
                    if (isset($roleData['team_name'])) $user['teamName'] = $roleData['team_name'];
                    if (isset($roleData['playground_name'])) $user['playgroundName'] = $roleData['playground_name'];
                }
            }
        }

        return $user;
    }

//    Delete the user
    public function deleteById(int $userId): bool
    {
        $sql = "DELETE FROM users
            WHERE user_id = :user_id";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(
            ":user_id",
            $userId,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->rowCount() > 0;
    }
}
