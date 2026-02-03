<?php
/**
 * Print Invoice
 * COSMIC SURGICALS - Invoice Management System
 * Matches QuickInvoice.html print layout
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    die('Invalid invoice');
}

$invoice = getInvoice($id);

if (!$invoice) {
    die('Invoice not found');
}

// $company is available from database.php (included via auth.php)
$states = getStateCodes();

// Get state name
$customerStateName = $states[$invoice['customer_state_code']] ?? 'Unknown';
$sellerStateName = $states[$company['state_code']] ?? 'Kerala';

// Calculate tax summary by rate
$taxSummary = [];
foreach ($invoice['items'] as $item) {
    $rate = $item['gst_rate'];
    if (!isset($taxSummary[$rate])) {
        $taxSummary[$rate] = [
            'hsn' => $item['hsn_code'] ?: '-',
            'taxable' => 0,
            'cgst' => 0,
            'sgst' => 0,
            'igst' => 0,
            'total_tax' => 0
        ];
    }
    $taxSummary[$rate]['taxable'] += $item['taxable_amount'];
    $taxSummary[$rate]['cgst'] += $item['cgst_amount'];
    $taxSummary[$rate]['sgst'] += $item['sgst_amount'];
    $taxSummary[$rate]['igst'] += $item['igst_amount'];
    $taxSummary[$rate]['total_tax'] += ($item['cgst_amount'] + $item['sgst_amount'] + $item['igst_amount']);
}

// Amount in words
$amountInWords = $invoice['amount_in_words'] ?: numberToWords($invoice['grand_total']);

// Set document title for PDF filename
$dateStr = date('dMy-His', strtotime($invoice['created_at']));
$docTitle = 'Invoice-' . $invoice['invoice_number'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $docTitle; ?></title>
    <style>
        @page {
            size: A4;
            margin: 8mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.4;
            background: #f0f0f0;
            padding: 20px;
        }

        .inv-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        /* Header */
        .inv-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298) !important;
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .inv-company {
            flex: 1;
        }

        .inv-company-name {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .inv-company-addr {
            font-size: 10px;
            opacity: 0.9;
            line-height: 1.5;
        }

        .inv-gstin-box {
            background: rgba(255,255,255,0.15);
            padding: 12px 20px;
            border-radius: 8px;
            text-align: center;
        }

        .inv-gstin-label {
            font-size: 9px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .inv-gstin-value {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        /* Title */
        .inv-title {
            text-align: center;
            padding: 12px;
            background: #f8f9fa !important;
            border-bottom: 3px solid #1e3c72;
        }

        .inv-title h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1e3c72;
            letter-spacing: 3px;
            margin: 0;
        }

        .inv-title .copy-type {
            font-size: 12px;
            font-weight: 600;
            margin-top: 3px;
            color: #666;
        }

        /* Info Section */
        .inv-info {
            display: flex;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .inv-info-block {
            flex: 1;
            padding: 0 15px;
        }

        .inv-info-block:first-child {
            padding-left: 0;
            border-right: 1px solid #e5e7eb;
        }

        .inv-info-block:last-child {
            padding-right: 0;
        }

        .inv-info-title {
            font-size: 9px;
            font-weight: 700;
            color: #1e3c72;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 2px solid #1e3c72;
            display: inline-block;
        }

        .inv-info-content p {
            margin: 4px 0;
            font-size: 11px;
        }

        .inv-info-content .name {
            font-size: 13px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .inv-info-content strong {
            color: #374151;
        }

        /* Items Table */
        .inv-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10px;
        }

        .inv-table th {
            background: #1e3c72 !important;
            color: white !important;
            padding: 10px 6px;
            text-align: center;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #1e3c72;
        }

        .inv-table td {
            padding: 8px 4px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .inv-table td small {
            display: block;
            font-size: 8px;
            color: #666;
            margin-top: 2px;
        }

        .inv-table tbody tr:nth-child(even) {
            background: #f9fafb !important;
        }

        .inv-table .text-left { text-align: left; padding-left: 8px; }
        .inv-table .text-right { text-align: right; padding-right: 8px; }

        .inv-table tfoot td {
            background: #f0f4ff !important;
            font-weight: 600;
            border-top: 2px solid #1e3c72;
        }

        /* Summary Section */
        .inv-summary {
            display: flex;
            gap: 20px;
            margin: 15px 20px;
        }

        .inv-tax-summary {
            flex: 1.2;
        }

        .inv-tax-summary h4 {
            font-size: 10px;
            font-weight: 700;
            color: #1e3c72;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .inv-tax-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .inv-tax-table th {
            background: #374151 !important;
            color: white !important;
            padding: 8px 6px;
            text-align: center;
            font-weight: 600;
        }

        .inv-tax-table td {
            padding: 8px 6px;
            border: 1px solid #e5e7eb;
            text-align: right;
        }

        .inv-totals {
            width: 240px;
        }

        .inv-totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            font-size: 11px;
            border-bottom: 1px solid #e5e7eb;
        }

        .inv-totals-row.grand {
            background: #1e3c72 !important;
            color: white !important;
            font-size: 14px;
            font-weight: 700;
            margin-top: 5px;
            border-radius: 6px;
            padding: 12px;
        }

        /* Amount in Words */
        .inv-words {
            background: #f0f4ff !important;
            padding: 12px 15px;
            border-left: 4px solid #1e3c72;
            margin: 15px 20px;
            font-size: 11px;
        }

        .inv-words strong {
            color: #1e3c72;
        }

        /* Bank Details */
        .inv-bank {
            background: #f9fafb !important;
            padding: 12px 15px;
            border-radius: 8px;
            margin: 15px 20px;
            font-size: 10px;
        }

        .inv-bank h4 {
            font-size: 10px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 8px;
        }

        .inv-bank p {
            margin: 3px 0;
        }

        /* Footer */
        .inv-footer {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            border-top: 2px dashed #d1d5db;
            margin: 0 20px;
        }

        .inv-terms {
            flex: 1;
            font-size: 9px;
            color: #6b7280;
        }

        .inv-terms h4 {
            font-size: 10px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 5px;
        }

        .inv-signature {
            width: 180px;
            text-align: center;
        }

        .inv-signature .company-name {
            font-weight: 700;
            color: #1e3c72;
            font-size: 11px;
        }

        .inv-signature-line {
            border-top: 1px solid #1a1a1a;
            margin: 45px auto 5px;
            width: 140px;
        }

        .inv-signature .label {
            font-size: 10px;
            color: #6b7280;
        }

        /* Thank You */
        .inv-thankyou {
            text-align: center;
            margin: 15px 20px 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }

        .inv-thankyou p {
            font-size: 12px;
            font-weight: 600;
            color: #1e3c72;
        }

        /* Print Button */
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 30px;
            background: linear-gradient(135deg, #f5af19, #f12711);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(241, 39, 17, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(241, 39, 17, 0.4);
        }

        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 12px 20px;
            background: #374151;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 14px;
            cursor: pointer;
            z-index: 1000;
            text-decoration: none;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .print-btn, .back-btn {
                display: none !important;
            }
            .inv-container {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <a href="/xamp-cosmic/modules/invoices/index.php" class="back-btn">&larr; Back</a>
    <button class="print-btn" onclick="window.print()">
        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
        Print Invoice
    </button>

    <div class="inv-container">
        <!-- Header -->
        <div class="inv-header">
            <div class="inv-company">
                <div class="inv-company-name"><?php echo e($company['name']); ?></div>
                <div class="inv-company-addr">
                    <?php echo e($company['address']); ?><br>
                    <?php echo e($company['city']); ?><br>
                    Phone: <?php echo e($company['phone']); ?> | Email: <?php echo e($company['email']); ?><br>
                    Web: <?php echo e($company['website']); ?>
                    <?php if ($invoice['drug_license']): ?>
                    <br><span style="font-size:9px;">D/L No: <?php echo e($invoice['drug_license']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="inv-gstin-box">
                <div class="inv-gstin-label">GSTIN</div>
                <div class="inv-gstin-value"><?php echo e($company['gstin']); ?></div>
            </div>
        </div>

        <!-- Title -->
        <div class="inv-title">
            <h2><?php echo strtoupper($invoice['invoice_type']); ?> BILL - TAX INVOICE</h2>
            <div class="copy-type"><?php echo strtoupper($invoice['copy_type']); ?></div>
        </div>

        <!-- Info -->
        <div class="inv-info">
            <div class="inv-info-block">
                <div class="inv-info-title">Bill To</div>
                <div class="inv-info-content">
                    <p class="name"><?php echo e($invoice['customer_name']); ?></p>
                    <?php if ($invoice['customer_phone']): ?>
                    <p><strong>Phone:</strong> <?php echo e($invoice['customer_phone']); ?></p>
                    <?php endif; ?>
                    <?php if ($invoice['customer_gstin']): ?>
                    <p><strong>GSTIN:</strong> <?php echo e($invoice['customer_gstin']); ?></p>
                    <?php endif; ?>
                    <p><strong>State:</strong> <?php echo e($customerStateName); ?> (<?php echo $invoice['customer_state_code']; ?>)</p>
                </div>
            </div>
            <div class="inv-info-block">
                <div class="inv-info-title">Invoice Details</div>
                <div class="inv-info-content">
                    <p><strong>Invoice No:</strong> <?php echo e($invoice['invoice_number']); ?></p>
                    <p><strong>Invoice Date:</strong> <?php echo formatDate($invoice['invoice_date']); ?></p>
                    <p><strong>Place of Supply:</strong> <?php echo e($customerStateName); ?> (<?php echo $invoice['customer_state_code']; ?>)</p>
                    <p><strong>Reverse Charge:</strong> No</p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="inv-table" style="margin: 15px 20px; width: calc(100% - 40px);">
            <thead>
                <tr>
                    <th style="width:25px">S.No</th>
                    <th class="text-left">Description of Goods/Services</th>
                    <th style="width:50px">HSN</th>
                    <th style="width:50px">Batch</th>
                    <th style="width:45px">Expiry</th>
                    <th style="width:35px">Qty</th>
                    <th style="width:55px">MRP</th>
                    <th style="width:60px">Taxable</th>
                    <?php if ($invoice['gst_type'] === 'igst'): ?>
                    <th style="width:55px">IGST</th>
                    <?php else: ?>
                    <th style="width:50px">CGST</th>
                    <th style="width:50px">SGST</th>
                    <?php endif; ?>
                    <th style="width:60px">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice['items'] as $i => $item): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td class="text-left"><?php echo e($item['description']); ?></td>
                    <td><?php echo e($item['hsn_code']) ?: '-'; ?></td>
                    <td><?php echo e($item['batch_no']) ?: '-'; ?></td>
                    <td><?php echo e($item['expiry_date']) ?: '-'; ?></td>
                    <td><?php echo (int)$item['quantity']; ?></td>
                    <td class="text-right"><?php echo formatCurrency($item['mrp']); ?></td>
                    <td class="text-right"><?php echo formatCurrency($item['taxable_amount']); ?></td>
                    <?php if ($invoice['gst_type'] === 'igst'): ?>
                    <td class="text-right">
                        <?php echo formatCurrency($item['igst_amount']); ?>
                        <small>(<?php echo $item['gst_rate']; ?>%)</small>
                    </td>
                    <?php else: ?>
                    <td class="text-right">
                        <?php echo formatCurrency($item['cgst_amount']); ?>
                        <small>(<?php echo $item['gst_rate']/2; ?>%)</small>
                    </td>
                    <td class="text-right">
                        <?php echo formatCurrency($item['sgst_amount']); ?>
                        <small>(<?php echo $item['gst_rate']/2; ?>%)</small>
                    </td>
                    <?php endif; ?>
                    <td class="text-right"><?php echo formatCurrency($item['total_amount']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="text-right"><strong>Total</strong></td>
                    <td class="text-right"><?php echo formatCurrency($invoice['subtotal']); ?></td>
                    <?php if ($invoice['gst_type'] === 'igst'): ?>
                    <td class="text-right"><?php echo formatCurrency($invoice['total_igst']); ?></td>
                    <?php else: ?>
                    <td class="text-right"><?php echo formatCurrency($invoice['total_cgst']); ?></td>
                    <td class="text-right"><?php echo formatCurrency($invoice['total_sgst']); ?></td>
                    <?php endif; ?>
                    <td class="text-right"><?php echo formatCurrency($invoice['grand_total']); ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Summary -->
        <div class="inv-summary">
            <div class="inv-tax-summary">
                <h4>Tax Summary</h4>
                <table class="inv-tax-table">
                    <thead>
                        <tr>
                            <th>HSN/SAC</th>
                            <th>Rate</th>
                            <th>Taxable Value</th>
                            <?php if ($invoice['gst_type'] === 'igst'): ?>
                            <th>IGST</th>
                            <?php else: ?>
                            <th>CGST</th>
                            <th>SGST</th>
                            <?php endif; ?>
                            <th>Total Tax</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($taxSummary as $rate => $summary): ?>
                        <tr>
                            <td style="text-align:center;"><?php echo e($summary['hsn']); ?></td>
                            <td style="text-align:center;"><?php echo $rate; ?>%</td>
                            <td><?php echo formatCurrency($summary['taxable']); ?></td>
                            <?php if ($invoice['gst_type'] === 'igst'): ?>
                            <td><?php echo formatCurrency($summary['igst']); ?></td>
                            <?php else: ?>
                            <td><?php echo formatCurrency($summary['cgst']); ?></td>
                            <td><?php echo formatCurrency($summary['sgst']); ?></td>
                            <?php endif; ?>
                            <td><?php echo formatCurrency($summary['total_tax']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="inv-totals">
                <div class="inv-totals-row">
                    <span>Taxable Amount</span>
                    <span><?php echo formatCurrency($invoice['subtotal']); ?></span>
                </div>
                <?php if ($invoice['gst_type'] === 'igst'): ?>
                <div class="inv-totals-row">
                    <span>IGST</span>
                    <span><?php echo formatCurrency($invoice['total_igst']); ?></span>
                </div>
                <?php else: ?>
                <div class="inv-totals-row">
                    <span>CGST</span>
                    <span><?php echo formatCurrency($invoice['total_cgst']); ?></span>
                </div>
                <div class="inv-totals-row">
                    <span>SGST</span>
                    <span><?php echo formatCurrency($invoice['total_sgst']); ?></span>
                </div>
                <?php endif; ?>
                <div class="inv-totals-row grand">
                    <span>Grand Total</span>
                    <span><?php echo formatCurrency($invoice['grand_total']); ?></span>
                </div>
            </div>
        </div>

        <!-- Amount in Words -->
        <div class="inv-words">
            <strong>Total Amount (in words):</strong> <?php echo e($amountInWords); ?>
        </div>

        <!-- Bank Details -->
        <div class="inv-bank">
            <h4>Our Bankers</h4>
            <p><strong>Bank:</strong> <?php echo e($company['bank_name']); ?></p>
            <p><strong>A/C No:</strong> <?php echo e($company['bank_account']); ?> | <strong>IFS Code:</strong> <?php echo e($company['bank_ifsc']); ?></p>
        </div>

        <!-- Footer -->
        <div class="inv-footer">
            <div class="inv-terms">
                <h4>Declaration</h4>
                <p style="margin-bottom:8px;">We Declare that this Invoice Shows the actual Price of the Goods described and that All Particulars are True and Correct.</p>
                <p><strong>E & O.E.</strong></p>
                <p style="margin-top:8px;">* Subject to <?php echo e($sellerStateName); ?> Jurisdiction</p>
            </div>
            <div class="inv-signature">
                <p class="company-name">For <?php echo e($company['name']); ?></p>
                <div class="inv-signature-line"></div>
                <p class="label">Authorized Signatory</p>
            </div>
        </div>

        <!-- Thank You -->
        <div class="inv-thankyou">
            <p>Thank you for your business!</p>
        </div>
    </div>
</body>
</html>
