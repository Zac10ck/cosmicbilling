<?php
/**
 * Customers List
 * COSMIC SURGICALS - Invoice Management System
 */

$pageTitle = 'Customers';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM customers WHERE active = 1";
$params = [];

if ($search) {
    $sql .= " AND (name LIKE ? OR phone LIKE ? OR gstin LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Customers</h1>
    <a href="/xamp-cosmic/modules/customers/create.php" class="btn btn-primary">+ Add Customer</a>
</div>

<div class="card">
    <form method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" placeholder="Search by name, phone or GSTIN..."
               value="<?php echo e($search); ?>">
        <button type="submit" class="btn btn-secondary">Search</button>
        <?php if ($search): ?>
            <a href="/xamp-cosmic/modules/customers/index.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($customers)): ?>
        <div class="empty-state">
            <h3>No customers found</h3>
            <p><?php echo $search ? 'Try a different search term' : 'Add your first customer to get started'; ?></p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>GSTIN</th>
                        <th>State</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $states = getStateCodes();
                    foreach ($customers as $customer):
                    ?>
                    <tr>
                        <td><?php echo e($customer['name']); ?></td>
                        <td><?php echo e($customer['phone']); ?></td>
                        <td><?php echo e($customer['gstin']); ?></td>
                        <td><?php echo e($states[$customer['state_code']] ?? $customer['state_code']); ?></td>
                        <td class="actions">
                            <a href="/xamp-cosmic/modules/customers/edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <button onclick="deleteCustomer(<?php echo $customer['id']; ?>)" class="btn btn-sm btn-danger">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top: 15px; color: #666; font-size: 13px;">Showing <?php echo count($customers); ?> customer(s)</p>
    <?php endif; ?>
</div>

<script>
function deleteCustomer(id) {
    if (confirm('Are you sure you want to delete this customer?')) {
        window.location.href = '/xamp-cosmic/modules/customers/edit.php?id=' + id + '&delete=1';
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
