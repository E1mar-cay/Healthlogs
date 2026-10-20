<?php
// api/index.php - Vercel Serverless Function Entry Point for HealthLogs

$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($rawUri, PHP_URL_PATH) ?: '/';

// Normalize URI: strip /HealthLogs and /public prefixes if present
$cleanPath = preg_replace('#^/HealthLogs#i', '', $path);
$cleanPath = preg_replace('#^/public#i', '', $cleanPath);
$cleanPath = '/' . ltrim($cleanPath, '/');

if ($cleanPath === '/' || $cleanPath === '') {
    $cleanPath = '/index.php';
}

$publicDir = dirname(__DIR__) . '/public';
$targetFile = $publicDir . $cleanPath;

// If target is directory or missing, try appending .php
if (is_dir($targetFile) || !file_exists($targetFile)) {
    if (file_exists($targetFile . '.php')) {
        $targetFile = $targetFile . '.php';
    } elseif (file_exists(rtrim($targetFile, '/') . '/index.php')) {
        $targetFile = rtrim($targetFile, '/') . '/index.php';
    }
}

// Ensure the target file exists and is inside the project
if (file_exists($targetFile) && !is_dir($targetFile)) {
    chdir(dirname($targetFile));
    require $targetFile;
    exit;
}

// Fallback to public/index.php
chdir($publicDir);
require $publicDir . '/index.php';
