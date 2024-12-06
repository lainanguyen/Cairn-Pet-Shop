<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure only admins can access this page
requireAdmin();

$success_message = '';
$error_message = '';

// Handle user actions (activate/deactivate, change role)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $userId = sanitizeInput($_POST['user_id']);
        $action = sanitizeInput($_POST['action']);

        $conn = connectDB();

        switch ($action) {
            case 'toggle_status':
                $stmt = prepareStatement($conn,
                    "UPDATE users SET is_active = NOT is_active WHERE user_id = ?"
                );
                executeStatement($stmt, [$userId], "s");
                $success_message = "User status updated successfully!";
                break;

            case 'update_role':
                $newRole = sanitizeInput($_POST['role']);
                if (!in_array($newRole, ['admin', 'employee', 'user'])) {
                    throw new Exception("Invalid role specified");
                }

                $stmt = prepareStatement($conn,
                    "UPDATE users SET role = ? WHERE user_id = ?"
                );
                executeStatement($stmt, [$newRole, $userId], "ss");
                $success_message = "User role updated successfully!";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch all users
try {
    $conn = connectDB();
    $stmt = prepareStatement($conn,
        "SELECT user_id, email, first_name, last_name, role, is_active, created_at 
         FROM users 
         ORDER BY created_at DESC"
    );
    executeStatement($stmt, [], "");
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching users: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .users-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .users-table th {
            background-color: #f8f9fa;
        }
        .action-button {
            padding: 8px 16px;
            border: none;
            border-radius: 300px;
            cursor: pointer;
            margin-right: 5px;
        }
        .action-button.activate {
            background-color: #28a745;
            color: white;
        }
        .action-button.deactivate {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="users-container">
    <h2>Manage Users</h2>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <table class="users-table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        <input type="hidden" name="action" value="update_role">
                        <select name="role" onchange="this.form.submit()">
                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="employee" <?php echo $user['role'] === 'employee' ? 'selected' : ''; ?>>Employee</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </form>
                </td>
                <td><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></td>
                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <button type="submit" class="action-button <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?>">
                            <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
