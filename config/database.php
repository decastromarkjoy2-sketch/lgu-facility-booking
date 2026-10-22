<?php
// config/database.php

$host = getenv('POSTGRES_HOST') ?: 'db.bdamxhkaqxbyeemtecxl.supabase.co'; // Your Supabase Host
$db   = getenv('POSTGRES_DATABASE') ?: 'postgres';
$user = getenv('POSTGRES_USER') ?: 'postgres';
$pass = getenv('POSTGRES_PASSWORD') ?: 'YOUR_SUPABASE_PASSWORD'; // Fallback local password
$port = getenv('POSTGRES_PORT') ?: '5432';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>