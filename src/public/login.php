<?php
require_once 'config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (authenticateUser($email, $password)) {
        // Redirect based on role
        if (isAdmin()) {
            header("Location: admin/dashboard.php");
        } else if (isEmployee()) {
            header("Location: employee/dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit();
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
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (!empty($error)) { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <?php if (isEmployee()) { ?>
            <p>Employee? <a href="employee/register.php">Create Account</a></p>
        <?php } ?>
    </div>
</body>

</html>