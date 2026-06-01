<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_notif.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']); exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'count') {
    $count = get_unread_notification_count($user_id, $conn);
    echo json_encode(['count' => $count]);
    exit();
}

if ($action === 'list') {
    $notifs = get_notifications($user_id, $conn, 10);
    $list = [];
    foreach ($notifs as $n) {
        $list[] = [
            'id' => $n['id'],
            'message' => $n['message'],
            'link' => $n['link'],
            'is_read' => (bool)$n['is_read'],
            'time_ago' => time_ago($n['created_at'])
        ];
    }
    echo json_encode(['notifications' => $list]);
    exit();
}

if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $nid = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($nid) {
        mark_notification_read($nid, $user_id, $conn);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Invalid id']);
    }
    exit();
}

echo json_encode(['error' => 'Invalid action']);
