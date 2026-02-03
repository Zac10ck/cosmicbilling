<?php
/**
 * Daily Sales Report
 * COSMIC SURGICALS - Invoice Management System
 */

$pageTitle = 'Daily Report';
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$db = getDB();
$date = $_GET['date'] ?? date('Y-m-d');

// Get invoices for the day
$stmt = $db->prepare("SELECT i.*, u.name as created_by_name
                      FROM invoices i
                      LEFT JOIN users u ON i.created_by = u.id
                      WHERE i.invoice_date = ?
                      ORDER BY i.created_at ASC");
$stmt->execute([$date]);
$invoices = $stmt->fetchAll();

// Calculate totals
$totalAmount = 0;
$totalTax = 0;
$cashCount = 0;
$creditCount = 0;
$cashTotal = 0;
$creditTotal = 0;

foreach ($invoices as $inv) {
    $totalAmount += $inv['grand_total'];
    $totalTax += $inv['total_tax'];

    if ($inv['invoice_type'] === 'cash') {
        $cashCount++;
        $cashTotal += $inv['grand_total'];
    } else {
        $creditCount++;
        $creditTotal += $inv['grand_total'];
    }
}
?>

<div class="page-header">
    <h1>Daily Sales Report</h1>
    <a href="/xamp-cosmic/modules/reports/index.php" class="btn btn-secondary">Back to Reports</a>
</div>

<div class="card">
    <form method="GET" class="form-inline" style="margin-bottom: 20px;">
        <div class="form-group">
            <label style="margin-right: 10px;">Select Date:</label>
            <input type="date" name="date" class="form-control" value="<?php echo e($date); ?>">
        </div>
        <button type="submit" class="btn btn-primary">View Report</button>
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print Report</button>
    </form>

    <h2 style="margin-bottom: 20px;">Sales Report for <?php echo formatDate($date); ?></h2>

    <!-- Summary -->
    <div class="stats-grid" style="margin-bottom: 25px;">
        <div class="stat-card purple">
            <h3>Total Sales</h3>
            <div class="value">Rs. <?php echo formatCurrency($totalAmount); ?></div>
            <div style="font-size: 13px; color: #666; margin-top: 5px;"><?php echo count($invoices); ?> invoice(s)</div>
        </div>

        <div class="stat-card green">
            <h3>Cash Sales</h3>
            <div class="value">Rs. <?php echo formatCurrency($cashTotal); ?></div>
            <div style="font-size: 13px; color: #666; margin-top: 5px;"><?php echo $cashCount; ?> invoice(s)</div>
        </div>

        <div class="stat-card orange">
            <h3>Credit Sales</h3>
            <div class="value">Rs. <?php echo formatCurrency($creditTotal); ?></div>
            <div style="font-size: 13px; color: #666; margin-top: 5px;"><?php echo $creditCount; ?> invoice(s)</div>
        </div>

        <div class="stat-card blue">
            <h3>Total Tax</h3>
            <div class="value">Rs. <?php echo formatCurrency($totalTax); ?></div>
        </div>
    </div>

    <?php if (empty($invoices)): ?>
        <div class="empty-state">
            <h3>No invoices found</h3>
            <p>No sales recorded for <?php echo formatDate($date); ?></p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Invoice #</th>
                        <th>Time</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th class="text-right">Taxable</th>
                        <th class="text-right">Tax</th>
                        <th class="text-right">Total</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $i => $inv): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td>
                            <a href="/xamp-cosmic/modules/invoices/view.php?id=<?php echo $inv['id']; ?>">
                                <?php echo e($inv['invoice_number']); ?>
                            </a>
                        </td>
                        <td><?php echo date('h:i A', strtotime($inv['created_at'])); ?></td>
                        <td><?php echo e($inv['customer_name']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $inv['invoice_type'] === 'cash' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($inv['invoice_type']); ?>
                            </span>
                        </td>
                        <td class="text-right"><?php echo formatCurrency($inv['subtotal']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($inv['total_tax']); ?></td>
                        <td class="text-right"><strong><?php echo formatCurrency($inv['grand_total']); ?></strong></td>
                        <td><?php echo e($inv['created_by_name']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8f9fa; font-weight: 600;">
                        <td colspan="5">Total</td>
                        <td class="text-right"><?php echo formatCurrency($totalAmount - $totalTax); ?></td>
                        <td class="text-right"><?php echo formatCurrency($totalTax); ?></td>
                        <td class="text-right"><?php echo formatCurrency($totalAmount); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
