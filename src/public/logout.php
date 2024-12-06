<?php
require_once '../includes/config.php';
require_once '../includes/authentication.php';
require_once '../includes/functions.php';

// Handle the actual logout if confirmed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    try {
        // Log the logout event if user was logged in
        if (isset($_SESSION['user_id'])) {
            $conn = connectDB();
            $stmt = prepareStatement($conn,
                "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?"
            );
            executeStatement($stmt, [$_SESSION['user_id']], "s");
        }

        // Clear all session variables
        $_SESSION = array();

        // Get session cookie parameters
        $params = session_get_cookie_params();

        // Delete the session cookie
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);

        // Destroy the session
        session_destroy();

        // Clear any other application-specific cookies
        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', time() - 3600, '/');
        }

        // Redirect with success message
        setFlashMessage('success', 'You have been successfully logged out.');
        header("Location: ../public/login.php");
        exit();

    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
        session_destroy();
        setFlashMessage('error', 'An error occurred during logout, but you have been logged out successfully.');
        header("Location: ../public/login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .logout-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .logout-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 20px;
        }

        .logout-message {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .button-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 30px;
        }

        .button {
            padding: 12px 24px;
            border-radius: 300px;
            font-family: "Crimson Pro", serif;
            font-size: 1.1rem;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .button-primary {
            background-color: #3693F0;
            color: white;
            border: none;
        }

        .button-secondary {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }

        .links-container {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .links-title {
            color: #666;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .quick-links {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .quick-link {
            color: #3693F0;
            text-decoration: none;
            font-size: 1.1rem;
        }

        .quick-link:hover {
            text-decoration: underline;
        }

        .user-info {
            color: #666;
            margin-bottom: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="logout-container">
    <h1 class="logout-title">Ready to Log Out?</h1>

    <?php if (isLoggedIn()): ?>
        <div class="user-info">
            Logged in as <?php echo htmlspecialchars($_SESSION['name']); ?>
        </div>
    <?php endif; ?>

    <p class="logout-message">
        Are you sure you want to log out? You'll need to log in again to access your account features.
    </p>

    <div class="button-container">
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="button button-primary">Yes, Log Out</button>
        </form>
        <a href="<?php echo isEmployee() ? '../employee/dashboard.php' : '../public/index.php'; ?>"
           class="button button-secondary">No, Take Me Back</a>
    </div>

    <div class="links-container">
        <h2 class="links-title">Before you go, you might want to visit:</h2>
        <div class="quick-links">
            <a href="animals.php" class="quick-link">Available Pets</a>
            <a href="contact.php" class="quick-link">Contact Us</a>
            <a href="reviews.php" class="quick-link">Reviews</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>