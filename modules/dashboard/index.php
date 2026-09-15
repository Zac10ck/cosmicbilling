<?php
/**
 * Admin Dashboard
 * COSMIC SURGICALS - Invoice Management System
 */

require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// Get stats
$todayStart = date('Y-m-d 00:00:00');
$monthStart = date('Y-m-01');

// Today's invoices count and total
$stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total FROM invoices WHERE created_at >= ?");
$stmt->execute([$todayStart]);
$today = $stmt->fetch();

// This month's invoices
$stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total FROM invoices WHERE invoice_date >= ?");
$stmt->execute([$monthStart]);
$month = $stmt->fetch();

// Total customers
$stmt = $db->query("SELECT COUNT(*) FROM customers WHERE active = 1");
$totalCustomers = $stmt->fetchColumn();

// Total products
$stmt = $db->query("SELECT COUNT(*) FROM products WHERE active = 1");
$totalProducts = $stmt->fetchColumn();

// Inventory alerts
$stmt = $db->query("SELECT
    SUM(stock_count <= low_stock_threshold AND stock_count > 0) AS low_count,
    SUM(stock_count <= 0) AS out_count
    FROM products WHERE active = 1");
$stockAlerts = $stmt->fetch();

// Recent invoices
$stmt = $db->query("SELECT i.*, u.name as created_by_name FROM invoices i
    LEFT JOIN users u ON i.created_by = u.id
    ORDER BY i.created_at DESC LIMIT 10");
$recentInvoices = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <a href="/xamp-cosmic/modules/invoices/create.php" class="btn btn-primary">+ New Invoice</a>
</div>

<div class="stats-grid">
    <div class="stat-card purple">
        <h3>Today's Sales</h3>
        <div class="value">Rs. <?php echo formatCurrency($today['total']); ?></div>
        <div style="font-size: 13px; color: #666; margin-top: 5px;"><?php echo $today['count']; ?> invoice(s)</div>
    </div>

    <div class="stat-card blue">
        <h3>This Month</h3>
        <div class="value">Rs. <?php echo formatCurrency($month['total']); ?></div>
        <div style="font-size: 13px; color: #666; margin-top: 5px;"><?php echo $month['count']; ?> invoice(s)</div>
    </div>

    <div class="stat-card green">
        <h3>Customers</h3>
        <div class="value"><?php echo $totalCustomers; ?></div>
    </div>

    <div class="stat-card orange">
        <h3>Products</h3>
        <div class="value"><?php echo $totalProducts; ?></div>
    </div>

    <a href="/xamp-cosmic/modules/inventory/index.php?filter=low" class="stat-card orange">
        <h3>Low Stock</h3>
        <div class="value"><?php echo (int)$stockAlerts['low_count']; ?></div>
    </a>

    <a href="/xamp-cosmic/modules/inventory/index.php?filter=out" class="stat-card red">
        <h3>Out of Stock</h3>
        <div class="value"><?php echo (int)$stockAlerts['out_count']; ?></div>
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h2>Recent Invoices</h2>
    </div>

    <?php if (empty($recentInvoices)): ?>
        <div class="empty-state">
            <h3>No invoices yet</h3>
            <p>Create your first invoice to get started</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th class="text-right">Amount</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentInvoices as $inv): ?>
                    <tr>
                        <td><a href="/xamp-cosmic/modules/invoices/view.php?id=<?php echo $inv['id']; ?>"><?php echo e($inv['invoice_number']); ?></a></td>
                        <td><?php echo formatDate($inv['invoice_date']); ?></td>
                        <td><?php echo e($inv['customer_name']); ?></td>
                        <td><span class="badge badge-<?php echo $inv['invoice_type'] === 'cash' ? 'success' : 'warning'; ?>"><?php echo ucfirst($inv['invoice_type']); ?></span></td>
                        <td class="text-right">Rs. <?php echo formatCurrency($inv['grand_total']); ?></td>
                        <td><?php echo e($inv['created_by_name']); ?></td>
                        <td class="actions">
                            <a href="/xamp-cosmic/modules/invoices/view.php?id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                            <a href="/xamp-cosmic/modules/invoices/print.php?id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-primary" target="_blank">Print</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
