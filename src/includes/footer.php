<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h3>Blue Collar Pets</h3>
            <p>123 Pet Street<br>
                Petville, CA 12345<br>
                Phone: (555) 123-4567<br>
                Email: info@bluecollarspets.com</p>
        </div>
        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="../public/animals.php">Available Pets</a></li>
                <li><a href="../public/reviews.php">Reviews</a></li>
                <li><a href="../public/contact.php">Contact Us</a></li>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <li><a href="../public/login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Hours of Operation</h3>
            <p>Monday - Friday: 9:00 AM - 6:00 PM<br>
                Saturday: 10:00 AM - 4:00 PM<br>
                Sunday: Closed</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Blue Collar Pets. All rights reserved.</p>
    </div>
</footer>
<?php if (isset($additionalScripts)) echo $additionalScripts; ?>
</body>
</html>