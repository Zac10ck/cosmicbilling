<?php
/**
 * Database Installation Script
 * COSMIC SURGICALS - Invoice Management System
 *
 * Run this once to set up the database and tables.
 * Access via: http://localhost/xamp-cosmic/install.php
 */

$host = 'localhost';
$user = 'root';
$pass = $_POST['db_password'] ?? '';
$submitted = isset($_POST['db_password']);

$messages = [];
$errors = [];
$connected = false;

// Only try to connect if form was submitted or password is empty (first try)
if ($submitted || !isset($_POST['db_password'])) {
    // Create database connection without database selected
    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $messages[] = "Connected to MySQL server.";
        $connected = true;
    } catch (PDOException $e) {
        if (!$submitted) {
            // First attempt with empty password failed, show form
            $errors[] = "MySQL requires a password. Please enter your MySQL root password below.";
        } else {
            $errors[] = "Connection failed: " . $e->getMessage();
        }
    }
}

if ($connected) {
    // Save password to config file
    $configContent = '<?php
/**
 * Database Configuration
 * COSMIC SURGICALS - Invoice Management System
 */

define(\'DB_HOST\', \'localhost\');
define(\'DB_NAME\', \'cosmic_billing\');
define(\'DB_USER\', \'root\');
define(\'DB_PASS\', \'' . addslashes($pass) . '\');

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
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}

// Company Details
$company = [
    \'name\' => \'COSMIC SURGICALS\',
    \'address\' => \'Vayalat Building, Chalunkalpady, Puthuppally P.O.\',
    \'city\' => \'Kottayam, Kerala - 686011\',
    \'phone\' => \'0481-2569733, 7592092140\',
    \'email\' => \'cosmicsurgical@gmail.com\',
    \'website\' => \'www.cosmicsurgical.com\',
    \'gstin\' => \'32AFTPM3704R1ZV\',
    \'state_code\' => \'32\',
    \'bank_name\' => \'STATE BANK OF INDIA\',
    \'bank_account\' => \'37778761693\',
    \'bank_ifsc\' => \'SBIN0070122\'
];
';
    file_put_contents(__DIR__ . '/config/database.php', $configContent);
    $messages[] = "Database configuration saved.";

    // Create database
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS cosmic_billing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $messages[] = "Database 'cosmic_billing' created or already exists.";
        $pdo->exec("USE cosmic_billing");
    } catch (PDOException $e) {
        $errors[] = "Database creation failed: " . $e->getMessage();
        $connected = false;
    }
}

if ($connected && empty($errors)) {
    // Create tables
    $tables = [
        // Users table
        "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            role ENUM('admin', 'staff') DEFAULT 'staff',
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // Customers table
        "CREATE TABLE IF NOT EXISTS customers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(200) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(100),
            gstin VARCHAR(15),
            address TEXT,
            state_code VARCHAR(2) DEFAULT '32',
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // Products table
        "CREATE TABLE IF NOT EXISTS products (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(200) NOT NULL,
            hsn_code VARCHAR(20),
            barcode VARCHAR(100) UNIQUE,
            image_path VARCHAR(255),
            gst_rate DECIMAL(5,2) DEFAULT 5.00,
            mrp DECIMAL(10,2),
            unit VARCHAR(20) DEFAULT 'Nos',
            stock_count DECIMAL(12,2) NOT NULL DEFAULT 0,
            low_stock_threshold DECIMAL(12,2) NOT NULL DEFAULT 5,
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // Invoices table
        "CREATE TABLE IF NOT EXISTS invoices (
            id INT PRIMARY KEY AUTO_INCREMENT,
            invoice_number VARCHAR(50) UNIQUE NOT NULL,
            invoice_date DATE NOT NULL,
            invoice_type ENUM('cash', 'credit') DEFAULT 'cash',
            copy_type ENUM('original', 'duplicate', 'triplicate') DEFAULT 'original',

            customer_id INT NULL,
            customer_name VARCHAR(200) NOT NULL,
            customer_phone VARCHAR(20),
            customer_gstin VARCHAR(15),
            customer_state_code VARCHAR(2) DEFAULT '32',

            price_mode ENUM('exclusive', 'inclusive') DEFAULT 'exclusive',
            gst_type ENUM('cgst_sgst', 'igst') DEFAULT 'cgst_sgst',

            subtotal DECIMAL(12,2) DEFAULT 0,
            total_cgst DECIMAL(12,2) DEFAULT 0,
            total_sgst DECIMAL(12,2) DEFAULT 0,
            total_igst DECIMAL(12,2) DEFAULT 0,
            total_tax DECIMAL(12,2) DEFAULT 0,
            grand_total DECIMAL(12,2) DEFAULT 0,

            amount_in_words VARCHAR(500),
            drug_license VARCHAR(200),
            notes TEXT,

            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX idx_invoice_date (invoice_date),
            INDEX idx_customer_id (customer_id),
            INDEX idx_created_by (created_by)
        )",

        // Invoice items table
        "CREATE TABLE IF NOT EXISTS invoice_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            invoice_id INT NOT NULL,
            product_id INT NULL,

            description VARCHAR(300) NOT NULL,
            hsn_code VARCHAR(20),
            batch_no VARCHAR(50),
            expiry_date VARCHAR(10),

            quantity DECIMAL(10,2) NOT NULL,
            mrp DECIMAL(10,2) NOT NULL,
            gst_rate DECIMAL(5,2) DEFAULT 5.00,

            taxable_amount DECIMAL(12,2),
            cgst_amount DECIMAL(12,2) DEFAULT 0,
            sgst_amount DECIMAL(12,2) DEFAULT 0,
            igst_amount DECIMAL(12,2) DEFAULT 0,
            total_amount DECIMAL(12,2),

            INDEX idx_invoice_id (invoice_id),
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        )",

        // Immutable inventory audit trail
        "CREATE TABLE IF NOT EXISTS stock_movements (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            product_id INT NOT NULL,
            movement_type VARCHAR(30) NOT NULL,
            quantity_change DECIMAL(12,2) NOT NULL DEFAULT 0,
            stock_before DECIMAL(12,2) NOT NULL DEFAULT 0,
            stock_after DECIMAL(12,2) NOT NULL DEFAULT 0,
            reference_type VARCHAR(30),
            reference_id INT,
            notes VARCHAR(500),
            details TEXT,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_stock_product_date (product_id, created_at),
            INDEX idx_stock_reference (reference_type, reference_id),
            INDEX idx_stock_created_by (created_by),
            FOREIGN KEY (product_id) REFERENCES products(id),
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )"
    ];

    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            $errors[] = "Table creation failed: " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        $messages[] = "All tables created successfully.";
    }
}

