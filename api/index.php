<?php
// api/index.php - Vercel Serverless Function Entry Point for HealthLogs

$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($rawUri, PHP_URL_PATH) ?: '/';

// Strip /HealthLogs and /public prefixes if they exist
$cleanPath = preg_replace('#^/HealthLogs#i', '', $path);
$cleanPath = preg_replace('#^/public#i', '', $cleanPath);
$cleanPath = '/' . ltrim($cleanPath, '/');

if ($cleanPath === '/' || $cleanPath === '') {
    $cleanPath = '/index.php';
}

$publicDir = realpath(__DIR__ . '/../public');
$targetFile = realpath($publicDir . $cleanPath);

// If file not found or is a directory, check with .php extension
if (!$targetFile || is_dir($targetFile)) {
    $withPhp = realpath($publicDir . $cleanPath . '.php');
    if ($withPhp && file_exists($withPhp)) {
        $targetFile = $withPhp;
    }
}

// Serve the file if it exists inside the public directory
if ($targetFile && strpos($targetFile, $publicDir) === 0 && file_exists($targetFile) && !is_dir($targetFile)) {
    chdir(dirname($targetFile));
    require $targetFile;
    exit;
}

// Default fallback to index.php
chdir($publicDir);
require $publicDir . '/index.php';
