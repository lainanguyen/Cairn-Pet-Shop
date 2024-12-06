<?php
require_once '../includes/config.php';
require_once '../includes/authentication.php';
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
            case 'add_user':
                // Validate inputs
                $email = sanitizeInput($_POST['email']);
                $firstName = sanitizeInput($_POST['first_name']);
                $lastName = sanitizeInput($_POST['last_name']);
                $role = sanitizeInput($_POST['role']);
                $password = $_POST['password'];

                if (!validateEmail($email)) {
                    throw new Exception("Invalid email format");
                }

                if (strlen($password) < 8) {
                    throw new Exception("Password must be at least 8 characters long");
                }

                if (!in_array($role, ['admin', 'employee', 'user'])) {
                    throw new Exception("Invalid role specified");
                }

                // Create the user
                createUser($email, $password, $firstName, $lastName, $role);
                $success_message = "User created successfully!";
                break;

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

            case 'delete_user':
                // Check if user has any associated records
                $stmt = prepareStatement($conn,
                    "SELECT COUNT(*) as count FROM adoption_applications WHERE user_id = ?"
                );
                executeStatement($stmt, [$userId], "s");
                $result = $stmt->get_result()->fetch_assoc();

                if ($result['count'] > 0) {
                    throw new Exception("Cannot delete user with existing applications");
                }

                // Delete the user
                $stmt = prepareStatement($conn,
                    "DELETE FROM users WHERE user_id = ?"
                );
                executeStatement($stmt, [$userId], "s");
                $success_message = "User deleted successfully!";
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
        "SELECT user_id, email, first_name, last_name, role, is_active, created_at, last_login,
       (SELECT COUNT(*) FROM adoption_applications WHERE user_id = u.user_id) as application_count,
       (SELECT COUNT(*) FROM saved_pets WHERE user_id = u.user_id) as saved_pets_count
FROM users u 
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

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .user-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .user-name {
            font-size: 1.2em;
            margin: 0;
        }

        .role-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .role-admin { background-color: #f8d7da; color: #721c24; }
        .role-employee { background-color: #d4edda; color: #155724; }
        .role-user { background-color: #cce5ff; color: #004085; }

        .user-details {
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .detail-label {
            color: #666;
        }

        .status-active {
            color: #155724;
        }

        .status-inactive {
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .button {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.2s;
        }

        .button-primary {
            background-color: #3693F0;
            color: white;
        }

        .button-danger {
            background-color: #dc3545;
            color: white;
        }

        .add-user-form {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="users-container">
    <h2>Manage Users</h2>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Add New User Form -->
    <div class="add-user-form">
        <h3>Add New User</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_user">

            <div class="form-grid">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="user">User</option>
                        <option value="employee">Employee</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="button button-primary">Add User</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Users Grid -->
    <div class="grid-container">
        <?php foreach ($users as $user): ?>
            <div class="user-card">
                <div class="user-header">
                    <h3 class="user-name">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </h3>
                    <span class="role-badge role-<?php echo $user['role']; ?>">
                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                    </span>
                </div>

                <div class="user-details">
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
        </span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Created:</span>
                        <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>

                    <?php if ($user['last_login']): ?>
                        <div class="detail-row">
                            <span class="detail-label">Last Login:</span>
                            <span><?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="detail-row">
                        <span class="detail-label">Applications:</span>
                        <span><?php echo $user['application_count']; ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Saved Pets:</span>
                        <span><?php echo $user['saved_pets_count']; ?></span>
                    </div>
                </div>

                <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                    <input type="hidden" name="action" value="update_role">

                    <div class="form-group">
                        <label for="role-<?php echo $user['user_id']; ?>">Change Role:</label>
                        <select name="role" id="role-<?php echo $user['user_id']; ?>"
                                onchange="this.form.submit()"
                            <?php echo $user['user_id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="employee" <?php echo $user['role'] === 'employee' ? 'selected' : ''; ?>>Employee</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                </form>

                <div class="action-buttons">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <button type="submit" class="button button-primary"
                            <?php echo $user['user_id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                            <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                    </form>

                    <?php if ($user['application_count'] == 0 && $user['user_id'] !== $_SESSION['user_id']): ?>
                        <form method="POST" style="display: inline;"
                              onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                            <input type="hidden" name="action" value="delete_user">
                            <button type="submit" class="button button-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>