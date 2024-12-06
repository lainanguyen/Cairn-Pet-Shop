<?php
require_once 'authentication.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <?php if (isset($additionalStyles)) echo $additionalStyles; ?>
</head>
<body>
    <header>
        <div class="logo">
            <a href="../public/index.php">
                <img src="../assets/images/logo.png" alt="Blue Collar Pets Logo">
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="../public/animals.php">Our Pets</a></li>
                <li><a href="../public/reviews.php">Reviews</a></li>
                <li><a href="../public/contact.php">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'employee'): ?>
                        <li><a href="../employee/dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="../includes/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="../public/login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>