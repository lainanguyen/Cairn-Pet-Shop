<?php
require_once '../includes/config.php';
require_once '../includes/authentication.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Initialize filters
$species = isset($_GET['species']) ? sanitizeInput($_GET['species']) : '';
$size = isset($_GET['size']) ? sanitizeInput($_GET['size']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

try {
    $conn = connectDB();

    // Build the query with filters
    $query = "SELECT a.*, ai.image_url, 
              CASE 
                  WHEN aa.status = 'pending' THEN 'has_pending_application'
                  ELSE NULL 
              END as application_status
              FROM animals a
              LEFT JOIN animal_images ai ON a.animal_id = ai.animal_id AND ai.is_primary = TRUE
              LEFT JOIN adoption_applications aa ON a.animal_id = aa.animal_id AND aa.status = 'pending'
              WHERE a.status != 'adopted'";

    $params = [];
    $types = "";

    if ($species) {
        $query .= " AND a.species = ?";
        $params[] = $species;
        $types .= "s";
    }

    if ($size) {
        $query .= " AND a.size = ?";
        $params[] = $size;
        $types .= "s";
    }

    if ($search) {
        $query .= " AND (a.name LIKE ? OR a.breed LIKE ? OR a.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "sss";
    }

    $query .= " ORDER BY a.is_featured DESC, a.created_at DESC";

    $stmt = prepareStatement($conn, $query);
    if (!empty($params)) {
        executeStatement($stmt, $params, $types);
    } else {
        executeStatement($stmt, [], "");
    }

    $animals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Error fetching animals: " . $e->getMessage());
    $error_message = "An error occurred while fetching the animals. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Pets - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .pets-container {
            max-width: 1366px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 500;
            color: #666;
        }

        .filter-input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Crimson Pro', serif;
            font-size: 1rem;
        }

        .search-button {
            padding: 12px 24px;
            background-color: #3693F0;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Crimson Pro', serif;
            font-size: 1rem;
            transition: background-color 0.2s;
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
            transition: transform 0.2s;
            position: relative;
        }

        .pet-card:hover {
            transform: translateY(-5px);
        }

        .pet-image-container {
            position: relative;
            padding-top: 75%; /* 4:3 Aspect Ratio */
            overflow: hidden;
        }

        .pet-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
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
            margin-bottom: 15px;
            color: #666;
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
        }

        .status-available {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .featured-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 8px 16px;
            border-radius: 20px;
            background-color: #3693F0;
            color: white;
            font-size: 0.9rem;
            font-weight: 500;
            z-index: 1;
        }

        .adoption-fee {
            font-weight: 500;
            color: #3693F0;
            font-size: 1.2rem;
            margin-top: 10px;
        }

        .action-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3693F0;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 15px;
            text-align: center;
            transition: background-color 0.2s;
        }

        .action-button:hover {
            background-color: #2a75d0;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
            grid-column: 1 / -1;
        }

        @media (max-width: 768px) {
            .pets-container {
                padding: 20px 10px;
            }

            .filters-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="pets-container">
    <!-- Search and Filters -->
    <section class="filters-section">
        <form class="filters-form" method="GET">
            <div class="filter-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search"
                       class="filter-input"
                       placeholder="Search by name, breed..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="filter-group">
                <label for="species">Species</label>
                <select id="species" name="species" class="filter-input">
                    <option value="">All Species</option>
                    <option value="dog" <?php echo $species === 'dog' ? 'selected' : ''; ?>>Dogs</option>
                    <option value="cat" <?php echo $species === 'cat' ? 'selected' : ''; ?>>Cats</option>
                    <option value="other" <?php echo $species === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="size">Size</label>
                <select id="size" name="size" class="filter-input">
                    <option value="">Any Size</option>
                    <option value="small" <?php echo $size === 'small' ? 'selected' : ''; ?>>Small</option>
                    <option value="medium" <?php echo $size === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="large" <?php echo $size === 'large' ? 'selected' : ''; ?>>Large</option>
                </select>
            </div>

            <div class="filter-group">
                <button type="submit" class="search-button">Search Pets</button>
            </div>
        </form>
    </section>

    <!-- Animals Grid -->
    <div class="pets-grid">
        <?php if (isset($error_message)): ?>
            <div class="no-results"><?php echo htmlspecialchars($error_message); ?></div>
        <?php elseif (empty($animals)): ?>
            <div class="no-results">No pets found matching your criteria. Try adjusting your filters.</div>
        <?php else: ?>
            <?php foreach ($animals as $animal): ?>
                <article class="pet-card">
                    <?php if ($animal['is_featured']): ?>
                        <div class="featured-badge">Featured</div>
                    <?php endif; ?>

                    <div class="status-badge <?php echo $animal['status'] === 'available' ? 'status-available' : 'status-pending'; ?>">
                        <?php echo ucfirst($animal['status']); ?>
                    </div>

                    <div class="pet-image-container">
                        <?php if ($animal['image_url']): ?>
                            <img src="../uploads/animals/<?php echo htmlspecialchars($animal['image_url']); ?>"
                                 alt="<?php echo htmlspecialchars($animal['name']); ?>"
                                 class="pet-image">
                        <?php else: ?>
                            <img src="../assets/images/placeholder-pet.jpg"
                                 alt="No image available"
                                 class="pet-image">
                        <?php endif; ?>
                    </div>

                    <div class="pet-info">
                        <h2 class="pet-name"><?php echo htmlspecialchars($animal['name']); ?></h2>

                        <div class="pet-details">
                            <div><?php echo htmlspecialchars($animal['breed']); ?></div>
                            <div><?php echo htmlspecialchars($animal['age_years']); ?> years •
                                <?php echo htmlspecialchars($animal['gender']); ?> •
                                <?php echo htmlspecialchars($animal['size']); ?></div>
                        </div>

                        <?php if ($animal['adoption_fee'] > 0): ?>
                            <div class="adoption-fee">
                                Adoption Fee: $<?php echo number_format($animal['adoption_fee'], 2); ?>
                            </div>
                        <?php endif; ?>

                        <a href="animal-details.php?id=<?php echo $animal['animal_id']; ?>"
                           class="action-button">
                            View Details
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>