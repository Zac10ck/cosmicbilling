<?php
/**
 * Email CLI Test Script
 * COSMIC SURGICALS - Test email configuration via command line
 */

echo "===========================================\n";
echo "COSMIC SURGICALS - Email Test\n";
echo "===========================================\n\n";

// Include mailer
require_once __DIR__ . '/includes/mailer.php';

// Test 1: Connection Test
echo "1. Testing SMTP Connection...\n";
$connResult = testEmailConnection();

if ($connResult['success']) {
    echo "   ✓ " . $connResult['message'] . "\n\n";
} else {
    echo "   ✗ " . $connResult['message'] . "\n";
    echo "   Please check your email configuration in config/email.php\n\n";
    exit(1);
}

// Test 2: Send Test Email
echo "2. Sending Test Invoice Email...\n";

// Sample invoice data
$testInvoice = [
    'id' => 9999,
    'invoice_number' => 'TEST-' . date('ymd-His'),
    'invoice_date' => date('Y-m-d'),
    'invoice_type' => 'upi',
    'customer_name' => 'Test Customer',
    'customer_phone' => '9876543210',
    'customer_gstin' => '',
    'customer_state_code' => '32',
    'gst_type' => 'cgst_sgst',
    'subtotal' => '1000.00',
    'total_cgst' => '25.00',
    'total_sgst' => '25.00',
    'total_igst' => '0.00',
    'grand_total' => '1050.00',
    'created_at' => date('Y-m-d H:i:s')
];

$testItems = [
    [
        'description' => 'Test Item 1 - Surgical Gloves',
        'quantity' => 10,
        'mrp' => '52.50',
        'gst_rate' => 5,
        'total_amount' => '525.00'
    ],
    [
        'description' => 'Test Item 2 - Bandage Roll',
        'quantity' => 5,
        'mrp' => '105.00',
        'gst_rate' => 5,
        'total_amount' => '525.00'
    ]
];

$baseUrl = 'http://localhost/xamp-cosmic';

echo "   Invoice: " . $testInvoice['invoice_number'] . "\n";
echo "   Amount: Rs. " . $testInvoice['grand_total'] . "\n";
echo "   Sending to: cosmicsurgical@gmail.com\n\n";

$emailResult = sendInvoiceNotification($testInvoice, $testItems, $baseUrl);

if ($emailResult['success']) {
    echo "   ✓ " . $emailResult['message'] . "\n\n";
    echo "===========================================\n";
    echo "SUCCESS! Check cosmicsurgical@gmail.com for the test email!\n";
    echo "===========================================\n";
} else {
    echo "   ✗ " . $emailResult['message'] . "\n\n";
    echo "===========================================\n";
    echo "FAILED! Email could not be sent.\n";
    echo "===========================================\n";
    exit(1);
}
