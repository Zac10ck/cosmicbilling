<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$error = '';
$db = null;

// Database connection
try {
    require_once __DIR__ . '/../../config/database.php';
    $db = getDB();
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Already logged in?
if ($db && isset($_SESSION['user_id'])) {
    header('Location: /xamp-cosmic/modules/invoices/index.php');
    exit;
}

// Handle login
if ($db && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: /xamp-cosmic/modules/invoices/index.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - COSMIC SURGICALS</title>
</head>
<body style="font-family: Arial; background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0;">
    <div style="background: white; padding: 40px; border-radius: 16px; width: 350px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <h1 style="text-align: center; margin: 0 0 5px 0; color: #333;">COSMIC SURGICALS</h1>
        <p style="text-align: center; color: #666; margin: 0 0 30px 0;">Invoice Management System</p>

        <?php if ($error): ?>
        <p style="background: #fee; color: #c33; padding: 10px; border-radius: 5px;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <p>
                <label style="font-weight: bold;">Username:</label><br>
                <input type="text" name="username" required style="width: 100%; padding: 12px; border: 2px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 16px;">
            </p>
            <p>
                <label style="font-weight: bold;">Password:</label><br>
                <input type="password" name="password" required style="width: 100%; padding: 12px; border: 2px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 16px;">
            </p>
            <p>
                <button type="submit" style="width: 100%; padding: 14px; background: #667eea; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer;">Login</button>
            </p>
        </form>

        <p style="text-align: center; color: #999; font-size: 12px; margin-top: 20px;">
            Default: admin / admin123
        </p>
    </div>
</body>
</html>
