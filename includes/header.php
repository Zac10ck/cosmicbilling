<?php
/**
 * Header Template
 * COSMIC SURGICALS - Invoice Management System
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

requireLogin();

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentModule = basename(dirname($_SERVER['PHP_SELF']));

// Get company info
global $company;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#3157d5">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/xamp-cosmic/manifest.webmanifest">
    <link rel="icon" href="/xamp-cosmic/assets/icons/icon.svg" type="image/svg+xml">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - ' : ''; ?>COSMIC SURGICALS</title>
    <link rel="stylesheet" href="/xamp-cosmic/assets/css/style.css?v=2.0.2">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="/xamp-cosmic/">COSMIC SURGICALS</a>
        </div>
        <button class="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false">☰</button>
        <div class="nav-menu" id="mainNav">
            <?php if (isAdmin()): ?>
                <a href="/xamp-cosmic/modules/dashboard/index.php" class="<?php echo $currentModule === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
            <?php endif; ?>
            <a href="/xamp-cosmic/modules/invoices/index.php" class="<?php echo $currentModule === 'invoices' ? 'active' : ''; ?>">Invoices</a>
            <a href="/xamp-cosmic/modules/customers/index.php" class="<?php echo $currentModule === 'customers' ? 'active' : ''; ?>">Customers</a>
            <a href="/xamp-cosmic/modules/inventory/index.php" class="<?php echo $currentModule === 'inventory' || $currentModule === 'products' ? 'active' : ''; ?>">Inventory</a>
            <?php if (isAdmin()): ?>
                <a href="/xamp-cosmic/modules/reports/index.php" class="<?php echo $currentModule === 'reports' ? 'active' : ''; ?>">Reports</a>
                <a href="/xamp-cosmic/modules/users/index.php" class="<?php echo $currentModule === 'users' ? 'active' : ''; ?>">Users</a>
            <?php endif; ?>
        </div>
        <div class="nav-user">
            <span class="user-name"><?php echo e($currentUser['name']); ?></span>
            <span class="user-role">(<?php echo ucfirst($currentUser['role']); ?>)</span>
            <a href="/xamp-cosmic/modules/auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <main class="main-content">
        <?php
        $flash = getFlash();
        if ($flash):
        ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo e($flash['message']); ?>
        </div>
        <?php endif; ?>
