<?php
// public/register.php
require_once '../includes/authentication.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('/public/index.php');
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $phone = sanitizeInput($_POST['phone']);

        // Validate email
        if (!validateEmail($email)) {
            throw new Exception("Invalid email format");
        }

        // Validate phone (if provided)
        if ($phone && !validatePhone($phone)) {
            throw new Exception("Invalid phone number format");
        }

        // Validate password
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        if ($password !== $confirmPassword) {
            throw new Exception("Passwords do not match");
        }

        // Create the user account
        createUser($email, $password, $firstName, $lastName, 'user');

        // Set success message and redirect
        setFlashMessage('success', 'Account created successfully! Please log in.');
        redirectTo('/public/login.php');

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
    <title>Register - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .register-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 30px;
            color: #333;
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

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .button {
            width: 100%;
            padding: 12px;
            background-color: #3693F0;
            color: white;
            border: none;
            border-radius: 300px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #2a75d0;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #3693F0;
            text-decoration: none;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
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
    <h2 class="form-title">Create an Account</h2>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-grid">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required
                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required
                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
            </div>

            <div class="form-group full-width">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group full-width">
                <label for="phone">Phone Number (optional)</label>
                <input type="tel" id="phone" name="phone"
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                       placeholder="1234567890">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       minlength="8">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                       minlength="8">
            </div>
        </div>

        <button type="submit" class="button">Create Account</button>
    </form>

    <div class="login-link">
        Already have an account? <a href="login.php">Log in</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>