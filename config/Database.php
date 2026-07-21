<?php

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {

            // Local MySQL 8 connection
            $host = "127.0.0.1";
            $port = "3306";
            $database = "ellehub"; // Change if your local DB has a different name
            $username = "root";
            $password = "1234";

            $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";

            $options = [
                PDO::ATTR_TIMEOUT => 15,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            try {

                self::$connection = new PDO(
                    $dsn,
                    $username,
                    $password,
                    $options
                );
            } catch (PDOException $e) {

                http_response_code(500);
                header('Content-Type: application/json');

                echo json_encode([
                    "success" => false,
                    "message" => "Database Connection Failed: " . $e->getMessage()
                ]);

                exit;
            }
        }

        return self::$connection;
    }

    public static function beginTransaction()
    {
        self::getConnection()->beginTransaction();
    }

    public static function commit()
    {
        self::getConnection()->commit();
    }

    public static function rollback()
    {
        self::getConnection()->rollBack();
    }
}
