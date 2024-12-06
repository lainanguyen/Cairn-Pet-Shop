<?php
// footer.php
?>
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-brand">
                    <img src="../assets/images/logo.png" alt="Blue Collar Pets Logo" class="footer-logo">
                    <p class="footer-tagline">Finding Forever Homes for Our Four-Legged Friends</p>
                </div>
                <div class="footer-social">
                    <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="../public/animals.php">Available Pets</a></li>
                    <li><a href="../public/adoption_form.php">Adoption Process</a></li>
                    <li><a href="../public/reviews.php">Success Stories</a></li>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li><a href="../public/login.php">Login</a></li>
                        <li><a href="../public/register.php">Create Account</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Visit Us</h3>
                <address>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Pet Street</p>
                    <p>Petville, CA 12345</p>
                    <p><i class="fas fa-phone"></i> (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> info@bluecollarspets.com</p>
                </address>
            </div>

            <div class="footer-section">
                <h3>Hours of Operation</h3>
                <div class="hours-grid">
                    <div class="day">Monday - Friday:</div>
                    <div class="time">9:00 AM - 6:00 PM</div>
                    <div class="day">Saturday:</div>
                    <div class="time">10:00 AM - 4:00 PM</div>
                    <div class="day">Sunday:</div>
                    <div class="time">Closed</div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p>&copy; <?php echo date('Y'); ?> Blue Collar Pets. All rights reserved.</p>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <span class="separator">•</span>
                    <a href="#">Terms of Service</a>
                    <span class="separator">•</span>
                    <a href="../public/contact.php">Contact Us</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Add Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        footer {
            background-color: #02022f;
            color: white;
            padding: 60px 0 0;
            margin-top: 80px;
            width: 100%;
        }

        .footer-content {
            max-width: 1366px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .footer-brand {
            margin-bottom: 20px;
        }

        .footer-logo {
            max-width: 180px;
            height: auto;
            margin-bottom: 15px;
        }

        .footer-tagline {
            font-size: 1.1rem;
            color: #a0b7db;
            margin-bottom: 20px;
        }

        .footer-social {
            display: flex;
            gap: 15px;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background-color: #3693F0;
            transform: translateY(-3px);
        }

        .footer-section h3 {
            color: #3693F0;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 12px;
        }

        .footer-section ul li a {
            color: #a0b7db;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: #3693F0;
        }

        address {
            font-style: normal;
            color: #a0b7db;
        }

        address p {
            margin-bottom: 10px;
        }

        address i {
            width: 20px;
            color: #3693F0;
            margin-right: 10px;
        }

        .hours-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px;
            color: #a0b7db;
        }

        .day {
            font-weight: 500;
        }

        .footer-bottom {
            margin-top: 60px;
            padding: 20px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-bottom-content {
            max-width: 1366px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-bottom p {
            color: #a0b7db;
            margin: 0;
        }

        .footer-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .footer-links a {
            color: #a0b7db;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #3693F0;
        }

        .separator {
            color: #a0b7db;
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-social {
                justify-content: center;
            }

            .footer-bottom-content {
                flex-direction: column;
                text-align: center;
            }

            .hours-grid {
                justify-content: center;
            }

            address {
                text-align: center;
            }
        }
    </style>

<?php if (isset($additionalScripts)) echo $additionalScripts; ?>