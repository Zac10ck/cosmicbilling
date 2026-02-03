<?php
/**
 * Logout
 * COSMIC SURGICALS - Invoice Management System
 */

require_once __DIR__ . '/../../includes/auth.php';

logoutUser();
header('Location: /xamp-cosmic/modules/auth/login.php');
exit;
