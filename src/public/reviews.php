<?php
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews | Blue Collar Pets</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .review {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .review h3 {
            margin: 0;
            font-size: 1.2em;
            color: #555;
        }
        .review .stars {
            color: #f4c430;
        }
        .review p {
            color: #666;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Customer Reviews</h2>

        <!-- Review 1 -->
        <div class="review">
            <h3>Great Service! <span class="stars">★★★★★</span></h3>
            <p>"Blue Collar Pets went above and beyond for my dog grooming needs. Absolutely recommend them!"</p>
            <p>- James R.</p>
        </div>

        <!-- Review 2 -->
        <div class="review">
            <h3>Friendly Staff <span class="stars">★★★★★</span></h3>
            <p>"The team is so friendly and made my nervous cat feel at ease. Will be back!"</p>
            <p>- Sarah M.</p>
        </div>

        <!-- Review 3 -->
        <div class="review">
            <h3>Good Experience <span class="stars">★★★★☆</span></h3>
            <p>"Had a good experience with the pet boarding services. Only wish they had a bit more flexibility with pickup times."</p>
            <p>- Lucas W.</p>
        </div>

        <!-- Review 4 -->
        <div class="review">
            <h3>Wonderful! <span class="stars">★★★★★</span></h3>
            <p>"The trainers here really know their stuff! My dog’s behavior has improved so much!"</p>
            <p>- Emily K.</p>
        </div>

        <!-- Review 5 -->
        <div class="review">
            <h3>Would Recommend <span class="stars">★★★★☆</span></h3>
            <p>"Great selection of pet supplies and friendly advice. Prices are reasonable too."</p>
            <p>- Oliver B.</p>
        </div>
    </div>

</body>
</html>
