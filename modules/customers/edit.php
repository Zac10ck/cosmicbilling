<?php
/**
 * Edit Customer
 * COSMIC SURGICALS - Invoice Management System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    redirect('/xamp-cosmic/modules/customers/index.php', 'Invalid customer', 'error');
}

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("UPDATE customers SET active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    redirect('/xamp-cosmic/modules/customers/index.php', 'Customer deleted successfully');
}

// Get customer
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ? AND active = 1");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    redirect('/xamp-cosmic/modules/customers/index.php', 'Customer not found', 'error');
}

$pageTitle = 'Edit Customer';
require_once __DIR__ . '/../../includes/header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission';
    } else {
        $customer = [
            'id' => $id,
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'gstin' => strtoupper(trim($_POST['gstin'] ?? '')),
            'address' => trim($_POST['address'] ?? ''),
            'state_code' => $_POST['state_code'] ?? '32'
        ];

        // Validate
        if (empty($customer['name'])) {
            $errors[] = 'Customer name is required';
        }

        // Validate GSTIN format if provided
        if ($customer['gstin'] && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $customer['gstin'])) {
            $errors[] = 'Invalid GSTIN format';
        }

        if (empty($errors)) {
            $stmt = $db->prepare("UPDATE customers SET name = ?, phone = ?, email = ?, gstin = ?, address = ?, state_code = ? WHERE id = ?");
            $stmt->execute([
                $customer['name'],
                $customer['phone'] ?: null,
                $customer['email'] ?: null,
                $customer['gstin'] ?: null,
                $customer['address'] ?: null,
                $customer['state_code'],
                $id
            ]);

            redirect('/xamp-cosmic/modules/customers/index.php', 'Customer updated successfully');
        }
    }
}

$states = getStateCodes();
?>

<div class="page-header">
    <h1>Edit Customer</h1>
    <a href="/xamp-cosmic/modules/customers/index.php" class="btn btn-secondary">Back to List</a>
</div>

<div class="card">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?php echo e($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <?php echo csrfField(); ?>

        <div class="form-row">
            <div class="form-group">
                <label for="name">Customer Name *</label>
                <input type="text" id="name" name="name" class="form-control" required
                       value="<?php echo e($customer['name']); ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" class="form-control"
                       value="<?php echo e($customer['phone']); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?php echo e($customer['email']); ?>">
            </div>

            <div class="form-group">
                <label for="gstin">GSTIN</label>
                <input type="text" id="gstin" name="gstin" class="form-control" maxlength="15"
                       style="text-transform: uppercase;"
                       value="<?php echo e($customer['gstin']); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="state_code">State</label>
                <select id="state_code" name="state_code" class="form-control">
                    <?php foreach ($states as $code => $name): ?>
                        <option value="<?php echo $code; ?>" <?php echo $customer['state_code'] === $code ? 'selected' : ''; ?>>
                            <?php echo e($code . ' - ' . $name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" class="form-control" rows="3"><?php echo e($customer['address']); ?></textarea>
        </div>

        <div class="mt-20">
            <button type="submit" class="btn btn-primary">Update Customer</button>
            <a href="/xamp-cosmic/modules/customers/index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
