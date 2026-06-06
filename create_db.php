<?php
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$db = 'pharmacy_lms';

try {
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully\n";
} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
