<?php
$pageTitle = 'Welcome';
require_once '../includes/authentication.php';  // This includes config.php and functions.php

$additionalStyles = <<<HTML
    <style>
        main {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            width: 100%;
            max-width: 1366px;
            margin-top: 50px;
            padding-left: 100px;
        }

        .content {
            max-width: 50%;
            margin-top: 20px;
        }

        .content h2 {
            font-family: "Crimson Pro", serif;
            font-optical-sizing: auto;
            font-weight: 300;
            font-size: 4rem;
            color: #333;
        }

        .content h1 {
            font-family: "Crimson Pro", serif;
            font-optical-sizing: auto;
            font-weight: 300;
            font-size: 6.25rem;
            margin-top: 10px;
            color: #333;
        }

        .button {
            display: inline-block;
            margin-top: 30px;
            padding: 20px 60px;
            background-color: #3693F0;
            color: white;
            font-size: 2rem;
            border-radius: 300px;
            text-decoration: none;
            font-family: "Crimson Pro", serif;
            font-optical-sizing: auto;
            font-weight: 300;
            text-align: center;
        }

        .image {
            max-width: 50%;
            display: flex;
            justify-content: flex-end;
        }

        .image img {
            width: 100%;
            max-width: 580px;
            height: auto;
            position: relative;
            right: -38px;
            top: -44px;
        }
        
        .about_us {
        text-align: center;
        margin: 35px auto;
        max-width: 1000px;
        padding: 20px;
        background-color: #ffffff; 
        border: 10px solid #c0cfe7; 
        border-radius: 10px; 
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .about_us h2 {
        text-align: center;
        font-size: 28px;
        color: #02022f; 
        margin-bottom: 10px;
        }
        .fun_facts {
        text-align: center;
        margin: 35px auto;
        max-width: 1000px;
        padding: 20px;
        background-color: #ffffff; 
        border: 10px solid #c0cfe7; 
        border-radius: 10px; 
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
HTML;

// Include the header
include '../includes/header.php';
?>

    <main>
        <div class="content">
            <h2>Welcome to</h2>
            <h1>BLUE COLLAR PETS.</h1>
            <a href="../public/animals.php" class="button">Meet our pets</a>
        </div>
        <div class="image">
            <img src="../assets/images/dog2.png" alt="Helmet Dog">
        </div>
    </main>
    <div class="about_us">
        <h2> Who Are we? </h2>
        <p>
            <strong> Blue Collar Pets is a pet store that specializes in presenting you with the cutest, hardworking, and loyal companions.
                Our signature blue collar represents the loyalty and dedication of each animal, as is our commitment to ensuring they find the perfect, loving home. </strong>
        </p>
        <p>
            Each of our pets were carefully selected. Each comes with its own unique story, personality and charm. No two are alike!
            We have a wonderful team of hardworking employees who care for all our our hard working pets.
            We provide a wide selection of food, toys, beds and more for every animal. Stop in today to find your fur-ever pet or stock up for the one at home!
        </p>
    </div>

    <div class = "fun_facts">
        <h1> Fun Facts </h1>
        <h2> Fact 1: </h2>
        <p>We are a no-kill pet store. All of our pets stay here until they are adopted.</p>
        <h2> Fact 2: </h2>
        <p>Over half of our employees adopted their own pets from us.</p>
        <h2> Fact 3: </h2>
        <p>We allow for up to 3 hours of visitation, in one visit, before adoption with any potential pet owners.</p>
        <h2> Fact 4: </h2>
        <p>We have more than just cats and dogs. Visit our store to find reptiles, birds and more.</p>
        <h2> Fact 5: </h2>
        <p>We provide 3 free vet visits (with our vets) with each adopted pet.</p>

    </div>

<?php
// Include the footer
include '../includes/footer.php';
?>