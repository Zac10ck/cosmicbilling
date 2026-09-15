<?php
/**
 * Reports Dashboard
 * COSMIC SURGICALS - Invoice Management System
 */

require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$pageTitle = 'Reports';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// Get today's stats
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total FROM invoices WHERE invoice_date = ?");
$stmt->execute([$today]);
$todayStats = $stmt->fetch();

// Get this month's stats
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total FROM invoices WHERE invoice_date BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$monthStats = $stmt->fetch();

// Get this year's stats
$yearStart = date('Y-01-01');
$yearEnd = date('Y-12-31');
$stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total FROM invoices WHERE invoice_date BETWEEN ? AND ?");
$stmt->execute([$yearStart, $yearEnd]);
$yearStats = $stmt->fetch();
?>

<div class="page-header">
    <h1>Reports</h1>
</div>

<div class="stats-grid">
    <div class="stat-card purple">
        <h3>Today's Sales</h3>
        <div class="value">Rs. <?php echo formatCurrency($todayStats['total']); ?></div>
        <div style="font-size: 13px; color: #666; margin-top: 5px;"><?php echo $todayStats['count']; ?> invoice(s)</div>
    </div>

    <div class="stat-card blue">
        <h3>This Month</h3>
        <div class="value">Rs. <?php echo formatCurrency($monthStats['total']); ?></div>
        <div style="font-size: 13px; color: #666; margin-top: 5px;"><?php echo $monthStats['count']; ?> invoice(s)</div>
    </div>

    <div class="stat-card green">
        <h3>This Year</h3>
        <div class="value">Rs. <?php echo formatCurrency($yearStats['total']); ?></div>
        <div style="font-size: 13px; color: #666; margin-top: 5px;"><?php echo $yearStats['count']; ?> invoice(s)</div>
    </div>
</div>

<div class="form-row">
    <div class="card" style="flex: 1;">
        <div class="card-header">
            <h2>Daily Report</h2>
        </div>
        <p style="color: #666; margin-bottom: 15px;">View detailed sales report for a specific date.</p>
        <form action="/xamp-cosmic/modules/reports/daily.php" method="GET" class="form-inline">
            <div class="form-group">
                <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <button type="submit" class="btn btn-primary">View Report</button>
        </form>
    </div>

    <div class="card" style="flex: 1;">
        <div class="card-header">
            <h2>Monthly Report</h2>
        </div>
        <p style="color: #666; margin-bottom: 15px;">View monthly sales summary and breakdown.</p>
        <form action="/xamp-cosmic/modules/reports/monthly.php" method="GET" class="form-inline">
            <div class="form-group">
                <select name="month" class="form-control">
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                        $selected = $m == date('n') ? 'selected' : '';
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
                        echo "<option value=\"$y\">$y</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">View Report</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
