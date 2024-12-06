<?php
require_once '../includes/config.php';
require_once '../includes/authentication.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$animal_id = sanitizeInput($_GET['id'] ?? '');
$success_message = '';
$error_message = '';

if (!$animal_id) {
    header("Location: animals.php");
    exit();
}

try {
    $conn = connectDB();

    // Fetch animal details with any pending applications
    $stmt = prepareStatement($conn,
        "SELECT a.*, ai.image_url,
            CASE 
                WHEN aa.status = 'pending' AND aa.user_id = ? THEN 'user_has_pending'
                WHEN aa.status = 'pending' THEN 'other_has_pending'
                ELSE NULL 
            END as application_status
         FROM animals a
         LEFT JOIN animal_images ai ON a.animal_id = ai.animal_id AND ai.is_primary = TRUE
         LEFT JOIN adoption_applications aa ON a.animal_id = aa.animal_id AND aa.status = 'pending'
         WHERE a.animal_id = ?"
    );

    executeStatement($stmt, [
        $_SESSION['user_id'] ?? null,
        $animal_id
    ], "ss");

    $animal = $stmt->get_result()->fetch_assoc();

    if (!$animal) {
        throw new Exception("Pet not found");
    }

    // Fetch additional images if any
    $stmt = prepareStatement($conn,
        "SELECT image_url FROM animal_images 
         WHERE animal_id = ? AND is_primary = FALSE
         ORDER BY upload_date DESC"
    );
    executeStatement($stmt, [$animal_id], "s");
    $additional_images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Error fetching animal details: " . $e->getMessage());
    $error_message = "An error occurred while fetching the pet details.";
}

