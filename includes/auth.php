<?php
/**
 * Authentication Functions
 * COSMIC SURGICALS - Invoice Management System
 */

session_start();

require_once __DIR__ . '/../config/database.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role']
    ];
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /xamp-cosmic/modules/auth/login.php');
        exit;
    }
}

/**
 * Require user to be admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /xamp-cosmic/modules/invoices/index.php?error=access_denied');
        exit;
    }
}

/**
 * Login user
 */
function loginUser($username, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, password, name, role, active FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'Invalid username or password'];
    }

    if (!$user['active']) {
        return ['success' => false, 'error' => 'Account is disabled'];
    }

    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Invalid username or password'];
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];

    return ['success' => true, 'user' => $user];
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF input field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}
