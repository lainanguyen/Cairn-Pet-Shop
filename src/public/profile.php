<?php
require_once '../includes/authentication.php';

// Ensure user is logged in
requireLogin();

$success_message = '';
$error_message = '';
$user = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = connectDB();

        switch ($_POST['action']) {
            case 'update_profile':
                // Update user information
                $stmt = prepareStatement($conn,
                    "UPDATE users SET 
                        first_name = ?, 
                        last_name = ?, 
                        phone = ? 
                    WHERE user_id = ?"
                );
                executeStatement($stmt, [
                    sanitizeInput($_POST['first_name']),
                    sanitizeInput($_POST['last_name']),
                    sanitizeInput($_POST['phone']),
                    $_SESSION['user_id']
                ], "ssss");

                $success_message = "Profile updated successfully!";
                break;

            case 'change_password':
                // Verify current password
                $stmt = prepareStatement($conn,
                    "SELECT password_hash FROM users WHERE user_id = ?"
                );
                executeStatement($stmt, [$_SESSION['user_id']], "s");
                $result = $stmt->get_result()->fetch_assoc();

                if (!password_verify($_POST['current_password'], $result['password_hash'])) {
                    throw new Exception("Current password is incorrect");
                }

                // Validate new password
                if ($_POST['new_password'] !== $_POST['confirm_password']) {
                    throw new Exception("New passwords do not match");
                }

                if (strlen($_POST['new_password']) < 8) {
                    throw new Exception("New password must be at least 8 characters long");
                }

                // Update password
                $stmt = prepareStatement($conn,
                    "UPDATE users SET password_hash = ? WHERE user_id = ?"
                );
                executeStatement($stmt, [
                    password_hash($_POST['new_password'], PASSWORD_DEFAULT),
                    $_SESSION['user_id']
                ], "ss");

                $success_message = "Password updated successfully!";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch user information
try {
    $conn = connectDB();
    $stmt = prepareStatement($conn,
        "SELECT first_name, last_name, email, phone, created_at, last_login
         FROM users WHERE user_id = ?"
    );
    executeStatement($stmt, [$_SESSION['user_id']], "s");
    $user = $stmt->get_result()->fetch_assoc();

    // Fetch user's saved pets
    $stmt = prepareStatement($conn,
        "SELECT a.*, ai.image_url
         FROM animals a
         LEFT JOIN animal_images ai ON a.animal_id = ai.animal_id AND ai.is_primary = TRUE
         WHERE a.animal_id IN (
             SELECT animal_id 
             FROM adoption_applications 
             WHERE user_id = ? AND status != 'withdrawn'
         )"
    );
    executeStatement($stmt, [$_SESSION['user_id']], "s");
    $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching user data: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-section {
            margin-bottom: 30px;
        }

        .profile-section h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .button {
            padding: 10px 20px;
            background-color: #3693F0;
            color: white;
            border: none;
            border-radius: 300px;
            cursor: pointer;
        }

        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .animal-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .animal-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .animal-info {
            padding: 15px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            margin-top: 10px;
        }

        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="profile-container">
    <h2>My Profile</h2>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="profile-grid">
        <div>
            <!-- Personal Information -->
            <div class="profile-card">
                <div class="profile-section">
                    <h3>Personal Information</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?php echo htmlspecialchars($user['phone']); ?>"
                                   placeholder="1234567890">
                        </div>

                        <button type="submit" class="button">Update Profile</button>
                    </form>
                </div>

                <!-- Change Password Section -->
                <div class="profile-section">
                    <h3>Change Password</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>

                        <button type="submit" class="button">Change Password</button>
                    </form>
                </div>

                <!-- Account Information -->
                <div class="profile-section">
                    <h3>Account Information</h3>
                    <div class="form-group">
                        <label>Member Since</label>
                        <div><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                    <div class="form-group">
                        <label>Last Login</label>
                        <div><?php echo $user['last_login'] ? date('F j, Y g:i a', strtotime($user['last_login'])) : 'N/A'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications and Saved Pets Section -->
        <div>
            <div class="profile-card">
                <h3>My Applications</h3>
                <?php if (empty($applications)): ?>
                    <p>You haven't submitted any adoption applications yet.</p>
                <?php else: ?>
                    <div class="applications-grid">
                        <?php foreach ($applications as $app): ?>
                            <div class="animal-card">
                                <?php if ($app['image_url']): ?>
                                    <img src="../uploads/animals/<?php echo htmlspecialchars($app['image_url']); ?>"
                                         alt="<?php echo htmlspecialchars($app['name']); ?>"
                                         class="animal-image">
                                <?php endif; ?>

                                <div class="animal-info">
                                    <h4><?php echo htmlspecialchars($app['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($app['breed']); ?> •
                                        <?php echo htmlspecialchars($app['age_years']); ?> years</p>

                                    <span class="status-badge status-<?php echo $app['status']; ?>">
                                            <?php echo ucfirst(htmlspecialchars($app['status'])); ?>
                                        </span>

                                    <?php if ($app['status'] === 'pending'): ?>
                                        <form method="POST" style="margin-top: 10px;">
                                            <input type="hidden" name="action" value="withdraw_application">
                                            <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                            <button type="submit" class="button"
                                                    onclick="return confirm('Are you sure you want to withdraw this application?')">
                                                Withdraw Application
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>