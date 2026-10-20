<?php
// PDO connection supporting local XAMPP and Cloud databases (TiDB / Aiven / etc.)

$host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? getenv('DB_PORT') ?: '4000';
$dbname = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? getenv('DB_NAME') ?: 'healthlogs';
$user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$pass = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? getenv('DB_PASS');
if ($pass === false || $pass === null) {
    $pass = '';
}
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Configure SSL for Cloud databases (like TiDB Cloud)
if ($host !== '127.0.0.1' && $host !== 'localhost') {
    $caBundlePaths = [
        '/etc/pki/tls/certs/ca-bundle.crt',      // Amazon Linux / RHEL (Vercel)
        '/etc/ssl/certs/ca-certificates.crt',  // Debian / Ubuntu
        '/etc/ssl/cert.pem',                   // Alpine / macOS
    ];

    $caFound = false;
    foreach ($caBundlePaths as $caPath) {
        if (file_exists($caPath)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
            $caFound = true;
            break;
        }
    }

    if (!$caFound) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database connection failed: ' . $e->getMessage());
    echo 'Database connection failed: ' . htmlspecialchars($e->getMessage());
    exit;
}
