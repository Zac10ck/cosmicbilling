<?php
/**
 * Users List
 * COSMIC SURGICALS - Invoice Management System
 */

$pageTitle = 'Users';
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$db = getDB();

// Get all users
$stmt = $db->query("SELECT * FROM users ORDER BY name ASC");
$users = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>User Management</h1>
    <a href="/xamp-cosmic/modules/users/create.php" class="btn btn-primary">+ Add User</a>
</div>

<div class="card">
    <?php if (empty($users)): ?>
        <div class="empty-state">
            <h3>No users found</h3>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo e($user['username']); ?></td>
                        <td><?php echo e($user['name']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'info' : 'success'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDate($user['created_at']); ?></td>
                        <td class="actions">
                            <a href="/xamp-cosmic/modules/users/create.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button onclick="toggleUser(<?php echo $user['id']; ?>, <?php echo $user['active'] ? 0 : 1; ?>)"
                                        class="btn btn-sm <?php echo $user['active'] ? 'btn-danger' : 'btn-success'; ?>">
                                    <?php echo $user['active'] ? 'Disable' : 'Enable'; ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleUser(id, active) {
    const action = active ? 'enable' : 'disable';
    if (confirm(`Are you sure you want to ${action} this user?`)) {
        window.location.href = `/xamp-cosmic/modules/users/create.php?id=${id}&toggle=${active}`;
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
