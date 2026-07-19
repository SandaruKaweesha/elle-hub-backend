<?php
require_once __DIR__ . '/../model/TournamentResult.php';
require_once __DIR__ . '/../../config/Database.php';

class TournamentResultRepository {
    private PDO $connection;

    public function __construct() {
        $this->connection = Database::getConnection();
    }

    public function saveMultiple(int $tournamentId, array $results): bool {
        try {
            $this->connection->beginTransaction();

            // First, delete existing results for this tournament to overwrite
            $deleteSql = "DELETE FROM tournament_results WHERE tournament_id = :tournament_id";
            $deleteStmt = $this->connection->prepare($deleteSql);
            $deleteStmt->bindValue(":tournament_id", $tournamentId, PDO::PARAM_INT);
            $deleteStmt->execute();

            $insertSql = "INSERT INTO tournament_results (tournament_id, award_type, recipient_name, recipient_team, created_at)
                          VALUES (:tournament_id, :award_type, :recipient_name, :recipient_team, NOW())";
            $insertStmt = $this->connection->prepare($insertSql);

            foreach ($results as $result) {
                if (empty($result['awardType']) || empty($result['recipientName'])) continue;
                
                $insertStmt->bindValue(":tournament_id", $tournamentId, PDO::PARAM_INT);
                $insertStmt->bindValue(":award_type", $result['awardType']);
                $insertStmt->bindValue(":recipient_name", $result['recipientName']);
                $insertStmt->bindValue(":recipient_team", $result['recipientTeam'] ?? null);
                $insertStmt->execute();
            }

            $this->connection->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            error_log("Error saving tournament results: " . $e->getMessage());
            return false;
        }
    }

    public function findByTournamentId(int $tournamentId): array {
        $sql = "SELECT * FROM tournament_results WHERE tournament_id = :tournament_id ORDER BY result_id ASC";
        $statement = $this->connection->prepare($sql);
        $statement->bindValue(":tournament_id", $tournamentId, PDO::PARAM_INT);
        $statement->execute();
        
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $results = [];

        foreach ($rows as $row) {
            $result = new TournamentResult(
                $row['result_id'],
                $row['tournament_id'],
                $row['award_type'],
                $row['recipient_name'],
                $row['recipient_team'],
                $row['created_at']
            );
            $results[] = $result;
        }

        return $results;
    }
}
