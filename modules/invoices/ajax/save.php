<?php
/**
 * Save Invoice AJAX Endpoint
 * COSMIC SURGICALS - Invoice Management System
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/mailer.php';
require_once __DIR__ . '/../../../includes/inventory.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Read JSON input
$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

// Verify CSRF
if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Invalid form submission']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    // Prepare invoice data
    $invoice = [
        'invoice_number' => $data['invoice_number'] ?? generateInvoiceNumber(),
        'invoice_date' => $data['invoice_date'] ?? date('Y-m-d'),
        'invoice_type' => $data['invoice_type'] ?? 'cash',
        'copy_type' => $data['copy_type'] ?? 'original',
        'customer_id' => $data['customer_id'] ?: null,
        'customer_name' => trim($data['customer_name'] ?? 'Walk-in Customer'),
        'customer_phone' => trim($data['customer_phone'] ?? ''),
        'customer_gstin' => strtoupper(trim($data['customer_gstin'] ?? '')),
        'customer_state_code' => $data['customer_state_code'] ?? '32',
        'price_mode' => $data['price_mode'] ?? 'exclusive',
        'gst_type' => $data['gst_type'] ?? 'cgst_sgst',
        'subtotal' => (float)($data['subtotal'] ?? 0),
        'total_cgst' => (float)($data['total_cgst'] ?? 0),
        'total_sgst' => (float)($data['total_sgst'] ?? 0),
        'total_igst' => (float)($data['total_igst'] ?? 0),
        'total_tax' => 0,
        'grand_total' => (float)($data['grand_total'] ?? 0),
        'amount_in_words' => $data['amount_in_words'] ?? '',
        'drug_license' => trim($data['drug_license'] ?? ''),
        'notes' => trim($data['notes'] ?? ''),
        'created_by' => $_SESSION['user_id']
    ];

    $invoice['total_tax'] = $invoice['total_cgst'] + $invoice['total_sgst'] + $invoice['total_igst'];

    // Generate amount in words if not provided
    if (empty($invoice['amount_in_words'])) {
        $invoice['amount_in_words'] = numberToWords($invoice['grand_total']);
    }

    // Validate
    if (empty($invoice['customer_name'])) {
        throw new Exception('Customer name is required');
    }

    if (empty($data['items']) || !is_array($data['items'])) {
        throw new Exception('At least one item is required');
    }

    // Aggregate and lock managed products before creating the invoice. Sorting
    // the IDs keeps lock order consistent if two staff members bill together.
    $stockRequirements = [];
    foreach ($data['items'] as $item) {
        $productId = isset($item['product_id']) ? (int)$item['product_id'] : 0;
        $quantity = isset($item['quantity']) ? (float)$item['quantity'] : 0;
        if ($quantity <= 0) throw new Exception('Item quantity must be greater than zero');
        if ($productId > 0) {
            if (!isset($stockRequirements[$productId])) $stockRequirements[$productId] = 0;
            $stockRequirements[$productId] += $quantity;
        }
    }
    ksort($stockRequirements);
    $lockedProducts = [];
    $lockStmt = $db->prepare("SELECT id, name, stock_count FROM products WHERE id = ? AND active = 1 FOR UPDATE");
    foreach ($stockRequirements as $productId => $requiredQuantity) {
        $lockStmt->execute([$productId]);
        $locked = $lockStmt->fetch();
        if (!$locked) throw new Exception('A selected product is no longer available. Please refresh the invoice.');
        if ((float)$locked['stock_count'] < $requiredQuantity) {
            throw new Exception($locked['name'] . ' has only ' . formatStock($locked['stock_count']) . ' in stock. Requested: ' . formatStock($requiredQuantity));
        }
        $lockedProducts[$productId] = $locked;
    }

    // Insert invoice
    $stmt = $db->prepare("INSERT INTO invoices
        (invoice_number, invoice_date, invoice_type, copy_type,
         customer_id, customer_name, customer_phone, customer_gstin, customer_state_code,
         price_mode, gst_type, subtotal, total_cgst, total_sgst, total_igst, total_tax,
         grand_total, amount_in_words, drug_license, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $invoice['invoice_number'],
        $invoice['invoice_date'],
        $invoice['invoice_type'],
        $invoice['copy_type'],
        $invoice['customer_id'],
        $invoice['customer_name'],
        $invoice['customer_phone'] ?: null,
        $invoice['customer_gstin'] ?: null,
        $invoice['customer_state_code'],
        $invoice['price_mode'],
        $invoice['gst_type'],
        $invoice['subtotal'],
        $invoice['total_cgst'],
        $invoice['total_sgst'],
        $invoice['total_igst'],
        $invoice['total_tax'],
        $invoice['grand_total'],
        $invoice['amount_in_words'],
        $invoice['drug_license'] ?: null,
        $invoice['notes'] ?: null,
        $invoice['created_by']
    ]);

    $invoiceId = $db->lastInsertId();

    // Insert items
    $itemStmt = $db->prepare("INSERT INTO invoice_items
        (invoice_id, product_id, description, hsn_code, batch_no, expiry_date,
         quantity, mrp, gst_rate, taxable_amount, cgst_amount, sgst_amount, igst_amount, total_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $priceMode = $invoice['price_mode'];
    $gstType = $invoice['gst_type'];

    foreach ($data['items'] as $item) {
        $description = trim($item['description'] ?? '');
        if (empty($description)) continue; // Skip empty rows

        $qty = (float)($item['quantity'] ?? 1);
        $mrp = (float)($item['mrp'] ?? 0);
        $gstRate = (float)($item['gst_rate'] ?? 5);

        // Calculate amounts
        if ($priceMode === 'inclusive') {
            $lineTotal = $qty * $mrp;
            $taxableAmount = $lineTotal / (1 + $gstRate / 100);
            $gstAmount = $lineTotal - $taxableAmount;
        } else {
            $taxableAmount = $qty * $mrp;
            $gstAmount = $taxableAmount * $gstRate / 100;
            $lineTotal = $taxableAmount + $gstAmount;
        }

        $cgstAmount = 0;
        $sgstAmount = 0;
        $igstAmount = 0;

        if ($gstType === 'igst') {
            $igstAmount = $gstAmount;
        } else {
            $cgstAmount = $gstAmount / 2;
            $sgstAmount = $gstAmount / 2;
        }

        $itemStmt->execute([
            $invoiceId,
            !empty($item['product_id']) ? (int)$item['product_id'] : null,
            $description,
            $item['hsn_code'] ?: null,
            $item['batch_no'] ?: null,
            $item['expiry_date'] ?: null,
            $qty,
            $mrp,
            $gstRate,
            $taxableAmount,
            $cgstAmount,
            $sgstAmount,
            $igstAmount,
            $lineTotal
        ]);
    }

    // Deduct all managed products and write the audit entries in the same
    // transaction as the invoice. Any failure rolls everything back.
    $deductStmt = $db->prepare("UPDATE products SET stock_count = ? WHERE id = ?");
    foreach ($stockRequirements as $productId => $requiredQuantity) {
        $before = (float)$lockedProducts[$productId]['stock_count'];
        $after = $before - $requiredQuantity;
        $deductStmt->execute([$after, $productId]);
        recordStockMovement(
            $db, $productId, 'sale', -$requiredQuantity, $before, $after,
            $_SESSION['user_id'], 'Automatically deducted for invoice ' . $invoice['invoice_number'],
            'invoice', $invoiceId
        );
    }

    $db->commit();

    // Prepare items data for email
    $invoice['id'] = $invoiceId;
    $invoice['created_at'] = date('Y-m-d H:i:s');

    // Get saved items from database for accurate totals
    $itemsStmt = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
    $itemsStmt->execute([$invoiceId]);
    $savedItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Send email notification (non-blocking - don't fail invoice if email fails)
    $emailResult = ['success' => false, 'message' => 'Email not sent'];
    try {
        // Determine base URL for invoice link
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host . '/xamp-cosmic';

        $emailResult = sendInvoiceNotification($invoice, $savedItems, $baseUrl);
    } catch (Exception $emailEx) {
        $emailResult = ['success' => false, 'message' => $emailEx->getMessage()];
    }

    echo json_encode([
        'success' => true,
        'invoice_id' => $invoiceId,
        'invoice_number' => $invoice['invoice_number'],
        'email_sent' => $emailResult['success'],
        'email_message' => $emailResult['message']
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
