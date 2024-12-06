<?php
require_once '../includes/config.php';
require_once '../includes/authentication.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Initialize filters
$species = sanitizeInput($_GET['species'] ?? '');
$size = sanitizeInput($_GET['size'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');
$sort = sanitizeInput($_GET['sort'] ?? 'newest');

try {
    $conn = connectDB();
    $query = "SELECT a.*, ai.image_url,
              CASE 
                  WHEN aa.status = 'pending' THEN 'has_pending_application'
                  ELSE NULL 
              END as application_status
              FROM animals a
              LEFT JOIN animal_images ai ON a.animal_id = ai.animal_id AND ai.is_primary = TRUE
              LEFT JOIN adoption_applications aa ON a.animal_id = aa.animal_id AND aa.status = 'pending'
              WHERE 1=1";

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
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }

    $query .= " ORDER BY ";
    switch ($sort) {
        case 'name_asc':
            $query .= "a.name ASC";
            break;
        case 'name_desc':
            $query .= "a.name DESC";
            break;
        case 'price_asc':
            $query .= "a.adoption_fee ASC";
            break;
        case 'price_desc':
            $query .= "a.adoption_fee DESC";
            break;
        default:
            $query .= "a.created_at DESC";
    }

    $stmt = prepareStatement($conn, $query);
    if (!empty($params)) {
        executeStatement($stmt, $params, $types);
    } else {
        executeStatement($stmt, [], "");
    }

    $animals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Error fetching animals: " . $e->getMessage());
    $error_message = "An error occurred while fetching the animals.";
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
            margin: 40px auto;
            padding: 20px;
        }

        .filters-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }

        .search-group {
            margin-bottom: 30px;
        }

        .search-input {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 20px;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #3693F0;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 8px;
        }

        .filter-select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            background-color: white;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        .filter-select:focus {
            outline: none;
            border-color: #3693F0;
        }

        .search-button {
            width: 100%;
            padding: 15px;
            background-color: #3693F0;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-button:hover {
            background-color: #2a75d0;
        }

        .results-info {
            margin-bottom: 30px;
            color: #666;
            font-size: 1.1rem;
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .pet-card:hover {
            transform: translateY(-5px);
        }

        .pet-image {
            width: 100%;
            height: 250px;
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
            color: white;
        }

        .status-pending {
            background-color: #ffc107;
            color: #000;
        }

        .status-adopted {
            background-color: #28a745;
        }

        .pet-info {
            padding: 20px;
            position: relative;
        }

        .pet-name {
            font-size: 1.5rem;
            margin: 0 0 5px 0;
            color: #333;
        }

        .pet-breed {
            color: #666;
            margin-bottom: 10px;
        }

        .pet-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 15px 0;
            font-size: 0.9rem;
            color: #666;
        }

        .pet-details div {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .adoption-fee {
            font-size: 1.25rem;
            color: #3693F0;
            font-weight: 500;
            margin: 15px 0;
        }

        .action-buttons {
            display: grid;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            display: block;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #3693F0;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2a75d0;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #bd2130;
        }

        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }

            .pets-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="pets-container">
    <div class="filters-section">
        <form method="GET" action="">
            <div class="search-group">
                <input type="text"
                       name="search"
                       class="search-input"
                       placeholder="Search by name, breed..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Species</label>
                    <select name="species" class="filter-select">
                        <option value="">All Species</option>
                        <option value="dog" <?php echo $species === 'dog' ? 'selected' : ''; ?>>Dogs</option>
                        <option value="cat" <?php echo $species === 'cat' ? 'selected' : ''; ?>>Cats</option>
                        <option value="other" <?php echo $species === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Size</label>
                    <select name="size" class="filter-select">
                        <option value="">Any Size</option>
                        <option value="small" <?php echo $size === 'small' ? 'selected' : ''; ?>>Small</option>
                        <option value="medium" <?php echo $size === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="large" <?php echo $size === 'large' ? 'selected' : ''; ?>>Large</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Sort By</label>
                    <select name="sort" class="filter-select">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="search-button">Search Pets</button>
        </form>
    </div>

    <div class="results-info">
        Found <?php echo count($animals); ?> pets
        <?php if ($search || $species || $size): ?>
            matching your criteria
        <?php endif; ?>
    </div>

    <div class="pets-grid">
        <?php foreach ($animals as $animal): ?>
            <div class="pet-card">
                <?php if ($animal['image_url']): ?>
                    <div style="position: relative;">
                        <img src="../uploads/animals/<?php echo htmlspecialchars($animal['image_url']); ?>"
                             alt="<?php echo htmlspecialchars($animal['name']); ?>"
                             class="pet-image">

                        <div class="status-badge status-<?php echo $animal['status']; ?>">
                            <?php echo ucfirst($animal['status']); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="pet-info">
                    <h3 class="pet-name"><?php echo htmlspecialchars($animal['name']); ?></h3>
                    <div class="pet-breed"><?php echo htmlspecialchars($animal['breed']); ?></div>

                    <div class="pet-details">
                        <div><?php echo htmlspecialchars($animal['age_years']); ?> years</div>
                        <div><?php echo $animal['gender'] === 'M' ? 'Male' : 'Female'; ?></div>
                        <div><?php echo ucfirst(htmlspecialchars($animal['size'])); ?></div>
                    </div>

                    <div class="adoption-fee">
                        $<?php echo number_format($animal['adoption_fee'], 2); ?>
                    </div>

                    <div class="action-buttons">
                        <a href="animal-details.php?id=<?php echo $animal['animal_id']; ?>" class="btn btn-primary">
                            View Details
                        </a>
                        <?php if (isLoggedIn()): ?>
                            <?php
                            try {
                                $is_saved = isPetSaved($_SESSION['user_id'], $animal['animal_id']);
                            } catch (Exception $e) {
                                $is_saved = false;
                            }
                            ?>
                            <form method="POST" action="animal-details.php?id=<?php echo $animal['animal_id']; ?>">
                                <input type="hidden" name="action" value="toggle_save">
                                <button type="submit" class="btn <?php echo $is_saved ? 'btn-danger' : 'btn-primary'; ?>">
                                    <?php echo $is_saved ? 'Remove from Saved' : 'Save This Pet'; ?>
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