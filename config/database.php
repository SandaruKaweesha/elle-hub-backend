<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "ellehub-mysql.mysql.database.azure.com";
$dbname = "elle_hub";
$username = "ellehubadmin";
$password = "admin@4444";

$conn = mysqli_init();

mysqli_ssl_set(
    $conn,
    NULL,
    NULL,
    __DIR__ . "/../certs/DigiCertGlobalRootG2.crt.pem",
    NULL,
    NULL
);

mysqli_real_connect(
    $conn,
    $host,
    $username,
    $password,
    $dbname,
    3306,
    NULL,
    MYSQLI_CLIENT_SSL
);

if (!$conn) {
    die("Connection Failed");
}else{

    echo "Connection Successful ";
}
