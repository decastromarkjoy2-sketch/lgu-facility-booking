<?php
// Retrieve database environment variables or fallback to local defaults
$host     = getenv('DB_HOST')     ?: 'localhost';
$port     = getenv('DB_PORT')     ?: '5432';
$dbname   = getenv('DB_NAME')     ?: 'postgres';
$user     = getenv('DB_USER')     ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'your_local_password';

try {
    // Pass options directly without single quotes so PDO includes it in the handshake
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};options=--dbname={$dbname};sslmode=require";
    
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>