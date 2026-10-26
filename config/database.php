<?php
// Retrieve database environment variables or fallback to defaults (supporting both DB_ and POSTGRES_ prefixes)
$host     = getenv('DB_HOST')     ?: (getenv('POSTGRES_HOST')     ?: 'aws-0-ap-northeast-2.pooler.supabase.com');
$port     = getenv('DB_PORT')     ?: (getenv('POSTGRES_PORT')     ?: '5432');
$dbname   = getenv('DB_NAME')     ?: (getenv('POSTGRES_DATABASE') ?: 'postgres');
$user     = getenv('DB_USER')     ?: (getenv('POSTGRES_USER')     ?: 'postgres.bdamxhkaqxbyeemtecxl');
$password = getenv('DB_PASSWORD') ?: (getenv('POSTGRES_PASSWORD') ?: 'AHrBngaowKuuKXk4');

// Ensure user does not end with duplicate suffixes
if (str_ends_with($user, '.postgres')) {
    $user = substr($user, 0, -9);
}

try {
    // Crucial: options=--dbname= specifies the tenant routing flag directly to Supavisor
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};options=--dbname={$dbname};sslmode=require";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>