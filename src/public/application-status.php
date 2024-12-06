<?php
require_once '../includes/config.php';
require_once '../includes/authentication.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure user is logged in
requireLogin();

$success_message = '';
$error_message = '';

try {
    $conn = connectDB();

    // Updated query with proper animal_images join
    $stmt = prepareStatement($conn,
        "SELECT aa.*, 
                a.name as animal_name, 
                a.species,
                a.breed,
                ai.image_url,
                CONCAT(rev.first_name, ' ', rev.last_name) as reviewer_name,
                a.status as animal_status
         FROM adoption_applications aa
         JOIN animals a ON aa.animal_id = a.animal_id
         LEFT JOIN animal_images ai ON a.animal_id = ai.animal_id AND ai.is_primary = TRUE
         LEFT JOIN users rev ON aa.reviewed_by = rev.user_id
         WHERE aa.user_id = ?
         ORDER BY aa.application_date DESC"
    );
    executeStatement($stmt, [$_SESSION['user_id']], "s");
    $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Add debug output
    if (empty($applications)) {
        error_log("No applications found for user_id: " . $_SESSION['user_id']);
    }

} catch (Exception $e) {
    error_log("Error fetching applications: " . $e->getMessage());
    $error_message = "An error occurred while fetching your applications.";
}

// Let's also add some debug code to verify the session
if (!isset($_SESSION['user_id'])) {
    error_log("No user_id in session when accessing application status");
    header("Location: ../public/login.php");
    exit();
}

// Handle application withdrawal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'withdraw') {
    try {
        $application_id = sanitizeInput($_POST['application_id']);

        // Start transaction
        $conn->begin_transaction();

        // Update application status
        $stmt = prepareStatement($conn,
            "UPDATE adoption_applications 
             SET status = 'withdrawn' 
             WHERE application_id = ? AND user_id = ? AND status = 'pending'"
        );
        executeStatement($stmt, [$application_id, $_SESSION['user_id']], "ss");

        // If application was withdrawn, update animal status back to available
        if ($stmt->affected_rows > 0) {
            $stmt = prepareStatement($conn,
                "UPDATE animals a
                 JOIN adoption_applications aa ON a.animal_id = aa.animal_id
                 SET a.status = 'available'
                 WHERE aa.application_id = ?"
            );
            executeStatement($stmt, [$application_id], "s");

            $success_message = "Application withdrawn successfully.";
        }

        $conn->commit();

        // Refresh the applications list
        header("Location: application-status.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error withdrawing application: " . $e->getMessage());
        $error_message = "An error occurred while withdrawing your application.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .applications-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .application-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .pet-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .application-content {
            padding: 20px;
        }

        .pet-name {
            font-size: 1.5rem;
            margin: 0 0 10px 0;
            color: #333;
        }

        .pet-details {
            color: #666;
            margin-bottom: 15px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-withdrawn {
            background-color: #e9ecef;
            color: #495057;
        }

        .application-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .detail-label {
            color: #666;
        }

        .detail-value {
            color: #333;
            font-weight: 500;
        }

        .review-notes {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .review-notes h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .action-button {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.95rem;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            border: none;
            margin-top: 15px;
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

        .button-neutral {
            background-color: #6c757d;
            color: white;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .no-applications {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .withdraw-form {
            display: inline-block;
        }

        @media (max-width: 768px) {
            .applications-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="applications-container">
    <h1>My Adoption Applications</h1>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if (empty($applications)): ?>
        <div class="no-applications">
            <h2>No Applications Yet</h2>
            <p>You haven't submitted any adoption applications yet.</p>
            <a href="../public/animals.php" class="action-button button-primary">Browse Available Pets</a>
        </div>
    <?php else: ?>
        <div class="applications-grid">
            <?php foreach ($applications as $app): ?>
                <div class="application-card">
                    <?php if ($app['image_url']): ?>
                        <img src="../uploads/animals/<?php echo htmlspecialchars($app['image_url']); ?>"
                             alt="<?php echo htmlspecialchars($app['animal_name']); ?>"
                             class="pet-image">
                    <?php endif; ?>

                    <div class="application-content">
                        <h2 class="pet-name"><?php echo htmlspecialchars($app['animal_name']); ?></h2>

                        <div class="pet-details">
                            <?php echo htmlspecialchars($app['breed']); ?> •
                            <?php echo ucfirst(htmlspecialchars($app['species'])); ?>
                        </div>

                        <div class="status-badge status-<?php echo $app['status']; ?>">
                            <?php echo ucfirst(htmlspecialchars($app['status'])); ?>
                        </div>

                        <div class="application-details">
                            <div class="detail-row">
                                <span class="detail-label">Submitted</span>
                                <span class="detail-value">
                                        <?php echo date('M d, Y', strtotime($app['application_date'])); ?>
                                    </span>
                            </div>

                            <?php if ($app['reviewed_by']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Reviewed By</span>
                                    <span class="detail-value">
                                            <?php echo htmlspecialchars($app['reviewer_name']); ?>
                                        </span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Review Date</span>
                                    <span class="detail-value">
                                            <?php echo date('M d, Y', strtotime($app['review_date'])); ?>
                                        </span>
                                </div>
                            <?php endif; ?>

                            <?php if ($app['review_notes']): ?>
                                <div class="review-notes">
                                    <h4>Review Notes</h4>
                                    <p><?php echo nl2br(htmlspecialchars($app['review_notes'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($app['status'] === 'pending'): ?>
                                <form method="POST" class="withdraw-form"
                                      onsubmit="return confirm('Are you sure you want to withdraw this application?');">
                                    <input type="hidden" name="action" value="withdraw">
                                    <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                    <button type="submit" class="action-button button-danger">
                                        Withdraw Application
                                    </button>
                                </form>
                            <?php endif; ?>

                            <a href="../public/animals.php" class="action-button button-primary">
                                Browse More Pets
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>