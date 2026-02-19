<?php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Default for Laragon
$db   = 'reembolsos';

echo "Waiting for MySQL database at $host:3306...\n";

$max_retries = 30; // 60 seconds
$attempt = 0;

while ($attempt < $max_retries) {
    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Try to create the database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
        
        // Connect to the specific database
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        
        echo "\nSuccessfully connected to the database!\nRunning migrations...\n";
        passthru('php artisan migrate:fresh --seed');
        echo "Migrations completed.\n";
        exit(0);
        
    } catch (PDOException $e) {
        echo ".";
        sleep(2);
        $attempt++;
    }
}

echo "\nCould not connect to MySQL after 60 seconds. Please ensure Laragon MySQL is running.\n";
exit(1);
