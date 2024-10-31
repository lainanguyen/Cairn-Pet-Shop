<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Inquiries and Business Questioning</title>
</head>
<body>
    <h2>Submit Your Inquiry</h2>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Capture the form data
        $first_name = htmlspecialchars($_POST['first_name']);
        $last_name = htmlspecialchars($_POST['last_name']);
        $cell_phone = htmlspecialchars($_POST['cell_phone']);
        $email = htmlspecialchars($_POST['email']);
        $comment = htmlspecialchars($_POST['comment']);

        // Display the captured data
        echo "<h3>Your Inquiry Details</h3>";
        echo "First Name: " . $first_name . "<br>";
        echo "Last Name: " . $last_name . "<br>";
        echo "Cell Phone: " . $cell_phone . "<br>";
        echo "Email: " . $email . "<br>";
        echo "Comment/Inquiry: " . $comment . "<br>";
    } else {
    ?>

    <!-- Form HTML -->
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" required><br><br>

        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" required><br><br>

        <label for="cell_phone">Cell Phone Number:</label>
        <input type="tel" id="cell_phone" name="cell_phone" pattern="[0-9]{10}" required><br><br>

        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" required><br><br>

        <label for="comment">Please leave your comment or inquiry below:</label><br>
        <textarea id="comment" name="comment" rows="4" cols="50" required></textarea><br><br>

        <input type="submit" value="Submit">
    </form>

    <?php
    }
    ?>
</body>
</html>
