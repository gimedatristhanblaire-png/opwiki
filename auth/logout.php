<?php
// --- Includes ---
require_once __DIR__ . '/../config/config.php'; // Ensure BASE_URL is available

// --- Start Session ---
// Ensure session is started before accessing $_SESSION variables
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Unset all session variables ---
$_SESSION = array();

// --- Destroy the session cookie ---
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// --- Finally, destroy the session ---
session_destroy();

// --- Redirect to login page or homepage ---
header('Location: ' . BASE_URL . 'auth/login.php'); // Redirect to login
exit();
?>
