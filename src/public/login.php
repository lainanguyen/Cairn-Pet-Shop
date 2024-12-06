<?php
require_once '../includes/authentication.php';

// Redirect if already logged in
if (isLoggedIn()) {
    // Redirect based on role
    if (isAdmin()) {
        header("Location: ../manage-users.php");
    } elseif (isEmployee()) {
        header("Location: ../employee/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $loginType = $_POST['login_type']; // 'user' or 'employee'

    if (authenticateUser($email, $password)) {
        // Check if user is attempting employee login
        if ($loginType === 'employee' && !isEmployee()) {
            $error = "You don't have employee access.";
            logout(); // Log them out if they tried to access employee portal without rights
        } else {
            // Redirect based on role
            if (isAdmin()) {
                header("Location: ../admin/manage-users.php");
            } elseif (isEmployee()) {
                header("Location: ../employee/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 20px;
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
        .button {
            width: 100%;
            padding: 10px;
            background-color: #3693F0;
            color: white;
            border: none;
            border-radius: 300px;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-tabs {
            display: flex;
            margin-bottom: 20px;
        }
        .login-tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            border: none;
            outline: none;
        }
        .login-tab.active {
            background: #3693F0;
            color: white;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="login-container">
    <h2>Login</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="login-tabs">
            <button type="button" class="login-tab active" onclick="switchTab('user')">User Login</button>
            <button type="button" class="login-tab" onclick="switchTab('employee')">Employee Portal</button>
        </div>

        <input type="hidden" name="login_type" id="login_type" value="user">

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="button">Login</button>

        <div class="register-link" id="register-link">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </form>
</div>

<script>
    function switchTab(type) {
        // Update hidden input
        document.getElementById('login_type').value = type;

        // Update tab styling
        const tabs = document.getElementsByClassName('login-tab');
        for (let tab of tabs) {
            tab.classList.remove('active');
        }
        event.target.classList.add('active');

        // Show/hide register link based on type
        document.getElementById('register-link').style.display =
            type === 'user' ? 'block' : 'none';
    }
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>