<?php
/**
 * Mailer Helper
 * COSMIC SURGICALS - Email sending functionality using PHPMailer
 */

// Include PHPMailer classes
require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';

// Include email configuration
require_once __DIR__ . '/../config/email.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send invoice notification email
 *
 * @param array $invoice Invoice data
 * @param array $items Invoice items
 * @param string $baseUrl Base URL for invoice links
 * @return array ['success' => bool, 'message' => string]
 */
function sendInvoiceNotification($invoice, $items, $baseUrl = '') {
    $config = getEmailConfig();

    // Check if notifications are enabled
    if (!$config['send_notifications']) {
        return ['success' => true, 'message' => 'Notifications disabled'];
    }

    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = $config['smtp_auth'];
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['smtp_port'];
        $mail->SMTPDebug = $config['debug_mode'];

        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($config['notify_email'], $config['notify_name']);
        $mail->addReplyTo($config['from_email'], $config['from_name']);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        // Email subject
        $invoiceType = strtoupper($invoice['invoice_type']);
        $mail->Subject = "New Invoice #{$invoice['invoice_number']} - {$invoiceType} - Rs. " . number_format($invoice['grand_total'], 2);

        // Build email body
        $mail->Body = buildInvoiceEmailBody($invoice, $items, $baseUrl);
        $mail->AltBody = buildInvoiceEmailText($invoice, $items);

        $mail->send();

        return ['success' => true, 'message' => 'Email sent successfully'];

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email failed: ' . $mail->ErrorInfo];
    }
}

/**
 * Build HTML email body for invoice notification
 */
function buildInvoiceEmailBody($invoice, $items, $baseUrl = '') {
    $invoiceDate = date('d M Y', strtotime($invoice['invoice_date']));
    $createdAt = date('d M Y, h:i A', strtotime($invoice['created_at'] ?? 'now'));
    $invoiceType = ucfirst($invoice['invoice_type']);
    $gstType = $invoice['gst_type'] === 'igst' ? 'IGST' : 'CGST + SGST';

    // Invoice type colors
    $typeColors = [
        'upi' => '#6366f1',
        'cash' => '#10b981',
        'credit' => '#f59e0b',
        'card' => '#06b6d4',
        'bank' => '#6b7280'
    ];
    $typeColor = $typeColors[$invoice['invoice_type']] ?? '#6b7280';

    // Build items table rows
    $itemRows = '';
    foreach ($items as $i => $item) {
        $itemRows .= "<tr>
            <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . ($i + 1) . "</td>
            <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['description']}</td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>Rs. " . number_format($item['mrp'], 2) . "</td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['gst_rate']}%</td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'><strong>Rs. " . number_format($item['total_amount'], 2) . "</strong></td>
        </tr>";
    }

    // Print link
    $printLink = $baseUrl ? "{$baseUrl}/modules/invoices/print.php?id={$invoice['id']}" : '#';

    // Pre-compute GSTIN display
    $gstinDisplay = '';
    if (!empty($invoice['customer_gstin'])) {
        $gstinDisplay = ' | GSTIN: ' . $invoice['customer_gstin'];
    }

    // Customer phone display
    $phoneDisplay = $invoice['customer_phone'] ? $invoice['customer_phone'] : '';

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #f5f5f5; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3c72, #2a5298); padding: 25px; text-align: center;">
                            <h1 style="margin: 0; color: white; font-size: 24px; letter-spacing: 1px;">COSMIC SURGICALS</h1>
                            <p style="margin: 5px 0 0; color: rgba(255,255,255,0.8); font-size: 12px;">New Invoice Notification</p>
                        </td>
                    </tr>

                    <!-- Invoice Badge -->
                    <tr>
                        <td style="padding: 20px 25px 0; text-align: center;">
                            <span style="display: inline-block; background: {$typeColor}; color: white; padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: 600;">
                                {$invoiceType} Payment
                            </span>
                        </td>
                    </tr>

                    <!-- Invoice Details -->
                    <tr>
                        <td style="padding: 20px 25px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="50%" style="padding: 10px; background: #f8f9fa; border-radius: 8px;">
                                        <p style="margin: 0 0 5px; font-size: 11px; color: #666; text-transform: uppercase;">Invoice Number</p>
                                        <p style="margin: 0; font-size: 18px; font-weight: 700; color: #1e3c72;">{$invoice['invoice_number']}</p>
                                    </td>
                                    <td width="50%" style="padding: 10px; background: #f8f9fa; border-radius: 8px;">
                                        <p style="margin: 0 0 5px; font-size: 11px; color: #666; text-transform: uppercase;">Invoice Date</p>
                                        <p style="margin: 0; font-size: 18px; font-weight: 700; color: #1e3c72;">{$invoiceDate}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Customer Info -->
                    <tr>
                        <td style="padding: 0 25px 20px;">
                            <div style="background: #f0f4ff; border-radius: 8px; padding: 15px; border-left: 4px solid #1e3c72;">
                                <p style="margin: 0 0 5px; font-size: 11px; color: #666; text-transform: uppercase;">Customer</p>
                                <p style="margin: 0; font-size: 16px; font-weight: 600; color: #333;">{$invoice['customer_name']}</p>
                                <p style="margin: 5px 0 0; font-size: 13px; color: #666;">
                                    {$phoneDisplay}{$gstinDisplay}
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Items Table -->
                    <tr>
                        <td style="padding: 0 25px 20px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #eee; border-radius: 8px; overflow: hidden;">
                                <thead>
                                    <tr style="background: #374151;">
                                        <th style="padding: 12px 10px; color: white; font-size: 11px; text-transform: uppercase; text-align: left;">#</th>
                                        <th style="padding: 12px 10px; color: white; font-size: 11px; text-transform: uppercase; text-align: left;">Item</th>
                                        <th style="padding: 12px 10px; color: white; font-size: 11px; text-transform: uppercase; text-align: center;">Qty</th>
                                        <th style="padding: 12px 10px; color: white; font-size: 11px; text-transform: uppercase; text-align: right;">MRP</th>
                                        <th style="padding: 12px 10px; color: white; font-size: 11px; text-transform: uppercase; text-align: center;">GST</th>
                                        <th style="padding: 12px 10px; color: white; font-size: 11px; text-transform: uppercase; text-align: right;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$itemRows}
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <!-- Totals -->
                    <tr>
                        <td style="padding: 0 25px 20px;">
                            <table width="300" cellpadding="0" cellspacing="0" style="margin-left: auto; border: 1px solid #eee; border-radius: 8px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 10px 15px; color: #666;">Taxable Amount</td>
                                    <td style="padding: 10px 15px; text-align: right; font-weight: 600;">Rs. {$invoice['subtotal']}</td>
                                </tr>
