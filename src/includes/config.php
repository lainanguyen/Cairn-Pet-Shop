<?php
// config.php - Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'blue_collar_pets');

// Establish database connection
function connectDB()
{
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// Start session management
session_start();

// User authentication function
function authenticateUser($email, $password)
{
    $conn = connectDB();

    $sql = "SELECT id, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
    }
    return false;
}

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is employee
function isEmployee()
{
    return isset($_SESSION['role']) &&
        ($_SESSION['role'] === 'employee' || $_SESSION['role'] === 'admin');
}

// Logout function
function logout()
{
    session_start();
    session_destroy();
    header("Location: login.php");
    exit();
}
