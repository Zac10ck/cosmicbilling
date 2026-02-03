<?php
/**
 * Product Search AJAX Endpoint
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

$products = getProducts($search);

// Format for autocomplete
$results = array_map(function($p) {
    return [
        'id' => $p['id'],
        'name' => $p['name'],
        'hsn_code' => $p['hsn_code'],
        'gst_rate' => $p['gst_rate'],
        'mrp' => $p['mrp'],
        'unit' => $p['unit']
    ];
}, $products);

echo json_encode(array_slice($results, 0, 10));
