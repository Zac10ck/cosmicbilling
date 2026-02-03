<?php
/**
 * View Invoice
 * COSMIC SURGICALS - Invoice Management System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    redirect('/xamp-cosmic/modules/invoices/index.php', 'Invalid invoice', 'error');
}

$invoice = getInvoice($id);

if (!$invoice) {
    redirect('/xamp-cosmic/modules/invoices/index.php', 'Invoice not found', 'error');
}

$pageTitle = 'Invoice ' . $invoice['invoice_number'];
require_once __DIR__ . '/../../includes/header.php';

global $company;
$states = getStateCodes();
?>

<div class="page-header">
    <h1>Invoice <?php echo e($invoice['invoice_number']); ?></h1>
    <div class="actions">
        <a href="/xamp-cosmic/modules/invoices/print.php?id=<?php echo $id; ?>" class="btn btn-primary" target="_blank">Print</a>
        <a href="/xamp-cosmic/modules/invoices/index.php" class="btn btn-secondary">Back to List</a>
    </div>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success">Invoice saved successfully!</div>
<?php endif; ?>

<div class="card">
    <div class="form-row">
        <div class="form-group">
            <label>Invoice Number</label>
            <div style="font-size: 18px; font-weight: 600;"><?php echo e($invoice['invoice_number']); ?></div>
        </div>
        <div class="form-group">
            <label>Invoice Date</label>
            <div><?php echo formatDate($invoice['invoice_date']); ?></div>
        </div>
        <div class="form-group">
            <label>Invoice Type</label>
            <div>
                <span class="badge badge-<?php echo $invoice['invoice_type'] === 'cash' ? 'success' : 'warning'; ?>">
                    <?php echo ucfirst($invoice['invoice_type']); ?> Bill
                </span>
            </div>
        </div>
        <div class="form-group">
            <label>Copy Type</label>
            <div><?php echo ucfirst($invoice['copy_type']); ?></div>
        </div>
    </div>
</div>

<div class="form-row">
    <div class="card" style="flex: 1;">
        <div class="card-header">
            <h2>Customer Details</h2>
        </div>
        <table>
            <tr>
                <td style="width: 120px; color: #666;">Name</td>
                <td><strong><?php echo e($invoice['customer_name']); ?></strong></td>
            </tr>
            <?php if ($invoice['customer_phone']): ?>
            <?php
            // Format phone for WhatsApp (remove spaces, dashes, add country code if needed)
            $phone = preg_replace('/[^0-9]/', '', $invoice['customer_phone']);
            if (strlen($phone) == 10) {
                $phone = '91' . $phone; // Add India country code
            }
            ?>
            <tr>
                <td style="color: #666;">Phone</td>
                <td>
                    <a href="https://wa.me/<?php echo $phone; ?>" target="_blank" style="color: #25D366; text-decoration: none; font-weight: 500;" title="Chat on WhatsApp">
                        <?php echo e($invoice['customer_phone']); ?>
                        <span style="background: #25D366; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 5px;">WhatsApp</span>
                    </a>
                </td>
            </tr>
            <?php endif; ?>
            <?php if ($invoice['customer_gstin']): ?>
            <tr>
                <td style="color: #666;">GSTIN</td>
                <td><?php echo e($invoice['customer_gstin']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="color: #666;">State</td>
                <td><?php echo e($states[$invoice['customer_state_code']] ?? $invoice['customer_state_code']); ?></td>
            </tr>
        </table>
    </div>

    <div class="card" style="flex: 1;">
        <div class="card-header">
            <h2>Invoice Settings</h2>
        </div>
        <table>
            <tr>
                <td style="width: 120px; color: #666;">Price Mode</td>
                <td><?php echo $invoice['price_mode'] === 'inclusive' ? 'Tax Inclusive' : 'Tax Exclusive'; ?></td>
            </tr>
            <tr>
                <td style="color: #666;">GST Type</td>
                <td><?php echo $invoice['gst_type'] === 'igst' ? 'IGST' : 'CGST + SGST'; ?></td>
            </tr>
            <?php if ($invoice['drug_license']): ?>
            <tr>
                <td style="color: #666;">Drug License</td>
                <td><?php echo e($invoice['drug_license']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="color: #666;">Created By</td>
                <td><?php echo e($invoice['created_by_name']); ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Invoice Items</h2>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>HSN</th>
                    <th>Batch</th>
                    <th>Expiry</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">MRP</th>
                    <th class="text-right">GST %</th>
                    <th class="text-right">Taxable</th>
                    <?php if ($invoice['gst_type'] === 'igst'): ?>
                        <th class="text-right">IGST</th>
                    <?php else: ?>
                        <th class="text-right">CGST</th>
                        <th class="text-right">SGST</th>
                    <?php endif; ?>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice['items'] as $i => $item): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo e($item['description']); ?></td>
                    <td><?php echo e($item['hsn_code']); ?></td>
                    <td><?php echo e($item['batch_no']); ?></td>
                    <td><?php echo e($item['expiry_date']); ?></td>
                    <td class="text-right"><?php echo $item['quantity']; ?></td>
                    <td class="text-right"><?php echo formatCurrency($item['mrp']); ?></td>
                    <td class="text-right"><?php echo $item['gst_rate']; ?>%</td>
                    <td class="text-right"><?php echo formatCurrency($item['taxable_amount']); ?></td>
                    <?php if ($invoice['gst_type'] === 'igst'): ?>
                        <td class="text-right"><?php echo formatCurrency($item['igst_amount']); ?></td>
                    <?php else: ?>
                        <td class="text-right"><?php echo formatCurrency($item['cgst_amount']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($item['sgst_amount']); ?></td>
                    <?php endif; ?>
                    <td class="text-right"><?php echo formatCurrency($item['total_amount']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="invoice-totals">
        <table>
            <tr>
                <td>Sub Total:</td>
                <td class="text-right">Rs. <?php echo formatCurrency($invoice['subtotal']); ?></td>
            </tr>
            <?php if ($invoice['gst_type'] === 'igst'): ?>
                <tr>
                    <td>IGST:</td>
                    <td class="text-right">Rs. <?php echo formatCurrency($invoice['total_igst']); ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td>CGST:</td>
                    <td class="text-right">Rs. <?php echo formatCurrency($invoice['total_cgst']); ?></td>
                </tr>
                <tr>
                    <td>SGST:</td>
                    <td class="text-right">Rs. <?php echo formatCurrency($invoice['total_sgst']); ?></td>
                </tr>
            <?php endif; ?>
            <tr class="grand-total">
                <td><strong>Grand Total:</strong></td>
                <td class="text-right"><strong>Rs. <?php echo formatCurrency($invoice['grand_total']); ?></strong></td>
            </tr>
            <tr>
                <td colspan="2" style="font-size: 12px; color: #666;"><?php echo e($invoice['amount_in_words']); ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if ($invoice['notes']): ?>
<div class="card">
    <div class="card-header">
        <h2>Notes</h2>
    </div>
    <p><?php echo nl2br(e($invoice['notes'])); ?></p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