// Create default admin user
if ($connected && empty($errors)) {
    try {
        // Check if admin exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        if ($stmt->fetchColumn() == 0) {
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (username, password, name, role) VALUES ('admin', '$adminPassword', 'Administrator', 'admin')");
            $messages[] = "Default admin user created (username: admin, password: admin123)";
        } else {
            $messages[] = "Admin user already exists.";
        }

        // Check if staff exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'staff'");
        if ($stmt->fetchColumn() == 0) {
            $staffPassword = password_hash('staff123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (username, password, name, role) VALUES ('staff', '$staffPassword', 'Staff User', 'staff')");
            $messages[] = "Default staff user created (username: staff, password: staff123)";
        } else {
            $messages[] = "Staff user already exists.";
        }
    } catch (PDOException $e) {
        $errors[] = "User creation failed: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - COSMIC SURGICALS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        h2 {
            color: #666;
            font-weight: normal;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .message {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .credentials {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .credentials h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        .credentials table {
            width: 100%;
            font-size: 14px;
        }
        .credentials td {
            padding: 5px 10px;
        }
        .credentials td:first-child {
            font-weight: 600;
            width: 100px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group small {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>COSMIC SURGICALS</h1>
        <h2>Invoice Management System - Installation</h2>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!$connected): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="db_password">MySQL Root Password</label>
                    <input type="password" id="db_password" name="db_password" placeholder="Enter MySQL root password">
                    <small>Leave empty if MySQL has no password set</small>
                </div>
                <button type="submit" class="btn">Connect & Install</button>
            </form>
        <?php else: ?>
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message success"><?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (empty($errors)): ?>
                <div class="credentials">
                    <h3>Default Login Credentials</h3>
                    <table>
                        <tr>
                            <td>Admin:</td>
                            <td>admin / admin123</td>
                        </tr>
                        <tr>
                            <td>Staff:</td>
                            <td>staff / staff123</td>
                        </tr>
                    </table>
                </div>

                <a href="index.php" class="btn">Go to Application</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
