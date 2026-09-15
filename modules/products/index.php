<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
header('Location: /xamp-cosmic/modules/inventory/index.php');
exit;
