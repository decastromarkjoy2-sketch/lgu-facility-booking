<?php
// Retrieve database environment variables
$host     = getenv('DB_HOST')     ?: 'aws-0-ap-northeast-2.pooler.supabase.com';
$port     = getenv('DB_PORT')     ?: '5432';
$dbname   = getenv('DB_NAME')     ?: 'postgres';
$user     = getenv('DB_USER')     ?: 'postgres.bdamxhkaqbyeemteclxl';
$password = getenv('DB_PASSWORD') ?: 'AHrBngaowKuuKXk4';

try {
    // Sanitize user: ensure no trailing '.postgres' suffix remains
    if (str_ends_with($user, '.postgres')) {
        $user = substr($user, 0, -9);
    }

    // Force option --dbname in driver options array for PDO pgsql
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Pass the tenant options string natively to PostgreSQL client driver
        PDO::PGSQL_ATTR_DISABLE_PREPARES => true,
    ];

    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>