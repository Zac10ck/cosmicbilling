<?php
/**
 * Invoice Search AJAX Endpoint
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
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$db = getDB();

$sql = "SELECT id, invoice_number, invoice_date, customer_name, grand_total
        FROM invoices
        WHERE invoice_date BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];

if (strlen($search) >= 2) {
    $sql .= " AND (invoice_number LIKE ? OR customer_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC LIMIT 20";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

echo json_encode($invoices);
