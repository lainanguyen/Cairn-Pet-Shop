<?php
require_once '../includes/config.php';
require_once '../includes/authentication.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure only employees can access this page
requireEmployee();

// Initialize variables for messages
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                // Handle profile update
                try {
                    $conn = connectDB();
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
                } catch (Exception $e) {
                    $error_message = "Error updating profile: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch employee information
try {
    $conn = connectDB();
    $stmt = prepareStatement($conn,
        "SELECT first_name, last_name, email, phone, role, created_at 
        FROM users 
        WHERE user_id = ?"
    );
    executeStatement($stmt, [$_SESSION['user_id']], "s");
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
} catch (Exception $e) {
    $error_message = "Error fetching employee data: " . $e->getMessage();
}

// Fetch recent animals added by this employee
try {
    $stmt = prepareStatement($conn,
        "SELECT * FROM animals 
        WHERE created_by = ? 
        ORDER BY created_at DESC 
        LIMIT 5"
    );
    executeStatement($stmt, [$_SESSION['user_id']], "s");
    $recentAnimals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching recent animals: " . $e->getMessage();
}

// Fetch pending adoption applications
try {
    $stmt = prepareStatement($conn,
        "SELECT aa.*, a.name as animal_name, u.first_name, u.last_name
        FROM adoption_applications aa
        JOIN animals a ON aa.animal_id = a.animal_id
        JOIN users u ON aa.user_id = u.user_id
        WHERE aa.status = 'pending'
        ORDER BY aa.application_date DESC
        LIMIT 5"
    );
    executeStatement($stmt, [], "");
    $pendingApplications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching pending applications: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dashboard-heading {
            font-size: 2rem;
            margin-bottom: 20px;
            color: #333;
        }

        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .action-button {
            padding: 12px 24px;
            background-color: #3693F0;
            color: white;
            border: none;
            border-radius: 300px;
            text-decoration: none;
            font-family: "Crimson Pro", serif;
            cursor: pointer;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3693F0;
        }

        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>


<div class="dashboard-container">
    <h1 class="dashboard-heading">Welcome, <?php echo htmlspecialchars($employee['first_name']); ?>!</h1>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="quick-actions">
        <a href="manage-animals.php?action=add" class="action-button">Add New Animal</a>
        <a href="manage-applications.php" class="action-button">View Applications</a>
    </div>

    <div class="dashboard-grid">
        <!-- Profile Section -->
        <div class="dashboard-card">
            <h2>Your Profile</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" name="first_name" id="first_name"
                           value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" name="last_name" id="last_name"
                           value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" name="phone" id="phone"
                           value="<?php echo htmlspecialchars($employee['phone']); ?>">
                </div>
                <button type="submit" class="action-button">Update Profile</button>
            </form>
        </div>

        <!-- Recent Animals Section -->
        <div class="dashboard-card">
            <h2>Recently Added Animals</h2>
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Species</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentAnimals as $animal): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($animal['name']); ?></td>
                        <td><?php echo htmlspecialchars($animal['species']); ?></td>
                        <td><?php echo htmlspecialchars($animal['status']); ?></td>
                        <td>
                            <a href="manage-animals.php?action=edit&id=<?php echo $animal['animal_id']; ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pending Applications Section -->
        <div class="dashboard-card">
            <h2>Pending Applications</h2>
            <table>
                <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Animal</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pendingApplications as $app): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($app['animal_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                        <td>
                            <a href="manage-applications.php?action=review&id=<?php echo $app['application_id']; ?>">Review</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>