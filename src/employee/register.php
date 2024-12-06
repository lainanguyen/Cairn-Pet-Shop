<?php
require_once '../includes/authentication.php';

// Ensure only admins can access this page
//requireAdmin();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $role = sanitizeInput($_POST['role']);

        // Validate role (ensure only valid roles are used)
        if (!in_array($role, ['employee', 'admin'])) {
            throw new Exception("Invalid role specified");
        }

        // Create the employee account
        createUser($email, $password, $firstName, $lastName, $role);
        $success_message = "Employee account created successfully!";

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Employee Account - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .register-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .button {
            padding: 12px 24px;
            background-color: #3693F0;
            color: white;
            border: none;
            border-radius: 300px;
            cursor: pointer;
            width: 100%;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="register-container">
    <h2>Create Employee Account</h2>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-grid">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>

            <div class="form-group full-width">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="employee">Employee</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
        </div>

        <button type="submit" class="button">Create Employee Account</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>