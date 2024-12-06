<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Pets - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .search-container {
            width: 100%;
            max-width: 1366px;
            padding: 20px 100px;
            margin: 20px auto;
        }

        .search-form {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 30px;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 300px;
            font-family: 'Crimson Pro', serif;
            font-size: 1.1rem;
        }

        .filter-select {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 300px;
            font-family: 'Crimson Pro', serif;
            font-size: 1.1rem;
            min-width: 150px;
        }

        .search-button {
            padding: 12px 30px;
            background-color: #3693F0;
            color: white;
            border: none;
            border-radius: 300px;
            font-family: 'Crimson Pro', serif;
            font-size: 1.1rem;
            cursor: pointer;
        }

        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            padding: 0 100px;
            max-width: 1366px;
            margin: 0 auto;
        }

        .pet-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }

        .pet-card:hover {
            transform: translateY(-5px);
        }

        .pet-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .pet-info {
            padding: 20px;
        }

        .pet-name {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #333;
        }

        .pet-details {
            color: #666;
            margin-bottom: 15px;
        }

        .pet-link {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3693F0;
            color: white;
            text-decoration: none;
            border-radius: 300px;
            font-size: 1rem;
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            margin: 40px 0;
            font-weight: 300;
            color: #333;
        }
    </style>
</head>

<body>


    <!-- Main Content -->
    <h1 class="section-title">Find Your Perfect Pet</h1>

    <!-- Search Section -->
    <div class="search-container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="search-form">
            <input type="text" name="search" placeholder="Search pets..." class="search-input">

            <select name="species" class="filter-select">
                <option value="">All Species</option>
                <option value="dog">Dogs</option>
                <option value="cat">Cats</option>
                <option value="other">Other</option>
            </select>

            <select name="age" class="filter-select">
                <option value="">Any Age</option>
                <option value="baby">Baby</option>
                <option value="young">Young</option>
                <option value="adult">Adult</option>
                <option value="senior">Senior</option>
            </select>

            <button type="submit" class="search-button">Search</button>
        </form>
    </div>

    <!-- Pets Grid -->
    <div class="pets-grid">
        <?php
        // Placeholder data - replace with database query
        $pets = [
            [
                'name' => 'Max',
                'species' => 'Dog',
                'breed' => 'Golden Retriever',
                'age' => '2 years',
                'image' => 'images/placeholder-dog.webp'
            ],
            [
                'name' => 'Luna',
                'species' => 'Cat',
                'breed' => 'Siamese',
                'age' => '1 year',
                'image' => 'images/placeholder-cat.jpeg'
            ],
        ];

        foreach ($pets as $pet) {
            echo '<div class="pet-card">
                    <img src="' . $pet['image'] . '" alt="' . $pet['name'] . '" class="pet-image">
                    <div class="pet-info">
                        <h2 class="pet-name">' . $pet['name'] . '</h2>
                        <div class="pet-details">
                            <p>' . $pet['breed'] . ' • ' . $pet['age'] . '</p>
                        </div>
                        <a href="#" class="pet-link">Learn More</a>
                    </div>
                  </div>';
        }
        ?>
    </div>

    <!-- Footer -->
    <?php include 'footerish.php'; ?>
</body>

</html>