<?php
require_once 'config.php';

// UUID Generator
function generateUUID() {
    if (function_exists('random_bytes')) {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Input Sanitization
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validation Functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match("/^[0-9]{10}$/", $phone);
}

// Flash Messages
function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

// File Upload Handling
function handleFileUpload($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'webp']) {
    try {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload failed with error code " . $file['error']);
        }

        // Validate file type
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowedTypes));
        }

        // Generate unique filename
        $fileName = generateUUID() . '.' . $fileType;
        $targetPath = $targetDir . '/' . $fileName;

        // Create directory if it doesn't exist
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Failed to move uploaded file.");
        }

        return $fileName;
    } catch (Exception $e) {
        error_log("File upload error: " . $e->getMessage());
        throw $e;
    }
}

// URL Helpers
function getBaseURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . BASE_URL;
}

function redirectTo($path) {
    header("Location: " . getBaseURL() . ltrim($path, '/'));
    exit();
}

// Error Handling
function displayError($message) {
    return "<div class='error-message'>" . htmlspecialchars($message) . "</div>";
}

function displaySuccess($message) {
    return "<div class='success-message'>" . htmlspecialchars($message) . "</div>";
}

// Pagination Helper
function getPaginationData($page, $totalItems, $itemsPerPage = 10) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $itemsPerPage;

    return [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'items_per_page' => $itemsPerPage,
        'offset' => $offset
    ];
}


// Add these functions to functions.php

/**
 * Save a pet for a user
 */
function savePet($userId, $animalId)
{
    try {
        $conn = connectDB();

        // Check if already saved
        if (isPetSaved($userId, $animalId)) {
            return false;
        }

        $stmt = prepareStatement($conn,
            "INSERT INTO saved_pets (id, user_id, animal_id)
             VALUES (?, ?, ?)"
        );
        executeStatement($stmt, [
            generateUUID(),
            $userId,
            $animalId
        ], "sss");

        return true;
    } catch (Exception $e) {
        error_log("Error saving pet: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Remove a pet from user's saved list
 */
function unsavePet($userId, $animalId)
{
    try {
        $conn = connectDB();

        // Check if saved first
        if (!isPetSaved($userId, $animalId)) {
            return false;
        }

        $stmt = prepareStatement($conn,
            "DELETE FROM saved_pets 
             WHERE user_id = ? AND animal_id = ?"
        );
        executeStatement($stmt, [$userId, $animalId], "ss");

        return true;
    } catch (Exception $e) {
        error_log("Error unsaving pet: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Check if a pet is saved by a user
 */
function isPetSaved($userId, $animalId)
{
    try {
        $conn = connectDB();
        $stmt = prepareStatement($conn,
            "SELECT id FROM saved_pets 
             WHERE user_id = ? AND animal_id = ?"
        );
        executeStatement($stmt, [$userId, $animalId], "ss");
        return $stmt->get_result()->fetch_assoc() !== null;
    } catch (Exception $e) {
        error_log("Error checking saved pet: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Get count of saved pets for a user
 */
function getSavedPetsCount($userId)
{
    try {
        $conn = connectDB();
        $stmt = prepareStatement($conn,
            "SELECT COUNT(*) as count 
             FROM saved_pets 
             WHERE user_id = ?"
        );
        executeStatement($stmt, [$userId], "s");
        return $stmt->get_result()->fetch_assoc()['count'];
    } catch (Exception $e) {
        error_log("Error getting saved pets count: " . $e->getMessage());
        return 0;
    }
}