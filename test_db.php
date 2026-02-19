<?php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Default for Laragon
$db   = 'reembolsos';

echo "Trying to connect to MySQL at $host...\n";

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    echo "SUCCESS: Connected to MySQL server.\n";
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
    echo "SUCCESS: Database '$db' exists or created.\n";
    
} catch (PDOException $e) {
    echo "ERROR: Could not connect to MySQL. " . $e->getMessage() . "\n";
    echo "Please ensure Laragon MySQL is started.\n";
    exit(1);
}
