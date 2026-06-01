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

$theory_id = filter_input(INPUT_POST, 'theory_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'theory_id', FILTER_VALIDATE_INT);
$vote = $_POST['vote'] ?? $_GET['vote'] ?? '';
$user_id = $_SESSION['user_id'];

if (!$theory_id || !in_array($vote, ['up', 'down'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid params']);
    exit();
}

$existing = get_user_vote($theory_id, $user_id, $conn);

if ($existing === $vote) {
    $stmt = $conn->prepare("DELETE FROM theory_votes WHERE theory_id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $theory_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    $vote_result = null;
} else {
    if ($existing) {
        $stmt = $conn->prepare("UPDATE theory_votes SET vote = ? WHERE theory_id = ? AND user_id = ?");
        if ($stmt) {
            $stmt->bind_param("sii", $vote, $theory_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO theory_votes (theory_id, user_id, vote, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("iis", $theory_id, $user_id, $vote);
            $stmt->execute();
            $stmt->close();
        }
    }
    $vote_result = $vote;
}

$score = get_vote_score($theory_id, $conn);
echo json_encode(['vote' => $vote_result, 'score' => $score]);
