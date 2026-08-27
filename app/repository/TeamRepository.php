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

    public function getTeamRankings(): array
    {
        $sql = "SELECT 
                    t.user_id,
                    t.team_name,
                    t.district,
                    t.contact_number,
                    t.rating,
                    u.email,
                    u.profile_picture,
                    u.status AS user_status,
                    COALESCE(req_counts.played_count, 0) AS played,
                    (
                        COALESCE(win_results.won_count, 0) + 
                        COALESCE(win_draws.won_count, 0)
                    ) AS won
                FROM teams t
                JOIN users u ON t.user_id = u.user_id
                LEFT JOIN (
                    SELECT team_user_id, COUNT(DISTINCT tournament_id) AS played_count
                    FROM tournament_team_requests
                    WHERE UPPER(status) IN ('APPROVED', 'ACCEPTED', 'PENDING')
                    GROUP BY team_user_id
                ) req_counts ON t.user_id = req_counts.team_user_id
                LEFT JOIN (
                    SELECT recipient_team, COUNT(DISTINCT tournament_id) AS won_count
                    FROM tournament_results
                    WHERE UPPER(award_type) LIKE '%WINNER%' 
                       OR UPPER(award_type) LIKE '%CHAMPION%' 
                       OR UPPER(award_type) LIKE '%1ST%' 
                       OR UPPER(award_type) LIKE '%FIRST%'
                    GROUP BY recipient_team
                ) win_results ON LOWER(TRIM(t.team_name)) = LOWER(TRIM(win_results.recipient_team))
                LEFT JOIN (
                    SELECT 
                        LOWER(TRIM(
                            COALESCE(
                                NULLIF(JSON_UNQUOTE(JSON_EXTRACT(draw_data, '$.winner')), ''),
                                NULLIF(JSON_UNQUOTE(JSON_EXTRACT(draw_data, '$.bracketWinners.champion')), '')
                            )
                        )) AS winner_name,
                        COUNT(DISTINCT tournament_id) AS won_count
                    FROM tournaments
                    WHERE draw_data IS NOT NULL 
                      AND draw_data != ''
                    GROUP BY winner_name
                ) win_draws ON LOWER(TRIM(t.team_name)) = win_draws.winner_name
                ORDER BY 
                    CASE WHEN (COALESCE(req_counts.played_count, 0) > 0 OR (COALESCE(win_results.won_count, 0) + COALESCE(win_draws.won_count, 0)) > 0) THEN 1 ELSE 2 END ASC,
                    ((COALESCE(win_results.won_count, 0) + COALESCE(win_draws.won_count, 0)) * 100 + COALESCE(req_counts.played_count, 0) * 25 + COALESCE(t.rating, 0)) DESC, 
                    t.team_name ASC";

        $statement = $this->connection->prepare($sql);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTeamStats(int $userId): array
    {
        $sql = "SELECT 
                    t.user_id,
                    t.team_name,
                    t.rating,
                    COALESCE(req_counts.played_count, 0) AS played,
                    (
                        COALESCE(win_results.won_count, 0) + 
                        COALESCE(win_draws.won_count, 0)
                    ) AS won
                FROM teams t
                LEFT JOIN (
                    SELECT team_user_id, COUNT(DISTINCT tournament_id) AS played_count
                    FROM tournament_team_requests
                    WHERE UPPER(status) IN ('APPROVED', 'ACCEPTED', 'PENDING')
                    GROUP BY team_user_id
                ) req_counts ON t.user_id = req_counts.team_user_id
                LEFT JOIN (
                    SELECT recipient_team, COUNT(DISTINCT tournament_id) AS won_count
                    FROM tournament_results
                    WHERE UPPER(award_type) LIKE '%WINNER%' 
                       OR UPPER(award_type) LIKE '%CHAMPION%' 
                       OR UPPER(award_type) LIKE '%1ST%' 
                       OR UPPER(award_type) LIKE '%FIRST%'
                    GROUP BY recipient_team
                ) win_results ON LOWER(TRIM(t.team_name)) = LOWER(TRIM(win_results.recipient_team))
                LEFT JOIN (
                    SELECT 
                        LOWER(TRIM(
                            COALESCE(
                                NULLIF(JSON_UNQUOTE(JSON_EXTRACT(draw_data, '$.winner')), ''),
                                NULLIF(JSON_UNQUOTE(JSON_EXTRACT(draw_data, '$.bracketWinners.champion')), '')
                            )
                        )) AS winner_name,
                        COUNT(DISTINCT tournament_id) AS won_count
                    FROM tournaments
                    WHERE draw_data IS NOT NULL 
                      AND draw_data != ''
                    GROUP BY winner_name
                ) win_draws ON LOWER(TRIM(t.team_name)) = win_draws.winner_name
                WHERE t.user_id = :user_id";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":user_id", $userId, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        // Fallback if team row not found in teams table but user exists
        if (!$result) {
            $fallbackStmt = $this->connection->prepare("
                SELECT COUNT(DISTINCT tournament_id) AS played_count
                FROM tournament_team_requests
                WHERE team_user_id = :user_id
                  AND UPPER(status) IN ('APPROVED', 'ACCEPTED', 'PENDING')
            ");
            $fallbackStmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
            $fallbackStmt->execute();
            $fRes = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
            $played = (int) ($fRes['played_count'] ?? 0);
            return [
                'played' => $played,
                'won' => 0,
                'losses' => $played,
                'win_rate' => 0.0,
                'goal_progress' => $played > 0 ? min(100, (int) round(($played / 10) * 100)) : 0
            ];
        }

        $played = (int) ($result['played'] ?? 0);
        $won = (int) ($result['won'] ?? 0);
        $losses = max(0, $played - $won);
        $winRate = $played > 0 ? round(($won / $played) * 100, 1) : 0.0;
        
        $goalTarget = 10;
        $goalProgress = $played > 0 ? min(100, (int) round(($played / $goalTarget) * 100)) : 0;

        return [
            'played' => $played,
            'won' => $won,
            'losses' => $losses,
            'win_rate' => $winRate,
            'goal_progress' => $goalProgress
        ];
    }
}
