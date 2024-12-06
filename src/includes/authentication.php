<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

// Configure session cookie parameters BEFORE session_start()
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 86400, // Set to 24 hours instead of using default
    'path' => '/',
    'domain' => '',  // Leave empty to use current domain
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to authenticate user
function authenticateUser($email, $password) {
    try {
        $conn = connectDB();
        $stmt = prepareStatement($conn,
            "SELECT user_id, email, password_hash, role, first_name, last_name, is_active 
             FROM users WHERE email = ?"
        );
        executeStatement($stmt, [$email], "s");
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Check if account is active
            if (!$user['is_active']) {
                return false;
            }

            if (password_verify($password, $user['password_hash'])) {
                // Store user data in session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['created'] = time();
                $_SESSION['last_activity'] = time();

                // Update last login timestamp
                $updateStmt = prepareStatement($conn,
                    "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?"
                );
                executeStatement($updateStmt, [$user['user_id']], "s");

                return true;
            }
        }
        return false;
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Role checking functions
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isEmployee() {
    return isset($_SESSION['role']) &&
        in_array($_SESSION['role'], ['employee', 'admin']);
}

function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

// Access control functions
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: /public/login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: /public/unauthorized.php");
        exit();
    }
}

function requireEmployee() {
    requireLogin();
    if (!isEmployee()) {
        header("Location: /public/unauthorized.php");
        exit();
    }
}

// Logout function
function logout() {
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    session_destroy();
}

// Session maintenance
function checkSessionTimeout() {
    $timeout = 1800; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        logout();
        header("Location: /public/login.php?timeout=1");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Function to create new user account
function createUser($email, $password, $firstName, $lastName, $role = 'user', $phone = null) {
    try {
        $conn = connectDB();

        // Validate role
        $validRoles = ['user', 'employee', 'admin'];
        if (!in_array($role, $validRoles)) {
            throw new Exception("Invalid role specified");
        }

        // Check if email already exists
        $stmt = prepareStatement($conn,
            "SELECT user_id FROM users WHERE email = ?"
        );
        executeStatement($stmt, [$email], "s");
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email already exists");
        }

        // Create new user
        $userId = generateUUID();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = prepareStatement($conn,
            "INSERT INTO users (
                user_id, email, password_hash, first_name, last_name, 
                role, phone, created_at, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, TRUE)"
        );
        executeStatement($stmt, [
            $userId, $email, $passwordHash, $firstName,
            $lastName, $role, $phone
        ], "sssssss");

        return $userId;
    } catch (Exception $e) {
        error_log("User creation error: " . $e->getMessage());
        throw $e;
    }
}

// Function to get user info
function getUserInfo($userId = null) {
    if ($userId === null) {
        $userId = $_SESSION['user_id'] ?? null;
    }

    if (!$userId) {
        return null;
    }

    try {
        $conn = connectDB();
        $stmt = prepareStatement($conn,
            "SELECT user_id, email, first_name, last_name, role, phone, 
                    created_at, last_login, is_active 
             FROM users 
             WHERE user_id = ?"
        );
        executeStatement($stmt, [$userId], "s");
        return $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error fetching user info: " . $e->getMessage());
        return null;
    }
}

// Function to update user profile
function updateUserProfile($userId, $data) {
    try {
        $conn = connectDB();
        $allowedFields = ['first_name', 'last_name', 'phone'];
        $updates = [];
        $params = [];
        $types = '';

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = sanitizeInput($data[$field]);
                $types .= 's';
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $userId;
        $types .= 's';

        $stmt = prepareStatement($conn,
            "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = ?"
        );
        executeStatement($stmt, $params, $types);

        return true;
    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        return false;
    }
}

// Function to change password
function changePassword($userId, $currentPassword, $newPassword) {
    try {
        $conn = connectDB();

        // Verify current password
        $stmt = prepareStatement($conn,
            "SELECT password_hash FROM users WHERE user_id = ?"
        );
        executeStatement($stmt, [$userId], "s");
        $result = $stmt->get_result()->fetch_assoc();

        if (!password_verify($currentPassword, $result['password_hash'])) {
            throw new Exception("Current password is incorrect");
        }

        // Update to new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = prepareStatement($conn,
            "UPDATE users SET password_hash = ? WHERE user_id = ?"
        );
        executeStatement($stmt, [$newPasswordHash, $userId], "ss");

        return true;
    } catch (Exception $e) {
        error_log("Password change error: " . $e->getMessage());
        throw $e;
    }
}

// Keep session secure by regenerating ID periodically
if (isLoggedIn()) {
    checkSessionTimeout();
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 60 * 30) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}