<?php
/**
 * Database connection file.
 * Returns a PDO instance for database interactions.
 */
$config = [
    'host'    => 'localhost',        // MySQL host
    'dbname'  => 'takip_muhasebe',   // Database name
    'user'    => 'root',             // Database username
    'pass'    => '',                 // Database password
    'charset' => 'utf8mb4',          // Character set
];

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

return $pdo;
