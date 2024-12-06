<?php
require_once '../includes/config.php';
require_once '../includes/authentication.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure only employees can access this page
requireEmployee();

$success_message = '';
$error_message = '';

// Handle application status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $applicationId = sanitizeInput($_POST['application_id']);
        $newStatus = sanitizeInput($_POST['status']);
        $reviewNotes = sanitizeInput($_POST['review_notes'] ?? '');

        if (!in_array($newStatus, ['approved', 'rejected', 'pending'])) {
            throw new Exception("Invalid status specified");
        }

        $conn = connectDB();
        $stmt = prepareStatement($conn,
            "UPDATE adoption_applications 
             SET status = ?, 
                 review_notes = ?, 
                 reviewed_by = ?, 
                 review_date = CURRENT_TIMESTAMP 
             WHERE application_id = ?"
        );
        executeStatement($stmt, [$newStatus, $reviewNotes, $_SESSION['user_id'], $applicationId], "ssss");

        // If approved, update animal status to 'pending'
        if ($newStatus === 'approved') {
            $stmt = prepareStatement($conn,
                "UPDATE animals a 
                 JOIN adoption_applications aa ON a.animal_id = aa.animal_id 
                 SET a.status = 'pending' 
                 WHERE aa.application_id = ?"
            );
            executeStatement($stmt, [$applicationId], "s");
        }

        $success_message = "Application status updated successfully!";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch applications with related information
try {
    $conn = connectDB();
    $stmt = prepareStatement($conn,
        "SELECT aa.*, 
                a.name as animal_name, 
                a.species as animal_species,
                u.first_name, 
                u.last_name, 
                u.email,
                rev.first_name as reviewer_first_name,
                rev.last_name as reviewer_last_name
         FROM adoption_applications aa 
         JOIN animals a ON aa.animal_id = a.animal_id 
         JOIN users u ON aa.user_id = u.user_id 
         LEFT JOIN users rev ON aa.reviewed_by = rev.user_id
         ORDER BY 
            CASE 
                WHEN aa.status = 'pending' THEN 1
                WHEN aa.status = 'approved' THEN 2
                ELSE 3
            END,
            aa.application_date DESC"
    );
    executeStatement($stmt, [], "");
    $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching applications: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .applications-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .application-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .application-title {
            font-size: 1.2em;
            margin: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
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

        .application-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-group {
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: bold;
            color: #666;
        }

        .action-form {
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .submit-button {
            padding: 10px 20px;
            background-color: #3693F0;
            color: white;
            border: none;
            border-radius: 300px;
            cursor: pointer;
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

<div class="applications-container">
    <h2>Adoption Applications</h2>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php foreach ($applications as $app): ?>
        <div class="application-card">
            <div class="application-header">
                <h3 class="application-title">
                    Application for <?php echo htmlspecialchars($app['animal_name']); ?>
                    (<?php echo htmlspecialchars($app['animal_species']); ?>)
                </h3>
                <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                        <?php echo ucfirst(htmlspecialchars($app['status'])); ?>
                    </span>
            </div>

            <div class="application-details">
                <div>
                    <div class="detail-group">
                        <div class="detail-label">Applicant</div>
                        <div><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Email</div>
                        <div><?php echo htmlspecialchars($app['email']); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Home Type</div>
                        <div><?php echo htmlspecialchars($app['home_type']); ?></div>
                    </div>
                </div>
                <div>
                    <div class="detail-group">
                        <div class="detail-label">Application Date</div>
                        <div><?php echo date('F j, Y', strtotime($app['application_date'])); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Other Pets</div>
                        <div><?php echo htmlspecialchars($app['other_pets'] ?: 'None'); ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Household Members</div>
                        <div><?php echo htmlspecialchars($app['household_members']); ?></div>
                    </div>
                </div>
            </div>

            <?php if ($app['reviewed_by']): ?>
                <div class="detail-group">
                    <div class="detail-label">Reviewed By</div>
                    <div>
                        <?php echo htmlspecialchars($app['reviewer_first_name'] . ' ' . $app['reviewer_last_name']); ?>
                        on <?php echo date('F j, Y', strtotime($app['review_date'])); ?>
                    </div>
                </div>
                <?php if ($app['review_notes']): ?>
                    <div class="detail-group">
                        <div class="detail-label">Review Notes</div>
                        <div><?php echo nl2br(htmlspecialchars($app['review_notes'])); ?></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($app['status'] === 'pending'): ?>
                <form method="POST" class="action-form">
                    <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status-<?php echo $app['application_id']; ?>">Update Status</label>
                            <select name="status" id="status-<?php echo $app['application_id']; ?>" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="notes-<?php echo $app['application_id']; ?>">Review Notes</label>
                            <textarea
                                name="review_notes"
                                id="notes-<?php echo $app['application_id']; ?>"
                                rows="2"
                            ></textarea>
                        </div>
                    </div>

                    <button type="submit" class="submit-button">Update Application</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>