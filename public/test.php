<?php

require_once "../config/Database.php";

try {

    $connection = Database::getConnection();

    echo "✅ Database Connected Successfully";

} catch (Exception $e) {

    echo $e->getMessage();

}