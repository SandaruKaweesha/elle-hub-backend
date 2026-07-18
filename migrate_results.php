<?php
require_once __DIR__ . '/config/Database.php';

try {
    $db = Database::getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS tournament_results (
        result_id INT AUTO_INCREMENT PRIMARY KEY,
        tournament_id INT NOT NULL,
        award_type VARCHAR(50) NOT NULL,
        recipient_name VARCHAR(255) NOT NULL,
        recipient_team VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (tournament_id) REFERENCES tournaments(tournament_id) ON DELETE CASCADE
    )";
    
    $db->exec($sql);
    echo "Table tournament_results created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
