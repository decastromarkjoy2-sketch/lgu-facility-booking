<?php
// Retrieve database environment variables
$database_url = getenv('DATABASE_URL');

try {
    if ($database_url) {
        $parsed = parse_url($database_url);
        $host   = $parsed['host'] ?? 'aws-0-ap-northeast-2.pooler.supabase.com';
        $port   = $parsed['port'] ?? 6543;
        $user   = $parsed['user'] ?? 'postgres.bdamxhkaqbyeemteclxl';
        $pass   = $parsed['pass'] ?? 'AHrBngaowKuuKXk4';
        $dbname = ltrim($parsed['path'] ?? '/postgres', '/');

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        $host     = getenv('DB_HOST')     ?: 'localhost';
        $port     = getenv('DB_PORT')     ?: '6543';
        $dbname   = getenv('DB_NAME')     ?: 'postgres';
        $user     = getenv('DB_USER')     ?: 'postgres';
        $password = getenv('DB_PASSWORD') ?: 'your_local_password';

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>