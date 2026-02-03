<?php
/**
 * Database Configuration
 * COSMIC SURGICALS - Invoice Management System
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'cosmic_billing');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP has no password

// PDO Connection
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}

// Company Details
$company = [
    'name' => 'COSMIC SURGICALS',
    'address' => 'Vayalat Building, Chalunkalpady, Puthuppally P.O.',
    'city' => 'Kottayam, Kerala - 686011',
    'phone' => '0481-2569733, 7592092140',
    'email' => 'cosmicsurgical@gmail.com',
    'website' => 'www.cosmicsurgical.com',
    'gstin' => '32AFTPM3704R1ZV',
    'state_code' => '32',
    'bank_name' => 'STATE BANK OF INDIA',
    'bank_account' => '37778761693',
    'bank_ifsc' => 'SBIN0070122'
];
