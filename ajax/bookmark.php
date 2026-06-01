<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_interactive.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$type = $_POST['type'] ?? $_GET['type'] ?? '';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!in_array($type, ['article', 'theory']) || !$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid params']);
    exit();
}

$has_bookmarked = is_bookmarked($user_id, $type, $id, $conn);

if ($has_bookmarked) {
    $stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND target_type = ? AND target_id = ?");
    if ($stmt) {
        $stmt->bind_param("isi", $user_id, $type, $id);
        $stmt->execute();
        $stmt->close();
    }
    $bookmarked = false;
} else {
    $stmt = $conn->prepare("INSERT IGNORE INTO bookmarks (user_id, target_type, target_id, created_at) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("isi", $user_id, $type, $id);
        $stmt->execute();
        $stmt->close();
    }
    $bookmarked = true;
}

echo json_encode(['bookmarked' => $bookmarked]);
