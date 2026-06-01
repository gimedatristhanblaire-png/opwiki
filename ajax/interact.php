<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_interactive.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Not logged in']); exit(); }

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- Theory Vote ---
if ($action === 'vote' && isset($_POST['theory_id'], $_POST['vote'])) {
    $theory_id = filter_input(INPUT_POST, 'theory_id', FILTER_VALIDATE_INT);
    $vote = $_POST['vote'];
    if (!$theory_id || !in_array($vote, ['up','down'])) { echo json_encode(['error' => 'Invalid params']); exit(); }

    $existing = get_user_vote($theory_id, $user_id, $conn);
    if ($existing === $vote) {
        $conn->query("DELETE FROM theory_votes WHERE theory_id = $theory_id AND user_id = $user_id");
    } else {
        $conn->query("REPLACE INTO theory_votes (theory_id, user_id, vote) VALUES ($theory_id, $user_id, '$vote')");
    }
    echo json_encode(['score' => get_vote_score($theory_id, $conn), 'user_vote' => get_user_vote($theory_id, $user_id, $conn)]);
    exit();
}

// --- Bookmark Toggle ---
if ($action === 'bookmark' && isset($_POST['target_type'], $_POST['target_id'])) {
    $target_type = $_POST['target_type'];
    $target_id = filter_input(INPUT_POST, 'target_id', FILTER_VALIDATE_INT);
    if (!in_array($target_type, ['article','theory']) || !$target_id) { echo json_encode(['error' => 'Invalid params']); exit(); }

    $bookmarked = is_bookmarked($user_id, $target_type, $target_id, $conn);
    if ($bookmarked) {
        $stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND target_type = ? AND target_id = ?");
        if ($stmt) { $stmt->bind_param("isi", $user_id, $target_type, $target_id); $stmt->execute(); $stmt->close(); }
        echo json_encode(['bookmarked' => false]);
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO bookmarks (user_id, target_type, target_id) VALUES (?, ?, ?)");
        if ($stmt) { $stmt->bind_param("isi", $user_id, $target_type, $target_id); $stmt->execute(); $stmt->close(); }
        echo json_encode(['bookmarked' => true]);
    }
    exit();
}

// --- Follow Toggle ---
if ($action === 'follow' && isset($_POST['target_user_id'])) {
    $target_user = filter_input(INPUT_POST, 'target_user_id', FILTER_VALIDATE_INT);
    if (!$target_user || $target_user == $user_id) { echo json_encode(['error' => 'Invalid target']); exit(); }

    $following = is_following($user_id, $target_user, $conn);
    if ($following) {
        $conn->query("DELETE FROM user_follows WHERE follower_id = $user_id AND following_id = $target_user");
        echo json_encode(['following' => false, 'followers' => get_follower_count($target_user, $conn)]);
    } else {
        $conn->query("INSERT IGNORE INTO user_follows (follower_id, following_id) VALUES ($user_id, $target_user)");
        echo json_encode(['following' => true, 'followers' => get_follower_count($target_user, $conn)]);
    }
    exit();
}

echo json_encode(['error' => 'Invalid action']);
