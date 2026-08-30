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
                    t.points,
                    t.matches_played AS played,
                    t.wins AS won,
                    t.losses,
                    t.win_rate,
                    t.rank_position,
                    u.email,
                    u.profile_picture,
                    u.status AS user_status
                FROM teams t
                JOIN users u ON t.user_id = u.user_id
                ORDER BY 
                    CASE WHEN t.matches_played > 0 THEN 1 ELSE 2 END ASC,
                    t.points DESC,
                    t.rating DESC,
                    t.win_rate DESC,
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
                    t.points,
                    t.matches_played AS played,
                    t.wins AS won,
                    t.losses,
                    t.draws,
                    t.win_rate,
                    t.fair_play,
                    t.discipline,
                    t.rank_position,
                    t.tournaments_played,
                    t.tournaments_won
                FROM teams t
                WHERE t.user_id = :user_id";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":user_id", $userId, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return [
                'played' => 0,
                'won' => 0,
                'losses' => 0,
                'win_rate' => 0.0,
                'tournaments_played' => 0,
                'tournaments_won' => 0,
                'goal_progress' => 0,
                'points' => 0,
                'stars' => 0.0,
                'rating' => 0.0,
                'rank' => 0,
                'rank_position' => 0,
                'fair_play' => 0.0,
                'discipline' => 0.0,
                'reviews_count' => 0
            ];
        }

        $played = (int) ($result['played'] ?? 0);
        $won = (int) ($result['won'] ?? 0);
        $losses = (int) ($result['losses'] ?? 0);
        $winRate = (float) ($result['win_rate'] ?? 0.0);
        $rating = (float) ($result['rating'] ?? 0.0);
        $points = (int) ($result['points'] ?? 0);
        $rank = (int) ($result['rank_position'] ?? 0);
        $tournamentsPlayed = (int) ($result['tournaments_played'] ?? 0);
        $tournamentsWon = (int) ($result['tournaments_won'] ?? 0);

        $fairPlay = $played > 0 ? (float)($result['fair_play'] ?? 4.5) : 0.0;
        $discipline = $played > 0 ? (float)($result['discipline'] ?? 4.5) : 0.0;
        $reviewsCount = $played > 0 ? ($played * 3 + $won * 5) : 0;
        $goalProgress = $played > 0 ? min(100, (int) round(($played / 10) * 100)) : 0;

        return [
            'played' => $played,
            'won' => $won,
            'losses' => $losses,
            'win_rate' => $winRate,
            'tournaments_played' => $tournamentsPlayed,
            'tournaments_won' => $tournamentsWon,
            'goal_progress' => $goalProgress,
            'points' => $points,
            'stars' => $rating,
            'rating' => $rating,
            'rank' => $rank,
            'rank_position' => $rank,
            'fair_play' => $fairPlay,
            'discipline' => $discipline,
            'reviews_count' => $reviewsCount
        ];
    }
}
