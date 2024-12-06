<?php
require_once '../includes/config.php';
require_once '../includes/authentication.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure user is logged in
requireLogin();

$success_message = '';
$error_message = '';

// Handle remove from saved
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    try {
        $animalId = sanitizeInput($_POST['animal_id']);

        // Use the new unsavePet helper function
        if (unsavePet($_SESSION['user_id'], $animalId)) {
            $success_message = "Pet removed from saved list.";
        } else {
            throw new Exception("Unable to remove pet from saved list.");
        }
    } catch (Exception $e) {
        $error_message = "Error removing pet from saved list: " . $e->getMessage();
    }
}

// Fetch saved pets with detailed information
try {
    $conn = connectDB();
    $stmt = prepareStatement($conn,
        "SELECT a.*, ai.image_url, sp.saved_date,
                CASE 
                    WHEN aa.status = 'pending' THEN 'has_pending_application'
                    ELSE NULL 
                END as application_status,
                (SELECT COUNT(*) FROM adoption_applications 
                 WHERE animal_id = a.animal_id AND status = 'pending') as pending_applications_count
         FROM saved_pets sp
         JOIN animals a ON sp.animal_id = a.animal_id
         LEFT JOIN animal_images ai ON a.animal_id = ai.animal_id AND ai.is_primary = TRUE
         LEFT JOIN adoption_applications aa ON a.animal_id = aa.animal_id 
             AND aa.user_id = sp.user_id 
             AND aa.status = 'pending'
         WHERE sp.user_id = ?
         ORDER BY sp.saved_date DESC"
    );
    executeStatement($stmt, [$_SESSION['user_id']], "s");
    $saved_pets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get total saved pets count for user
    $saved_count = getSavedPetsCount($_SESSION['user_id']);

} catch (Exception $e) {
    error_log("Error fetching saved pets: " . $e->getMessage());
    $error_message = "Error fetching saved pets: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Pets (<?php echo $saved_count; ?>) - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .saved-pets-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .count-badge {
            background-color: #3693F0;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
        }

        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .pet-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: transform 0.2s;
        }

        .pet-card:hover {
            transform: translateY(-5px);
        }

        .pet-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .pet-info {
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
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            z-index: 1;
            backdrop-filter: blur(4px);
        }

        .status-available {
            background-color: rgba(40, 167, 69, 0.9);
            color: white;
        }

        .status-pending {
            background-color: rgba(255, 193, 7, 0.9);
            color: #000;
        }

        .status-adopted {
            background-color: rgba(0, 123, 255, 0.9);
            color: white;
        }

        .adoption-fee {
            font-weight: 500;
            color: #3693F0;
            font-size: 1.2rem;
            margin: 10px 0;
        }

        .saved-date {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            font-size: 1rem;
            transition: background-color 0.2s;
            font-family: inherit;
        }

        .button-primary {
            background-color: #3693F0;
            color: white;
        }

        .button-primary:hover {
            background-color: #2a75d0;
        }

        .button-danger {
            background-color: #dc3545;
            color: white;
        }

        .button-danger:hover {
            background-color: #c82333;
        }

        .button-outline {
            border: 2px solid #3693F0;
            color: #3693F0;
            background: none;
        }

        .button-outline:hover {
            background-color: #f8f9fa;
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

        .no-saved-pets {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .no-saved-pets h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .no-saved-pets p {
            color: #666;
            margin-bottom: 25px;
        }

        .pending-notice {
            background-color: #fff3cd;
            color: #856404;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .saved-pets-container {
                padding: 10px;
            }

            .header-section {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .action-buttons {
                flex-direction: column;
            }

            .pets-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="saved-pets-container">
    <div class="header-section">
        <div class="header-title">
            <h2>My Saved Pets</h2>
            <span class="count-badge"><?php echo $saved_count; ?></span>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if (empty($saved_pets)): ?>
        <div class="no-saved-pets">
            <h3>No Saved Pets</h3>
            <p>You haven't saved any pets yet. Browse our available pets and save your favorites!</p>
            <a href="../public/animals.php" class="button button-primary">Browse Available Pets</a>
        </div>
    <?php else: ?>
        <div class="pets-grid">
            <?php foreach ($saved_pets as $pet): ?>
                <div class="pet-card">
                    <span class="status-badge status-<?php echo $pet['status']; ?>">
                        <?php echo ucfirst($pet['status']); ?>
                        <?php if ($pet['pending_applications_count'] > 0): ?>
                            (<?php echo $pet['pending_applications_count']; ?> pending)
                        <?php endif; ?>
                    </span>

                    <?php if ($pet['image_url']): ?>
                        <img src="../uploads/animals/<?php echo htmlspecialchars($pet['image_url']); ?>"
                             alt="<?php echo htmlspecialchars($pet['name']); ?>"
                             class="pet-image">
                    <?php endif; ?>

                    <div class="pet-info">
                        <h3 class="pet-name"><?php echo htmlspecialchars($pet['name']); ?></h3>

                        <div class="pet-details">
                            <?php echo htmlspecialchars($pet['breed']); ?> •
                            <?php echo htmlspecialchars($pet['age_years']); ?> years •
                            <?php echo $pet['gender'] === 'M' ? 'Male' : 'Female'; ?>
                        </div>

                        <div class="saved-date">
                            Saved on <?php echo date('F j, Y', strtotime($pet['saved_date'])); ?>
                        </div>

                        <div class="adoption-fee">
                            Adoption Fee: $<?php echo number_format($pet['adoption_fee'], 2); ?>
                        </div>

                        <?php if ($pet['application_status'] === 'has_pending_application'): ?>
                            <div class="pending-notice">
                                You have a pending application for this pet
                            </div>
                        <?php endif; ?>

                        <div class="action-buttons">
                            <a href="animal-details.php?id=<?php echo $pet['animal_id']; ?>"
                               class="button button-primary">
                                View Details
                            </a>

                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="animal_id" value="<?php echo $pet['animal_id']; ?>">
                                <button type="submit" class="button button-danger"
                                        onclick="return confirm('Are you sure you want to remove this pet from your saved list?')">
                                    Remove
                                </button>
                            </form>
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