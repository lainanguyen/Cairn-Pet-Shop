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

        // Start transaction
        $conn->begin_transaction();

        // Update application status
        $stmt = prepareStatement($conn,
            "UPDATE adoption_applications 
             SET status = ?, 
                 review_notes = ?, 
                 reviewed_by = ?, 
                 review_date = CURRENT_TIMESTAMP 
             WHERE application_id = ?"
        );
        executeStatement($stmt, [$newStatus, $reviewNotes, $_SESSION['user_id'], $applicationId], "ssss");

        // Update animal status based on application status
        if ($newStatus === 'approved') {
            $stmt = prepareStatement($conn,
                "UPDATE animals a 
                 JOIN adoption_applications aa ON a.animal_id = aa.animal_id 
                 SET a.status = 'pending' 
                 WHERE aa.application_id = ?"
            );
            executeStatement($stmt, [$applicationId], "s");
        } elseif ($newStatus === 'rejected') {
            // If rejected, check if there are other pending applications
            $stmt = prepareStatement($conn,
                "UPDATE animals a 
                 JOIN adoption_applications aa ON a.animal_id = aa.animal_id 
                 SET a.status = 'available' 
                 WHERE aa.application_id = ? 
                 AND NOT EXISTS (
                     SELECT 1 FROM adoption_applications aa2 
                     WHERE aa2.animal_id = a.animal_id 
                     AND aa2.application_id != ? 
                     AND aa2.status = 'pending'
                 )"
            );
            executeStatement($stmt, [$applicationId, $applicationId], "ss");
        }

        $conn->commit();
        $success_message = "Application status updated successfully!";

        // Prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=true");
        exit();
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
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
                a.breed as animal_breed,
                ai.image_url,
                u.first_name, 
                u.last_name, 
                u.email,
                u.phone,
                rev.first_name as reviewer_first_name,
                rev.last_name as reviewer_last_name
         FROM adoption_applications aa 
         JOIN animals a ON aa.animal_id = a.animal_id 
         LEFT JOIN animal_images ai ON a.animal_id = ai.animal_id AND ai.is_primary = TRUE
         JOIN users u ON aa.user_id = u.user_id 
         LEFT JOIN users rev ON aa.reviewed_by = rev.user_id
         ORDER BY 
            CASE aa.status
                WHEN 'pending' THEN 1
                WHEN 'approved' THEN 2
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .applications-container {
            max-width: 1366px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 2rem;
            color: #333;
        }

        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .application-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .application-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            position: relative;
        }

        .pet-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }

        .card-content {
            padding: 20px;
        }

        .applicant-name {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 5px;
        }

        .pet-name {
            color: #3693F0;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            font-size: 0.9rem;
        }

        .info-label {
            color: #666;
            margin-bottom: 3px;
        }

        .info-value {
            color: #333;
            font-weight: 500;
        }

        .review-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #3693F0;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            text-align: center;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #3693F0;
            color: white;
        }

        .btn-approve {
            background-color: #28a745;
            color: white;
        }

        .btn-reject {
            background-color: #dc3545;
            color: white;
        }

        .review-notes {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .review-notes h4 {
            color: #333;
            margin-bottom: 10px;
        }

        .review-info {
            color: #666;
            font-style: italic;
            margin-top: 5px;
            font-size: 0.9rem;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }

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
    <div class="page-header">
        <h2 class="page-title">Manage Applications</h2>
    </div>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="applications-grid">
        <?php foreach ($applications as $app): ?>
            <div class="application-card">
                <div class="card-header">
                    <?php if ($app['image_url']): ?>
                        <img src="../uploads/animals/<?php echo htmlspecialchars($app['image_url']); ?>"
                             alt="<?php echo htmlspecialchars($app['animal_name']); ?>"
                             class="pet-image">
                    <?php endif; ?>

                    <div class="status-badge status-<?php echo strtolower($app['status']); ?>">
                        <?php echo ucfirst($app['status']); ?>
                    </div>
                </div>

                <div class="card-content">
                    <h3 class="applicant-name">
                        <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                    </h3>
                    <div class="pet-name">
                        For: <?php echo htmlspecialchars($app['animal_name']); ?>
                        (<?php echo htmlspecialchars($app['animal_breed']); ?>)
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($app['email']); ?></div>
                        </div>

                        <?php if ($app['phone']): ?>
                            <div class="info-item">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?php echo htmlspecialchars($app['phone']); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="info-item">
                            <div class="info-label">Applied On</div>
                            <div class="info-value">
                                <?php echo date('M d, Y', strtotime($app['application_date'])); ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Home Type</div>
                            <div class="info-value"><?php echo htmlspecialchars($app['home_type']); ?></div>
                        </div>
                    </div>

                    <?php if ($app['other_pets']): ?>
                        <div class="info-item">
                            <div class="info-label">Other Pets</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($app['other_pets'])); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($app['reviewed_by']): ?>
                        <div class="review-notes">
                            <h4>Review Notes</h4>
                            <div><?php echo nl2br(htmlspecialchars($app['review_notes'])); ?></div>
                            <div class="review-info">
                                Reviewed by <?php echo htmlspecialchars($app['reviewer_first_name'] . ' ' . $app['reviewer_last_name']); ?>
                                on <?php echo date('F j, Y', strtotime($app['review_date'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($app['status'] === 'pending'): ?>
                        <form method="POST" class="review-form">
                            <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">

                            <div class="form-group">
                                <label for="status-<?php echo $app['application_id']; ?>">Update Status</label>
                                <select name="status" id="status-<?php echo $app['application_id']; ?>"
                                        class="form-control" required>
                                    <option value="pending">Keep Pending</option>
                                    <option value="approved">Approve</option>
                                    <option value="rejected">Reject</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="notes-<?php echo $app['application_id']; ?>">Review Notes</label>
                                <textarea name="review_notes"
                                          id="notes-<?php echo $app['application_id']; ?>"
                                          class="form-control"
                                          rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Application</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>