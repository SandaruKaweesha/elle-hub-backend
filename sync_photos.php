<?php
require_once __DIR__ . '/config/Database.php';

echo "====================================================\n";
echo "   SYSTEM PHOTOS FOLDER-TO-DATABASE SYNC TOOL       \n";
echo "====================================================\n\n";

$frontendImagesDir = "C:/xampp/htdocs/elle-hub-frontend/public/images";

if (!is_dir($frontendImagesDir)) {
    echo "ERROR: Directory not found: $frontendImagesDir\n";
    exit(1);
}

try {
    $db = Database::getConnection();
    
    // Ensure system_photos table exists
    $db->exec("CREATE TABLE IF NOT EXISTS system_photos (
        photo_id INT AUTO_INCREMENT PRIMARY KEY,
        photo_name VARCHAR(100) NOT NULL,
        image_url LONGTEXT NOT NULL,
        category VARCHAR(50) DEFAULT 'TOURNAMENT_COVER',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Option: Wipe old photo entries to reflect exact folder contents
    $db->exec("DELETE FROM system_photos WHERE category = 'TOURNAMENT_COVER'");
    echo " -> Cleared previous entries from 'system_photos' table.\n";

    $dirFiles = scandir($frontendImagesDir);
    $insertedCount = 0;

    foreach ($dirFiles as $fileName) {
        if ($fileName === '.' || $fileName === '..') continue;
        
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) continue;

        // Skip non-cover UI mockups if desired
        if (in_array($fileName, ['create-tournament.png', 'generate-certificates.png', 'manage-fixtures.png', 'track-rankings.png', 'update-results.png'])) {
            continue;
        }

        $webPath = "/images/" . $fileName;

        $insertStmt = $db->prepare("INSERT INTO system_photos (photo_name, image_url, category) VALUES (:photo_name, :image_url, 'TOURNAMENT_COVER')");
        $insertStmt->bindValue(":photo_name", ucfirst(pathinfo($fileName, PATHINFO_FILENAME)));
        $insertStmt->bindValue(":image_url", $webPath);
        $insertStmt->execute();
        
        echo " [ADDED TO DB] -> $fileName (Web Path: $webPath)\n";
        $insertedCount++;
    }

    echo "\n----------------------------------------------------\n";
    echo "SYNC COMPLETED SUCCESSFUL!\n";
    echo " -> $insertedCount active developer photo(s) are now stored in 'system_photos' DB table.\n";
    echo " -> All tournament cards will now cycle exclusively through your custom folder photos!\n";
    echo "----------------------------------------------------\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
