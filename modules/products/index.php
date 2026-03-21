<?php
/**
 * Products List
 * COSMIC SURGICALS - Invoice Management System
 */

$pageTitle = 'Products';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM products WHERE active = 1";
$params = [];

if ($search) {
    $sql .= " AND (name LIKE ? OR hsn_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Products</h1>
    <a href="/xamp-cosmic/modules/products/create.php" class="btn btn-primary">+ Add Product</a>
</div>

<div class="card">
    <form method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" placeholder="Search by name or HSN code..."
               value="<?php echo e($search); ?>">
        <button type="submit" class="btn btn-secondary">Search</button>
        <?php if ($search): ?>
            <a href="/xamp-cosmic/modules/products/index.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($products)): ?>
        <div class="empty-state">
            <h3>No products found</h3>
            <p><?php echo $search ? 'Try a different search term' : 'Add your first product to get started'; ?></p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>HSN Code</th>
                        <th class="text-right">MRP</th>
                        <th class="text-right">GST %</th>
                        <th>Unit</th>
                        <th class="text-right">Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo e($product['name']); ?></td>
                        <td><?php echo e($product['hsn_code']); ?></td>
                        <td class="text-right"><?php echo $product['mrp'] ? 'Rs. ' . formatCurrency($product['mrp']) : '-'; ?></td>
                        <td class="text-right"><?php echo $product['gst_rate']; ?>%</td>
                        <td><?php echo e($product['unit']); ?></td>
                        <td class="text-right"><?php echo (int)$product['stock_count']; ?></td>
                        <td class="actions">
                            <a href="/xamp-cosmic/modules/products/edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="btn btn-sm btn-danger">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top: 15px; color: #666; font-size: 13px;">Showing <?php echo count($products); ?> product(s)</p>
    <?php endif; ?>
</div>

<script>
function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        window.location.href = '/xamp-cosmic/modules/products/edit.php?id=' + id + '&delete=1';
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