// Handle application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply') {
    try {
        if (!isLoggedIn()) {
            throw new Exception("Please log in to submit an adoption application.");
        }

        // Verify animal is still available
        if ($animal['status'] !== 'available') {
            throw new Exception("Sorry, this pet is no longer available for adoption.");
        }

        // Create adoption application
        $applicationId = generateUUID();
        $stmt = prepareStatement($conn,
            "INSERT INTO adoption_applications (
                application_id, animal_id, user_id, status,
                home_type, has_yard, other_pets, household_members,
                previous_pet_experience, employment_status,
                application_date
            ) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)"
        );

        executeStatement($stmt, [
            $applicationId,
            $animal_id,
            $_SESSION['user_id'],
            sanitizeInput($_POST['home_type']),
            isset($_POST['has_yard']) ? 1 : 0,
            sanitizeInput($_POST['other_pets']),
            intval($_POST['household_members']),
            sanitizeInput($_POST['previous_pet_experience']),
            sanitizeInput($_POST['employment_status'])
        ], "sssssisis");

        // Update animal status to pending
        $stmt = prepareStatement($conn,
            "UPDATE animals SET status = 'pending' WHERE animal_id = ?"
        );
        executeStatement($stmt, [$animal_id], "s");

        $success_message = "Your adoption application has been submitted successfully!";

        // Redirect to prevent form resubmission
        header("Location: application-status.php?id=" . $applicationId);
        exit();

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($animal['name']); ?> - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .pet-details-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .pet-header {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: start;
            gap: 30px;
            margin-bottom: 30px;
        }

        .pet-title h1 {
            font-size: 2.5rem;
            margin: 0;
            color: #333;
        }

        .status-badge {
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 500;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 30px;
        }

        .images-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
        }

        .thumbnail {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .thumbnail:hover {
            opacity: 0.8;
        }

        .details-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .detail-group {
            margin-bottom: 20px;
        }

        .detail-group h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #333;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 1.1rem;
            color: #333;
        }

        .adoption-fee {
            font-size: 1.5rem;
            color: #3693F0;
            margin: 20px 0;
        }

        .action-button {
            display: inline-block;
            width: 100%;
            padding: 15px 30px;
            background-color: #3693F0;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .action-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .application-form {
            margin-top: 20px;
            display: none;
        }

        .application-form.show {
            display: block;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
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

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .pet-header {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="pet-details-container">
    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <div class="pet-header">
        <div class="pet-title">
            <h1><?php echo htmlspecialchars($animal['name']); ?></h1>
            <p><?php echo htmlspecialchars($animal['breed']); ?></p>
        </div>

        <div class="status-badge status-<?php echo $animal['status']; ?>">
            <?php echo ucfirst($animal['status']); ?>
        </div>
    </div>

    <div class="content-grid">
        <div class="images-section">
            <img src="../uploads/animals/<?php echo htmlspecialchars($animal['image_url']); ?>"
                 alt="<?php echo htmlspecialchars($animal['name']); ?>"
                 class="main-image"
                 id="main-image">

            <?php if (!empty($additional_images)): ?>
                <div class="thumbnail-grid">
                    <img src="../uploads/animals/<?php echo htmlspecialchars($animal['image_url']); ?>"
                         alt="Primary image"
                         class="thumbnail"
                         onclick="updateMainImage(this.src)">
                    <?php foreach ($additional_images as $image): ?>
                        <img src="../uploads/animals/<?php echo htmlspecialchars($image['image_url']); ?>"
                             alt="Additional image"
                             class="thumbnail"
                             onclick="updateMainImage(this.src)">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="details-section">
            <div class="detail-group">
                <h2>Details</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Age</span>
                        <span class="detail-value"><?php echo htmlspecialchars($animal['age_years']); ?> years</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Gender</span>
                        <span class="detail-value"><?php echo $animal['gender'] === 'M' ? 'Male' : 'Female'; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Size</span>
                        <span class="detail-value"><?php echo ucfirst(htmlspecialchars($animal['size'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Color</span>
                        <span class="detail-value"><?php echo htmlspecialchars($animal['color']); ?></span>
                    </div>
                </div>
            </div>

            <div class="detail-group">
                <h2>About</h2>
                <p><?php echo nl2br(htmlspecialchars($animal['description'])); ?></p>
            </div>

            <?php if ($animal['health_notes']): ?>
                <div class="detail-group">
                    <h2>Health</h2>
                    <p><?php echo nl2br(htmlspecialchars($animal['health_notes'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($animal['behavior_notes']): ?>
                <div class="detail-group">
                    <h2>Behavior</h2>
                    <p><?php echo nl2br(htmlspecialchars($animal['behavior_notes'])); ?></p>
                </div>
            <?php endif; ?>

            <div class="adoption-fee">
                Adoption Fee: $<?php echo number_format($animal['adoption_fee'], 2); ?>
            </div>

            <?php if ($animal['status'] === 'available'): ?>
            <?php if (isLoggedIn()): ?>
            <button onclick="toggleApplicationForm()" class="action-button">
                Start Adoption Process
            </button>

            <form method="POST" class="application-form" id="applicationForm">
                <input type="hidden" name="action" value="apply">

                <div class="form-group">
                    <label for="home_type">Type of Home</label>
                    <select name="home_type" id="home_type" required>
                        <option value="">Select...</option>
                        <option value="house">House</option>
                        <option value="apartment">Apartment</option>
                        <option value="condo">Condo</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="has_yard">
                        Do you have a yard?
                    </label>
                </div>

                <div class="form-group">
                    <label for="other_pets">Other Pets in Household</label>
                    <textarea name="other_pets" id="other_pets" rows="3"
                              placeholder="Please list any current pets..."></textarea>
                </div>

                <div class="form-group">
                    <label for="household_members">Number of People in Household</label>
                    <input type="number" name="household_members" id="household_members"
                           min="1" required>
                </div>

                <div class="form-group">
                    <label for="previous_pet_experience">Previous Pet Experience</label>
                    <textarea name="previous_pet_experience" id="previous_pet_experience"
                              rows="3" required
                              placeholder="Describe your experience with pets..."></textarea>
                </div>

                <div class="form-group">
                    <label for="employment_status">Employment Status</label>
                    <select name="employment_status" id="employment_status" required>
                        <option value="">Select...</option>
                        <option value="full_time">Full-time</option>
                        <option value="part_time">Part-time</option>
                        <option value="self_employed">Self-employed</option>
                        <option value="retired">Retired</option>
                        <option value="student">Student</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <button type="submit" class="action-button">Submit Application</button>
            </form>
                <?php else: ?>
                    <a href="../public/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"
                       class="action-button">
                        Login to Start Adoption Process
                    </a>
                <?php endif; ?>
            <?php elseif ($animal['application_status'] === 'user_has_pending'): ?>
                <a href="../public/application-status.php" class="action-button" style="background-color: #ffc107;">
                    View Your Application Status
                </a>
            <?php else: ?>
                <button class="action-button" disabled>
                    Currently Not Available
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    function updateMainImage(src) {
        document.getElementById('main-image').src = src;
    }

    function toggleApplicationForm() {
        const form = document.getElementById('applicationForm');
        form.classList.toggle('show');
        form.scrollIntoView({ behavior: 'smooth' });
    }
</script>
</body>
</html>