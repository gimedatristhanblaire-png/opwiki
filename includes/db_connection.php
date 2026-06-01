<?php
// Ensure config.php is included to get database credentials
require_once __DIR__ . '/../config/config.php';

// --- Establish Database Connection ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // In a production environment, you would log this error and show a generic message.
    // For development, showing the error is helpful.
    error_log("Database Connection Failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}

// Set the character set to UTF-8
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $conn->error);
}

?>
