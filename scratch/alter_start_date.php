<?php
require_once "c:/xampp/htdocs/elle-hub-backend/config/Database.php";

try {
    $conn = Database::getConnection();
    $sql = "ALTER TABLE tournaments MODIFY COLUMN start_date DATE NULL";
    $conn->exec($sql);
    echo "Successfully altered tournaments table: start_date column is now nullable.\n";
} catch (Exception $e) {
    echo "Alteration failed: " . $e->getMessage() . "\n";
}
