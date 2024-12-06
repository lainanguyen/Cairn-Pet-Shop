<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure only employees can access this page
requireEmployee();

$success_message = '';
$error_message = '';
$animal = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = connectDB();

        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                // Generate new ID for new animals
                $animalId = isset($_POST['animal_id']) ? $_POST['animal_id'] : generateUUID();

                // Handle image upload if present
                $imageFileName = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = ROOT_DIR . '/uploads/animals';
                    $imageFileName = handleFileUpload($_FILES['image'], $uploadDir);
                }

                // Prepare animal data
                $animalData = [
                    'name' => sanitizeInput($_POST['name']),
                    'species' => sanitizeInput($_POST['species']),
                    'breed' => sanitizeInput($_POST['breed']),
                    'age_years' => floatval($_POST['age_years']),
                    'gender' => sanitizeInput($_POST['gender']),
                    'size' => sanitizeInput($_POST['size']),
                    'color' => sanitizeInput($_POST['color']),
                    'description' => sanitizeInput($_POST['description']),
                    'health_notes' => sanitizeInput($_POST['health_notes']),
                    'behavior_notes' => sanitizeInput($_POST['behavior_notes']),
                    'adoption_fee' => floatval($_POST['adoption_fee']),
                    'status' => sanitizeInput($_POST['status'])
                ];

                if ($_POST['action'] === 'add') {
                    // Insert new animal
                    $stmt = prepareStatement($conn,
                        "INSERT INTO animals (
                            animal_id, name, species, breed, age_years, gender, 
                            size, color, description, health_notes, behavior_notes,
                            adoption_fee, status, intake_date, created_by
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_DATE, ?
                        )"
                    );

                    executeStatement($stmt, [
                        $animalId,
                        $animalData['name'],
                        $animalData['species'],
                        $animalData['breed'],
                        $animalData['age_years'],
                        $animalData['gender'],
                        $animalData['size'],
                        $animalData['color'],
                        $animalData['description'],
                        $animalData['health_notes'],
                        $animalData['behavior_notes'],
                        $animalData['adoption_fee'],
                        $animalData['status'],
                        $_SESSION['user_id']
                    ], "sssssssssssdss");

                    $success_message = "Animal added successfully!";
                } else {
                    // Update existing animal
                    $stmt = prepareStatement($conn,
                        "UPDATE animals SET 
                            name = ?, species = ?, breed = ?, age_years = ?,
                            gender = ?, size = ?, color = ?, description = ?,
                            health_notes = ?, behavior_notes = ?, adoption_fee = ?,
                            status = ?
                        WHERE animal_id = ?"
                    );

                    executeStatement($stmt, [
                        $animalData['name'],
                        $animalData['species'],
                        $animalData['breed'],
                        $animalData['age_years'],
                        $animalData['gender'],
                        $animalData['size'],
                        $animalData['color'],
                        $animalData['description'],
                        $animalData['health_notes'],
                        $animalData['behavior_notes'],
                        $animalData['adoption_fee'],
                        $animalData['status'],
                        $animalId
                    ], "sssdssssssds");

                    $success_message = "Animal updated successfully!";
                }

                // Handle image if uploaded
                if ($imageFileName) {
                    $stmt = prepareStatement($conn,
                        "INSERT INTO animal_images (image_id, animal_id, image_url, is_primary)
                         VALUES (?, ?, ?, TRUE)
                         ON DUPLICATE KEY UPDATE image_url = ?"
                    );
                    executeStatement($stmt, [
                        generateUUID(),
                        $animalId,
                        $imageFileName,
                        $imageFileName
                    ], "ssss");
                }
                break;

            case 'delete':
                // Check if there are any pending applications
                $stmt = prepareStatement($conn,
                    "SELECT COUNT(*) as count 
                     FROM adoption_applications 
                     WHERE animal_id = ? AND status = 'pending'"
                );
                executeStatement($stmt, [$_POST['animal_id']], "s");
                $result = $stmt->get_result()->fetch_assoc();

                if ($result['count'] > 0) {
                    throw new Exception("Cannot delete animal with pending applications");
                }

                // Delete the animal
                $stmt = prepareStatement($conn,
                    "DELETE FROM animals WHERE animal_id = ?"
                );
                executeStatement($stmt, [$_POST['animal_id']], "s");
                $success_message = "Animal deleted successfully!";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle edit request
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    try {
        $conn = connectDB();
        $stmt = prepareStatement($conn,
            "SELECT a.*, ai.image_url 
             FROM animals a 
             LEFT JOIN animal_images ai ON a.animal_id = ai.animal_id AND ai.is_primary = TRUE
             WHERE a.animal_id = ?"
        );
        executeStatement($stmt, [$_GET['id']], "s");
        $animal = $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        $error_message = "Error fetching animal details: " . $e->getMessage();
    }
}

