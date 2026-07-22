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
        $sql = "SELECT u.*,
                       a.full_name AS admin_name,
                       t.team_name, t.rating, t.district,
                       o.organization_name,
                       s.company_name, s.contact_person AS sponsor_contact_person, s.address AS sponsor_address,
                       p.playground_name, p.located_district, p.location AS playground_location, p.address AS playground_address, p.area AS playground_area, p.area AS area, p.capacity AS capacity,
                       r.full_name AS referee_name, r.experience_years, r.rating AS referee_rating, r.availability_status AS referee_availability_status,
                       COALESCE(t.contact_number, o.contact_number, s.contact_number, p.contact_number, r.contact_number) AS contact_number,
                       COALESCE(t.district, p.located_district, 'Sri Lanka') AS district
                FROM users u
                LEFT JOIN admins a ON u.user_id = a.user_id
                LEFT JOIN teams t ON u.user_id = t.user_id
                LEFT JOIN organizers o ON u.user_id = o.user_id
                LEFT JOIN sponsors s ON u.user_id = s.user_id
                LEFT JOIN playgrounds p ON u.user_id = p.user_id
                LEFT JOIN referees r ON u.user_id = r.user_id
                ORDER BY u.created_at DESC";

        $statement = $this->connection->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $role = $row['role'];
            if ($role === 'ADMIN' && !empty($row['admin_name'])) {
                $row['display_name'] = $row['admin_name'];
            } elseif ($role === 'TEAM' && !empty($row['team_name'])) {
                $row['display_name'] = $row['team_name'];
            } elseif ($role === 'ORGANIZER' && !empty($row['organization_name'])) {
                $row['display_name'] = $row['organization_name'];
            } elseif ($role === 'SPONSOR' && !empty($row['company_name'])) {
                $row['display_name'] = $row['company_name'];
            } elseif ($role === 'PLAYGROUND' && !empty($row['playground_name'])) {
                $row['display_name'] = $row['playground_name'];
            } elseif ($role === 'REFEREE' && !empty($row['referee_name'])) {
                $row['display_name'] = $row['referee_name'];
            } else {
                $parts = explode('@', $row['email']);
                $row['display_name'] = ucfirst($parts[0]);
            }
        }

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

                if ($role === 'TEAM') {
                    $playerSql = "SELECT * FROM players WHERE team_user_id = :user_id";
                    $playerStatement = $this->connection->prepare($playerSql);
                    $playerStatement->bindValue(":user_id", $userId, PDO::PARAM_INT);
                    $playerStatement->execute();
                    $players = $playerStatement->fetchAll(PDO::FETCH_ASSOC);
                    $user['players'] = $players;
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

    public function getCountsByRole(): array
    {
        $sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
        $statement = $this->connection->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $counts = [
            'TOTAL' => 0,
            'TEAM' => 0,
            'REFEREE' => 0,
            'SPONSOR' => 0,
            'PLAYGROUND' => 0,
            'ORGANIZER' => 0,
            'ADMIN' => 0
        ];

        foreach ($rows as $row) {
            $role = strtoupper($row['role']);
            $count = (int)$row['count'];
            $counts[$role] = $count;
            $counts['TOTAL'] += $count;
        }

        return $counts;
    }

    public function updateProfile(int $userId, string $role, array $data): bool
    {
        try {
            $role = strtoupper($role);
            if ($role === 'TEAM') {
                $sql = "UPDATE teams 
                        SET team_name = :team_name, 
                            address = :address, 
                            district = :district,
                            contact_number = :contact_number,
                            description = :description
                        WHERE user_id = :user_id";
                $statement = $this->connection->prepare($sql);
                $statement->bindValue(':team_name', $data['teamName'] ?? $data['team_name'] ?? null);
                $statement->bindValue(':address', $data['address'] ?? null);
                $statement->bindValue(':district', $data['district'] ?? null);
                $statement->bindValue(':contact_number', $data['contactNumber'] ?? $data['contact_number'] ?? null);
                $statement->bindValue(':description', $data['description'] ?? null);
                $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $statement->execute();
                return true;
            } elseif ($role === 'ORGANIZER') {
                $sql = "UPDATE organizers 
                        SET organization_name = :organization_name, 
                            address = :address, 
                            contact_number = :contact_number
                        WHERE user_id = :user_id";
                $statement = $this->connection->prepare($sql);
                $statement->bindValue(':organization_name', $data['organizationName'] ?? $data['organization_name'] ?? null);
                $statement->bindValue(':address', $data['address'] ?? null);
                $statement->bindValue(':contact_number', $data['contactNumber'] ?? $data['contact_number'] ?? null);
                $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $statement->execute();
                return true;
            } elseif ($role === 'SPONSOR') {
                $sql = "UPDATE sponsors 
                        SET company_name = :company_name, 
                            contact_person = :contact_person,
                            address = :address, 
                            contact_number = :contact_number
                        WHERE user_id = :user_id";
                $statement = $this->connection->prepare($sql);
                $statement->bindValue(':company_name', $data['companyName'] ?? $data['company_name'] ?? null);
                $statement->bindValue(':contact_person', $data['contactPerson'] ?? $data['contact_person'] ?? null);
                $statement->bindValue(':address', $data['address'] ?? null);
                $statement->bindValue(':contact_number', $data['contactNumber'] ?? $data['contact_number'] ?? null);
                $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $statement->execute();
                return true;
            } elseif ($role === 'REFEREE') {
                $sql = "UPDATE referees 
                        SET full_name = :full_name, 
                            experience_years = :experience_years, 
                            contact_number = :contact_number,
                            availability_status = :availability_status
                        WHERE user_id = :user_id";
                $statement = $this->connection->prepare($sql);
                $statement->bindValue(':full_name', $data['fullName'] ?? $data['full_name'] ?? null);
                $statement->bindValue(':experience_years', $data['experienceYears'] ?? $data['experience_years'] ?? null);
                $statement->bindValue(':contact_number', $data['contactNumber'] ?? $data['contact_number'] ?? null);
                $statement->bindValue(':availability_status', $data['availabilityStatus'] ?? $data['availability_status'] ?? 'AVAILABLE');
                $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $statement->execute();
                return true;
            } elseif ($role === 'PLAYGROUND') {
                $sql = "UPDATE playgrounds 
                        SET playground_name = :playground_name, 
                            located_district = :located_district,
                            location = :location,
                            address = :address, 
                            contact_number = :contact_number,
                            area = :area
                        WHERE user_id = :user_id";
                $statement = $this->connection->prepare($sql);
                $statement->bindValue(':playground_name', $data['playgroundName'] ?? $data['playground_name'] ?? null);
                $statement->bindValue(':located_district', $data['locatedDistrict'] ?? $data['located_district'] ?? null);
                $statement->bindValue(':location', $data['location'] ?? null);
                $statement->bindValue(':address', $data['address'] ?? null);
                $statement->bindValue(':contact_number', $data['contactNumber'] ?? $data['contact_number'] ?? null);
                $statement->bindValue(':area', $data['area'] ?? $data['capacity'] ?? null);
                $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $statement->execute();
                return true;
            } elseif ($role === 'ADMIN') {
                $sql = "UPDATE admins SET full_name = :full_name WHERE user_id = :user_id";
                $statement = $this->connection->prepare($sql);
                $statement->bindValue(':full_name', $data['fullName'] ?? $data['full_name'] ?? null);
                $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $statement->execute();
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error in updateProfile: " . $e->getMessage());
            return false;
        }
    }

    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':password', $hashedPassword);
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->rowCount() > 0;
    }

    public function updateStatus(int $userId, string $status): bool
    {
        $sql = "UPDATE users SET status = :status WHERE user_id = :user_id";
        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':status', $status);
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->rowCount() > 0;
    }
}
