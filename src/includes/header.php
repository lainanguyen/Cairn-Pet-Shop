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
    <style>
        .user-menu {
            position: relative;
            display: inline-block;
        }

        .user-menu .dropdown-content {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 220px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            z-index: 1000;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .user-menu:hover .dropdown-content,
        .dropdown-content:hover {
            visibility: visible;
            opacity: 1;
        }

        .dropdown-content::before {
            content: '';
            position: absolute;
            top: -20px; /* Increased height to ensure no gap */
            left: 0;
            right: 0;
            height: 20px;
            background: transparent;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 1.2rem;
            transition: background-color 0.2s ease;
        }

        .dropdown-content a:hover {
            background-color: #f8f9fa;
        }

        .dropdown-content a:first-child {
            border-radius: 10px 10px 0 0;
        }

        .dropdown-content a:last-child {
            border-radius: 0 0 10px 10px;
        }

        .user-name {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px;
            border-radius: 20px;
            transition: background-color 0.2s ease;
        }

        .user-name:hover {
            background-color: #f8f9fa;
        }

        .user-icon {
            width: 32px;
            height: 32px;
            background-color: #3693F0;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .dropdown-divider {
            height: 1px;
            background-color: #eee;
            margin: 5px 0;
        }

        .role-badge {
            font-size: 0.8rem;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 5px;
            background-color: #e9ecef;
            color: #666;
        }

        .admin .user-icon {
            background-color: #dc3545;
        }

        .employee .user-icon {
            background-color: #28a745;
        }
    </style>
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
                <li class="user-menu <?php echo isAdmin() ? 'admin' : (isEmployee() ? 'employee' : ''); ?>">
                    <a href="#" class="user-name">
                            <span class="user-icon">
                                <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                            </span>
                        <?php echo htmlspecialchars($_SESSION['name']); ?>
                        <?php if (isAdmin()): ?>
                            <span class="role-badge">Admin</span>
                        <?php elseif (isEmployee()): ?>
                            <span class="role-badge">Employee</span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-content">
                        <?php if (isAdmin()): ?>
                            <!-- Admin Menu -->
                            <a href="../employee/dashboard.php">Dashboard</a>
                            <a href="../admin/manage-users.php">Manage Users</a>
                            <a href="../employee/manage-animals.php">Manage Animals</a>
                            <a href="../employee/manage-applications.php">Review Applications</a>
                            <div class="dropdown-divider"></div>
                            <a href="../public/profile.php">My Profile</a>
                            <a href="../public/logout.php">Logout</a>
                        <?php elseif (isEmployee()): ?>
                            <!-- Employee Menu -->
                            <a href="../employee/dashboard.php">Dashboard</a>
                            <a href="../employee/manage-animals.php">Manage Animals</a>
                            <a href="../employee/manage-applications.php">Review Applications</a>
                            <div class="dropdown-divider"></div>
                            <a href="../public/profile.php">My Profile</a>
                            <a href="../public/logout.php">Logout</a>
                        <?php else: ?>
                            <!-- Regular User Menu -->
                            <a href="../public/profile.php">My Profile</a>
                            <a href="../public/application-status.php">My Applications</a>
                            <a href="../public/saved-pets.php">Saved Pets</a>
                            <div class="dropdown-divider"></div>
                            <a href="../public/logout.php">Logout</a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="../public/login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>