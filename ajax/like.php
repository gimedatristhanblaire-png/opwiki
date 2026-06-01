<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_rep.php';
require_once __DIR__ . '/../includes/functions_notif.php';

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

$table = ($type === 'article') ? 'article_likes' : 'theory_likes';
$col = ($type === 'article') ? 'article_id' : 'theory_id';

$has_liked = user_has_liked($type, $id, $user_id, $conn);

$author_id = null; $content_title = ''; $content_slug = '';
if ($type === 'article') {
    $r = $conn->query("SELECT user_id, title, slug FROM wiki_articles WHERE id = $id");
    if ($r && $row = $r->fetch_assoc()) { $author_id = $row['user_id']; $content_title = $row['title']; $content_slug = $row['slug']; }
} else {
    $r = $conn->query("SELECT user_id, title, slug FROM theories WHERE id = $id");
    if ($r && $row = $r->fetch_assoc()) { $author_id = $row['user_id']; $content_title = $row['title']; $content_slug = $row['slug']; }
}

if ($has_liked) {
    $stmt = $conn->prepare("DELETE FROM $table WHERE $col = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    $rep_points = ($type === 'article') ? -1 : -2;
    add_reputation($user_id, $rep_points, "Unliked $type", $conn, $type, $id);
    $liked = false;
} else {
    $stmt = $conn->prepare("INSERT IGNORE INTO $table ($col, user_id, created_at) VALUES (?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    $rep_points = ($type === 'article') ? 1 : 2;
    add_reputation($user_id, $rep_points, "Liked $type", $conn, $type, $id);
    if ($author_id && $author_id != $user_id) {
        $link = BASE_URL . ($type === 'article' ? 'wiki' : 'theories') . '/view.php?slug=' . urlencode($content_slug);
        create_notification($author_id, 'like_received', 'Someone liked your ' . $type . ' "' . htmlspecialchars($content_title) . '"', $conn, $link, $type, $id);
    }
    $liked = true;
}

$count = get_like_count($type, $id, $conn);
echo json_encode(['liked' => $liked, 'count' => $count]);
