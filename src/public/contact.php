<?php
require_once '../includes/authentication.php';
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Blue Collar Pets</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .contact-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .contact-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #666;
            font-size: 1.1rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3693F0;
        }

        .submit-button {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #3693F0;
            color: white;
            border: none;
            border-radius: 300px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 30px;
        }

        .submit-button:hover {
            background-color: #2a75d0;
        }

        .contact-info {
            margin-top: 40px;
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }

        .contact-info h3 {
            color: #333;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 20px;
        }

        .info-item {
            text-align: center;
        }

        .info-item i {
            font-size: 2rem;
            color: #3693F0;
            margin-bottom: 15px;
        }

        .info-item p {
            color: #666;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="contact-container">
    <div class="contact-card">
        <h2 class="form-title">Have a Question?</h2>

        <form method="POST" action="">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="(123) 456-7890">
            </div>

            <div class="form-group">
                <label for="message">Your Message</label>
                <textarea id="message" name="message" required
                          placeholder="Please write your question or message here..."></textarea>
            </div>

            <button type="submit" class="submit-button">Send Message</button>
        </form>

        <div class="contact-info">
            <h3>Other Ways to Reach Us</h3>
            <div class="info-grid">
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <p>123 Pet Street<br>Petville, CA 12345</p>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <p>(555) 123-4567<br>Mon-Fri, 9am-6pm</p>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <p>info@bluecollarspets.com<br>We'll respond within 24hrs</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>