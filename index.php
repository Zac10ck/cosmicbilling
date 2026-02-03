<?php
/**
 * Entry Point
 * COSMIC SURGICALS - Invoice Management System
 */

// Check if database is configured
$configFile = __DIR__ . '/config/database.php';
$dbConfigured = false;

if (file_exists($configFile)) {
    require_once $configFile;
    try {
        $testDsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $testPdo = new PDO($testDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $dbConfigured = true;
    } catch (PDOException $e) {
        $dbConfigured = false;
    }
}

if (!$dbConfigured) {
    header('Location: /xamp-cosmic/install.php');
    exit;
}

require_once __DIR__ . '/includes/auth.php';

// Redirect based on login status
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: /xamp-cosmic/modules/dashboard/index.php');
    } else {
        header('Location: /xamp-cosmic/modules/invoices/index.php');
    }
} else {
    header('Location: /xamp-cosmic/modules/auth/login.php');
}
exit;
