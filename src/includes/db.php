<?php
require_once 'config.php';

function connectDB() {
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }

            // Set charset to handle special characters correctly
            $conn->set_charset("utf8mb4");

        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Unable to connect to the database. Please try again later.");
        }
    }

    return $conn;
}

// Function to safely prepare statements with error handling
function prepareStatement($conn, $sql) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare statement failed: " . $conn->error);
        throw new Exception("Database error occurred");
    }
    return $stmt;
}

// Function to safely execute statements with error handling
function executeStatement($stmt, $params = [], $types = "") {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Statement execution failed: " . $stmt->error);
        throw new Exception("Database error occurred");
    }

    return $stmt;
}

// Function to safely close database connection
function closeDB($conn) {
    if ($conn instanceof mysqli && !$conn->connect_error) {
        $conn->close();
    }
}