// Fetch all animals for listing
try {
    $conn = connectDB();
    $stmt = prepareStatement($conn,
        "SELECT a.*, ai.image_url,
            CASE 
                WHEN aa.status = 'pending' THEN 'Has pending applications'
                ELSE NULL
            END as application_status
         FROM animals a
         LEFT JOIN animal_images ai ON a.animal_id = ai.animal_id AND ai.is_primary = TRUE
         LEFT JOIN adoption_applications aa ON a.animal_id = aa.animal_id AND aa.status = 'pending'
         ORDER BY a.created_at DESC"
    );
    executeStatement($stmt, [], "");
    $animals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching animals: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Animals - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .manage-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .animal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .animal-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .animal-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .animal-info {
            padding: 20px;
        }

        .animal-form {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-group textarea {
            height: 100px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .button {
            padding: 10px 20px;
            border: none;
            border-radius: 300px;
            cursor: pointer;
            font-family: "Crimson Pro", serif;
        }

        .button-primary {
            background-color: #3693F0;
            color: white;
        }

        .button-danger {
            background-color: #dc3545;
            color: white;
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

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            margin-top: 10px;
        }

        .status-available { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-adopted { background-color: #cce5ff; color: #004085; }
        .status-unavailable { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="manage-container">
    <h2><?php echo isset($_GET['action']) && $_GET['action'] === 'edit' ? 'Edit Animal' : 'Add New Animal'; ?></h2>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Add/Edit Animal Form -->
    <form method="POST" enctype="multipart/form-data" class="animal-form">
        <input type="hidden" name="action" value="<?php echo $animal ? 'edit' : 'add'; ?>">
        <?php if ($animal): ?>
            <input type="hidden" name="animal_id" value="<?php echo $animal['animal_id']; ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required
                       value="<?php echo $animal ? htmlspecialchars($animal['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="species">Species:</label>
                <select id="species" name="species" required>
                    <option value="dog" <?php echo $animal && $animal['species'] === 'dog' ? 'selected' : ''; ?>>Dog</option>
                    <option value="cat" <?php echo $animal && $animal['species'] === 'cat' ? 'selected' : ''; ?>>Cat</option>
                    <option value="other" <?php echo $animal && $animal['species'] === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="breed">Breed:</label>
                <input type="text" id="breed" name="breed"
                       value="<?php echo $animal ? htmlspecialchars($animal['breed']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="age_years">Age (years):</label>
                <input type="number" id="age_years" name="age_years" step="0.1" required
                       value="<?php echo $animal ? htmlspecialchars($animal['age_years']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="gender">Gender:</label>
                <select id="gender" name="gender" required>
                    <option value="M" <?php echo $animal && $animal['gender'] === 'M' ? 'selected' : ''; ?>>Male</option>
                    <option value="F" <?php echo $animal && $animal['gender'] === 'F' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>

            <div class="form-group">
                <label for="size">Size:</label>
                <select id="size" name="size" required>
                    <option value="small" <?php echo $animal && $animal['size'] === 'small' ? 'selected' : ''; ?>>Small</option>
                    <option value="medium" <?php echo $animal && $animal['size'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="large" <?php echo $animal && $animal['size'] === 'large' ? 'selected' : ''; ?>>Large</option>
                </select>
            </div>

            <div class="form-group">
                <label for="color">Color:</label>
                <input type="text" id="color" name="color"
                       value="<?php echo $animal ? htmlspecialchars($animal['color']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="adoption_fee">Adoption Fee:</label>
                <input type="number" id="adoption_fee" name="adoption_fee" step="0.01" required
                       value="<?php echo $animal ? htmlspecialchars($animal['adoption_fee']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="available" <?php echo $animal && $animal['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="pending" <?php echo $animal && $animal['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="adopted" <?php echo $animal && $animal['status'] === 'adopted' ? 'selected' : ''; ?>>Adopted</option>
                    <option value="unavailable" <?php echo $animal && $animal['status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                </select>
            </div>

            <div class="form-group">
                <label for="image">Profile Image:</label>
                <input type="file" id="image" name="image" accept="image/*" <?php echo !$animal ? 'required' : ''; ?>>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" required><?php echo $animal ? htmlspecialchars($animal['description']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label for="health_notes">Health Notes:</label>
            <textarea id="health_notes" name="health_notes"><?php echo $animal ? htmlspecialchars($animal['health_notes']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label for="behavior_notes">Behavior Notes:</label>
            <textarea id="behavior_notes" name="behavior_notes"><?php echo $animal ? htmlspecialchars($animal['behavior_notes']) : ''; ?></textarea>
        </div>

        <div class="action-buttons">
            <button type="submit" class="button button-primary">
                <?php echo $animal ? 'Update Animal' : 'Add Animal'; ?>
            </button>
            <?php if (!$animal): ?>
                <button type="reset" class="button">Clear Form</button>
            <?php endif; ?>
        </div>
    </form>

    <!-- Animals List -->
    <h2>Current Animals</h2>
    <div class="animal-grid">
        <?php foreach ($animals as $animal): ?>
            <div class="animal-card">
                <?php if ($animal['image_url']): ?>
                    <img src="../uploads/animals/<?php echo htmlspecialchars($animal['image_url']); ?>"
                         alt="<?php echo htmlspecialchars($animal['name']); ?>"
                         class="animal-image">
                <?php endif; ?>

                <div class="animal-info">
                    <h3><?php echo htmlspecialchars($animal['name']); ?></h3>
                    <p><?php echo htmlspecialchars($animal['breed']); ?> •
                        <?php echo htmlspecialchars($animal['age_years']); ?> years</p>

                    <span class="status-badge status-<?php echo $animal['status']; ?>">
                            <?php echo ucfirst(htmlspecialchars($animal['status'])); ?>
                        </span>

                    <?php if ($animal['application_status']): ?>
                        <p class="status-badge status-pending">
                            <?php echo htmlspecialchars($animal['application_status']); ?>
                        </p>
                    <?php endif; ?>

                    <div class="action-buttons">
                        <a href="?action=edit&id=<?php echo $animal['animal_id']; ?>"
                           class="button button-primary">Edit</a>

                        <?php if (!$animal['application_status']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="animal_id" value="<?php echo $animal['animal_id']; ?>">
                                <button type="submit" class="button button-danger"
                                        onclick="return confirm('Are you sure you want to delete this animal?')">
                                    Delete
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>