HTML;

    if ($invoice['gst_type'] === 'igst') {
        $html .= <<<HTML
                                <tr style="background: #f0f4ff;">
                                    <td style="padding: 10px 15px; color: #666;">IGST</td>
                                    <td style="padding: 10px 15px; text-align: right; font-weight: 600;">Rs. {$invoice['total_igst']}</td>
                                </tr>
HTML;
    } else {
        $html .= <<<HTML
                                <tr style="background: #f0f4ff;">
                                    <td style="padding: 10px 15px; color: #666;">CGST</td>
                                    <td style="padding: 10px 15px; text-align: right; font-weight: 600;">Rs. {$invoice['total_cgst']}</td>
                                </tr>
                                <tr style="background: #f0f4ff;">
                                    <td style="padding: 10px 15px; color: #666;">SGST</td>
                                    <td style="padding: 10px 15px; text-align: right; font-weight: 600;">Rs. {$invoice['total_sgst']}</td>
                                </tr>
HTML;
    }

    $html .= <<<HTML
                                <tr style="background: linear-gradient(135deg, #1e3c72, #2a5298);">
                                    <td style="padding: 15px; color: white; font-size: 16px; font-weight: 700;">Grand Total</td>
                                    <td style="padding: 15px; text-align: right; color: white; font-size: 18px; font-weight: 700;">Rs. {$invoice['grand_total']}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- View Invoice Button -->
                    <tr>
                        <td style="padding: 0 25px 25px; text-align: center;">
                            <a href="{$printLink}" style="display: inline-block; background: linear-gradient(135deg, #f5af19, #f12711); color: white; padding: 15px 40px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 14px;">
                                View & Print Invoice
                            </a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: #f8f9fa; padding: 15px 25px; text-align: center; border-top: 1px solid #eee;">
                            <p style="margin: 0; font-size: 11px; color: #999;">
                                Invoice created on {$createdAt}<br>
                                COSMIC SURGICALS | GST Invoice Management System
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    return $html;
}

/**
 * Build plain text email body for invoice notification
 */
function buildInvoiceEmailText($invoice, $items) {
    $invoiceDate = date('d M Y', strtotime($invoice['invoice_date']));
    $invoiceType = ucfirst($invoice['invoice_type']);

    $text = "COSMIC SURGICALS - New Invoice Notification\n";
    $text .= "============================================\n\n";
    $text .= "Invoice Number: {$invoice['invoice_number']}\n";
    $text .= "Invoice Date: {$invoiceDate}\n";
    $text .= "Payment Type: {$invoiceType}\n\n";
    $text .= "Customer: {$invoice['customer_name']}\n";
    if ($invoice['customer_phone']) {
        $text .= "Phone: {$invoice['customer_phone']}\n";
    }
    $text .= "\n";
    $text .= "Items:\n";
    $text .= "------\n";

    foreach ($items as $i => $item) {
        $text .= ($i + 1) . ". {$item['description']} - Qty: {$item['quantity']} x Rs. {$item['mrp']} = Rs. {$item['total_amount']}\n";
    }

    $text .= "\n";
    $text .= "Taxable Amount: Rs. {$invoice['subtotal']}\n";

    if ($invoice['gst_type'] === 'igst') {
        $text .= "IGST: Rs. {$invoice['total_igst']}\n";
    } else {
        $text .= "CGST: Rs. {$invoice['total_cgst']}\n";
        $text .= "SGST: Rs. {$invoice['total_sgst']}\n";
    }

    $text .= "------\n";
    $text .= "Grand Total: Rs. {$invoice['grand_total']}\n\n";
    $text .= "============================================\n";
    $text .= "COSMIC SURGICALS | GST Invoice Management System\n";

    return $text;
}

/**
 * Test email configuration
 */
function testEmailConnection() {
    $config = getEmailConfig();

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = $config['smtp_auth'];
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['smtp_port'];
        $mail->SMTPDebug = 0;

        // Test connection
        $mail->smtpConnect();
        $mail->smtpClose();

        return ['success' => true, 'message' => 'SMTP connection successful'];

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'SMTP connection failed: ' . $e->getMessage()];
    }
}
