<?php
/**
 * Email Test Script
 * COSMIC SURGICALS - Test email configuration
 *
 * Run this script to test if email sending works:
 * http://localhost/xamp-cosmic/test_email.php
 */

require_once __DIR__ . '/includes/mailer.php';

echo "<h1>COSMIC SURGICALS - Email Test</h1>";

// Test 1: Connection Test
echo "<h2>1. Testing SMTP Connection...</h2>";
$connResult = testEmailConnection();

if ($connResult['success']) {
    echo "<p style='color: green;'>✓ " . $connResult['message'] . "</p>";
} else {
    echo "<p style='color: red;'>✗ " . $connResult['message'] . "</p>";
    echo "<p>Please check your email configuration in config/email.php</p>";
    exit;
}

// Test 2: Send Test Email
echo "<h2>2. Sending Test Invoice Email...</h2>";

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

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host . '/xamp-cosmic';

$emailResult = sendInvoiceNotification($testInvoice, $testItems, $baseUrl);

if ($emailResult['success']) {
    echo "<p style='color: green;'>✓ " . $emailResult['message'] . "</p>";
    echo "<p>Check cosmicsurgical@gmail.com for the test email!</p>";
} else {
    echo "<p style='color: red;'>✗ " . $emailResult['message'] . "</p>";
}

echo "<hr>";
echo "<h3>Email Configuration:</h3>";
echo "<pre>";
$config = getEmailConfig();
echo "SMTP Host: " . $config['smtp_host'] . "\n";
echo "SMTP Port: " . $config['smtp_port'] . "\n";
echo "Sender: " . $config['from_email'] . "\n";
echo "Recipient: " . $config['notify_email'] . "\n";
echo "Notifications Enabled: " . ($config['send_notifications'] ? 'Yes' : 'No') . "\n";
echo "</pre>";

echo "<p><a href='/xamp-cosmic/modules/invoices/create.php'>← Go to Create Invoice</a></p>";
