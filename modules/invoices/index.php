<?php
/**
 * Invoices List
 * COSMIC SURGICALS - Invoice Management System
 */

$pageTitle = 'Invoices';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// Search parameters
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Build query
$sql = "SELECT i.*, u.name as created_by_name FROM invoices i
        LEFT JOIN users u ON i.created_by = u.id
        WHERE i.invoice_date BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];

if ($search) {
    $sql .= " AND (i.invoice_number LIKE ? OR i.customer_name LIKE ? OR i.customer_phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY i.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Calculate totals
$totalAmount = array_sum(array_column($invoices, 'grand_total'));
?>

<div class="page-header">
    <h1>Invoices</h1>
    <a href="/xamp-cosmic/modules/invoices/create.php" class="btn btn-primary">+ New Invoice</a>
</div>

<div class="card">
    <form method="GET" class="search-bar" style="flex-wrap: wrap;">
        <div class="form-group" style="margin-bottom: 0;">
            <input type="date" name="date_from" class="form-control" value="<?php echo e($dateFrom); ?>">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <input type="date" name="date_to" class="form-control" value="<?php echo e($dateTo); ?>">
        </div>
        <div class="form-group" style="margin-bottom: 0; flex: 1;">
            <input type="text" name="search" class="form-control"
                   placeholder="Search by invoice #, customer name or phone..."
                   value="<?php echo e($search); ?>">
        </div>
        <button type="submit" class="btn btn-secondary">Search</button>
        <a href="/xamp-cosmic/modules/invoices/index.php" class="btn btn-secondary">Reset</a>
    </form>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">
            Invoice saved successfully!
            <?php if (isset($_GET['email']) && $_GET['email'] === 'sent'): ?>
                <br><small>Email notification sent to cosmicsurgical@gmail.com</small>
            <?php elseif (isset($_GET['email']) && $_GET['email'] === 'failed'): ?>
                <br><small style="color: #856404;">Email notification could not be sent (invoice was still saved)</small>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($invoices)): ?>
        <div class="empty-state">
            <h3>No invoices found</h3>
            <p>Try adjusting your search criteria or create a new invoice</p>
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
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td>
                            <a href="/xamp-cosmic/modules/invoices/view.php?id=<?php echo $inv['id']; ?>">
                                <?php echo e($inv['invoice_number']); ?>
                            </a>
                        </td>
                        <td><?php echo formatDate($inv['invoice_date']); ?></td>
                        <td>
                            <?php echo e($inv['customer_name']); ?>
                            <?php if ($inv['customer_phone']): ?>
                                <br><small style="color: #666;"><?php echo e($inv['customer_phone']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            // Define badge colors for different invoice types
                            $typeBadges = [
                                'cash' => 'success',
                                'upi' => 'primary',
                                'card' => 'info',
                                'bank' => 'secondary',
                                'credit' => 'warning'
                            ];
                            $typeLabels = [
                                'cash' => 'Cash',
                                'upi' => 'UPI',
                                'card' => 'Card',
                                'bank' => 'Bank',
                                'credit' => 'Credit'
                            ];
                            $badgeClass = $typeBadges[$inv['invoice_type']] ?? 'secondary';
                            $typeLabel = $typeLabels[$inv['invoice_type']] ?? ucfirst($inv['invoice_type']);
                            ?>
                            <span class="badge badge-<?php echo $badgeClass; ?>">
                                <?php echo $typeLabel; ?>
                            </span>
                        </td>
                        <td class="text-right">Rs. <?php echo formatCurrency($inv['grand_total']); ?></td>
                        <td><?php echo e($inv['created_by_name']); ?></td>
                        <td class="actions">
                            <a href="/xamp-cosmic/modules/invoices/view.php?id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                            <a href="/xamp-cosmic/modules/invoices/print.php?id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-primary" target="_blank">Print</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8f9fa; font-weight: 600;">
                        <td colspan="4">Total (<?php echo count($invoices); ?> invoices)</td>
                        <td class="text-right">Rs. <?php echo formatCurrency($totalAmount); ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
