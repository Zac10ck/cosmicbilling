<?php
/**
 * Customer Search AJAX Endpoint
 * COSMIC SURGICALS - Invoice Management System
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$search = $_GET['q'] ?? '';

if (strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

$customers = getCustomers($search);

// Format for autocomplete
$results = array_map(function($c) {
    return [
        'id' => $c['id'],
        'name' => $c['name'],
        'phone' => $c['phone'],
        'gstin' => $c['gstin'],
        'state_code' => $c['state_code'],
        'address' => $c['address']
    ];
}, $customers);

echo json_encode(array_slice($results, 0, 10));
