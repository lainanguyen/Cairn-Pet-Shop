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

<?php
// Include the footer
include '../includes/footer.php';
?>