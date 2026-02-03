<?php
/**
 * Monthly Sales Report
 * COSMIC SURGICALS - Invoice Management System
 */

$pageTitle = 'Monthly Report';
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$db = getDB();
$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));

$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = date('Y-m-t', strtotime($startDate));
$monthName = date('F Y', strtotime($startDate));

// Get daily breakdown
$stmt = $db->prepare("SELECT
    DATE(invoice_date) as date,
    COUNT(*) as count,
    SUM(subtotal) as subtotal,
    SUM(total_tax) as tax,
    SUM(grand_total) as total,
    SUM(CASE WHEN invoice_type = 'cash' THEN grand_total ELSE 0 END) as cash_total,
    SUM(CASE WHEN invoice_type = 'credit' THEN grand_total ELSE 0 END) as credit_total
    FROM invoices
    WHERE invoice_date BETWEEN ? AND ?
    GROUP BY DATE(invoice_date)
    ORDER BY date ASC");
$stmt->execute([$startDate, $endDate]);
$dailyData = $stmt->fetchAll();

// Get totals
$stmt = $db->prepare("SELECT
    COUNT(*) as count,
    COALESCE(SUM(subtotal), 0) as subtotal,
    COALESCE(SUM(total_tax), 0) as tax,
    COALESCE(SUM(grand_total), 0) as total,
    COALESCE(SUM(CASE WHEN invoice_type = 'cash' THEN grand_total ELSE 0 END), 0) as cash_total,
    COALESCE(SUM(CASE WHEN invoice_type = 'credit' THEN grand_total ELSE 0 END), 0) as credit_total
    FROM invoices
    WHERE invoice_date BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$totals = $stmt->fetch();

// Get top customers
$stmt = $db->prepare("SELECT
    customer_name,
    COUNT(*) as invoice_count,
    SUM(grand_total) as total
    FROM invoices
    WHERE invoice_date BETWEEN ? AND ?
    GROUP BY customer_name
    ORDER BY total DESC
    LIMIT 10");
$stmt->execute([$startDate, $endDate]);
$topCustomers = $stmt->fetchAll();

// Get top products
$stmt = $db->prepare("SELECT
    ii.description,
    SUM(ii.quantity) as total_qty,
    SUM(ii.total_amount) as total_amount
    FROM invoice_items ii
    JOIN invoices i ON ii.invoice_id = i.id
    WHERE i.invoice_date BETWEEN ? AND ?
    GROUP BY ii.description
    ORDER BY total_amount DESC
    LIMIT 10");
$stmt->execute([$startDate, $endDate]);
$topProducts = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Monthly Sales Report</h1>
    <a href="/xamp-cosmic/modules/reports/index.php" class="btn btn-secondary">Back to Reports</a>
</div>

<div class="card">
    <form method="GET" class="form-inline" style="margin-bottom: 20px;">
        <div class="form-group">
            <label style="margin-right: 10px;">Select Month:</label>
            <select name="month" class="form-control">
                <?php
                for ($m = 1; $m <= 12; $m++) {
                    $selected = $m == $month ? 'selected' : '';
                    echo "<option value=\"$m\" $selected>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <select name="year" class="form-control">
                <?php
                $currentYear = date('Y');
                for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                    $selected = $y == $year ? 'selected' : '';
                    echo "<option value=\"$y\" $selected>$y</option>";
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">View Report</button>
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print Report</button>
    </form>

    <h2 style="margin-bottom: 20px;">Sales Report for <?php echo $monthName; ?></h2>

    <!-- Summary -->
    <div class="stats-grid" style="margin-bottom: 25px;">
        <div class="stat-card purple">
            <h3>Total Sales</h3>
            <div class="value">Rs. <?php echo formatCurrency($totals['total']); ?></div>
            <div style="font-size: 13px; color: #666; margin-top: 5px;"><?php echo $totals['count']; ?> invoice(s)</div>
        </div>

        <div class="stat-card green">
            <h3>Cash Sales</h3>
            <div class="value">Rs. <?php echo formatCurrency($totals['cash_total']); ?></div>
        </div>

        <div class="stat-card orange">
            <h3>Credit Sales</h3>
            <div class="value">Rs. <?php echo formatCurrency($totals['credit_total']); ?></div>
        </div>

        <div class="stat-card blue">
            <h3>Total Tax</h3>
            <div class="value">Rs. <?php echo formatCurrency($totals['tax']); ?></div>
        </div>
    </div>
</div>

<!-- Daily Breakdown -->
<div class="card">
    <div class="card-header">
        <h2>Daily Breakdown</h2>
    </div>

    <?php if (empty($dailyData)): ?>
        <div class="empty-state">
            <h3>No sales data</h3>
            <p>No invoices found for <?php echo $monthName; ?></p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-right">Invoices</th>
                        <th class="text-right">Taxable</th>
                        <th class="text-right">Tax</th>
                        <th class="text-right">Cash</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dailyData as $day): ?>
                    <tr>
                        <td>
                            <a href="/xamp-cosmic/modules/reports/daily.php?date=<?php echo $day['date']; ?>">
                                <?php echo formatDate($day['date']); ?>
                            </a>
                        </td>
                        <td class="text-right"><?php echo $day['count']; ?></td>
                        <td class="text-right"><?php echo formatCurrency($day['subtotal']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($day['tax']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($day['cash_total']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($day['credit_total']); ?></td>
                        <td class="text-right"><strong><?php echo formatCurrency($day['total']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8f9fa; font-weight: 600;">
                        <td>Total</td>
                        <td class="text-right"><?php echo $totals['count']; ?></td>
                        <td class="text-right"><?php echo formatCurrency($totals['subtotal']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($totals['tax']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($totals['cash_total']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($totals['credit_total']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($totals['total']); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="form-row">
    <!-- Top Customers -->
    <div class="card" style="flex: 1;">
        <div class="card-header">
            <h2>Top Customers</h2>
        </div>

        <?php if (empty($topCustomers)): ?>
            <p style="color: #666;">No data available</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th class="text-right">Invoices</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topCustomers as $cust): ?>
                    <tr>
                        <td><?php echo e($cust['customer_name']); ?></td>
                        <td class="text-right"><?php echo $cust['invoice_count']; ?></td>
                        <td class="text-right"><?php echo formatCurrency($cust['total']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Top Products -->
    <div class="card" style="flex: 1;">
        <div class="card-header">
            <h2>Top Products</h2>
        </div>

        <?php if (empty($topProducts)): ?>
            <p style="color: #666;">No data available</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-right">Qty Sold</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topProducts as $prod): ?>
                    <tr>
                        <td><?php echo e($prod['description']); ?></td>
                        <td class="text-right"><?php echo $prod['total_qty']; ?></td>
                        <td class="text-right"><?php echo formatCurrency($prod['total_amount']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
