<?php
/**
 * Add/Edit User
 * COSMIC SURGICALS - Invoice Management System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

// Handle toggle active status
if (isset($_GET['toggle'])) {
    $active = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE users SET active = ? WHERE id = ?");
    $stmt->execute([$active, $id]);
    redirect('/xamp-cosmic/modules/users/index.php', 'User status updated successfully');
}

$errors = [];
$user = [
    'username' => '',
    'name' => '',
    'role' => 'staff',
    'active' => 1
];

// Get existing user for edit
if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $existingUser = $stmt->fetch();

    if (!$existingUser) {
        redirect('/xamp-cosmic/modules/users/index.php', 'User not found', 'error');
    }

    $user = $existingUser;
}

$pageTitle = $isEdit ? 'Edit User' : 'Add User';
require_once __DIR__ . '/../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission';
    } else {
        $user = [
            'username' => trim($_POST['username'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'role' => $_POST['role'] ?? 'staff',
            'active' => isset($_POST['active']) ? 1 : 0
        ];

        // Validate
        if (empty($user['username'])) {
            $errors[] = 'Username is required';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $user['username'])) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        }

        if (empty($user['name'])) {
            $errors[] = 'Name is required';
        }

        // Check username uniqueness
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$user['username'], $id]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already exists';
        }

        // Password validation for new user or if password is being changed
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!$isEdit && empty($password)) {
            $errors[] = 'Password is required for new users';
        }

        if ($password && strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }

        if ($password && $password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }

        if (empty($errors)) {
            if ($isEdit) {
                // Update user
                if ($password) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET username = ?, name = ?, role = ?, active = ?, password = ? WHERE id = ?");
                    $stmt->execute([$user['username'], $user['name'], $user['role'], $user['active'], $hashedPassword, $id]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET username = ?, name = ?, role = ?, active = ? WHERE id = ?");
                    $stmt->execute([$user['username'], $user['name'], $user['role'], $user['active'], $id]);
                }
                redirect('/xamp-cosmic/modules/users/index.php', 'User updated successfully');
            } else {
                // Create user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password, name, role, active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user['username'], $hashedPassword, $user['name'], $user['role'], $user['active']]);
                redirect('/xamp-cosmic/modules/users/index.php', 'User created successfully');
            }
        }
    }
}
?>

<div class="page-header">
    <h1><?php echo $isEdit ? 'Edit User' : 'Add User'; ?></h1>
    <a href="/xamp-cosmic/modules/users/index.php" class="btn btn-secondary">Back to List</a>
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
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" class="form-control" required
                       pattern="[a-zA-Z0-9_]+"
                       value="<?php echo e($user['username']); ?>">
                <small style="color: #666;">Letters, numbers, and underscores only</small>
            </div>

            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" class="form-control" required
                       value="<?php echo e($user['name']); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password">Password <?php echo $isEdit ? '(leave blank to keep current)' : '*'; ?></label>
                <input type="password" id="password" name="password" class="form-control"
                       <?php echo $isEdit ? '' : 'required'; ?> minlength="6">
                <small style="color: #666;">Minimum 6 characters</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" class="form-control">
                    <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
                <small style="color: #666;">Admin has access to reports and user management</small>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <div style="padding-top: 8px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="active" <?php echo $user['active'] ? 'checked' : ''; ?>
                               style="margin-right: 8px; width: 18px; height: 18px;">
                        Active (can login)
                    </label>
                </div>
            </div>
        </div>

        <div class="mt-20">
            <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Update User' : 'Create User'; ?></button>
            <a href="/xamp-cosmic/modules/users/index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
