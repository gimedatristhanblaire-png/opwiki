<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

function json_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit();
}

function json_success($data) {
    echo json_encode($data);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'articles':
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $conn->prepare("SELECT wa.id, wa.title, wa.slug, wa.content, wa.category, wa.spoiler_level, wa.created_at, wa.updated_at, u.username, (SELECT COUNT(*) FROM article_likes WHERE article_id = wa.id) as likes FROM wiki_articles wa JOIN users u ON wa.user_id = u.id WHERE wa.id = ? AND wa.status = 'approved'");
            if ($stmt) { $stmt->bind_param("i", $id); $stmt->execute(); $r = $stmt->get_result(); $row = $r->fetch_assoc(); $stmt->close(); }
            $row ? json_success($row) : json_error('Not found', 404);
        }
        $page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
        $per = min(50, max(1, filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT) ?: 10));
        $offset = ($page - 1) * $per;
        $items = []; $total = 0;
        $r = $conn->query("SELECT COUNT(*) as c FROM wiki_articles WHERE status='approved'");
        if ($r) $total = $r->fetch_assoc()['c'];
        $stmt = $conn->prepare("SELECT wa.id, wa.title, wa.slug, wa.category, wa.spoiler_level, wa.updated_at, u.username, (SELECT COUNT(*) FROM article_likes WHERE article_id = wa.id) as likes FROM wiki_articles wa JOIN users u ON wa.user_id = u.id WHERE wa.status='approved' ORDER BY wa.updated_at DESC LIMIT ? OFFSET ?");
        if ($stmt) { $stmt->bind_param("ii", $per, $offset); $stmt->execute(); $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close(); }
        json_success(['total' => $total, 'page' => $page, 'per_page' => $per, 'items' => $items]);

    case 'theories':
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $conn->prepare("SELECT t.id, t.title, t.slug, t.content, t.spoiler_level, t.created_at, t.updated_at, u.username, (SELECT COUNT(*) FROM theory_likes WHERE theory_id = t.id) as likes, (SELECT COUNT(*)-COUNT(*)+SUM(CASE WHEN vote='up' THEN 1 ELSE 0 END)-SUM(CASE WHEN vote='down' THEN 1 ELSE 0 END) FROM theory_votes WHERE theory_id = t.id) as score FROM theories t JOIN users u ON t.user_id = u.id WHERE t.id = ? AND t.status='approved'");
            if ($stmt) { $stmt->bind_param("i", $id); $stmt->execute(); $r = $stmt->get_result(); $row = $r->fetch_assoc(); $stmt->close(); }
            $row ? json_success($row) : json_error('Not found', 404);
        }
        $page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
        $per = min(50, max(1, filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT) ?: 10));
        $offset = ($page - 1) * $per;
        $items = []; $total = 0;
        $r = $conn->query("SELECT COUNT(*) as c FROM theories WHERE status='approved'");
        if ($r) $total = $r->fetch_assoc()['c'];
        $stmt = $conn->prepare("SELECT t.id, t.title, t.slug, t.spoiler_level, t.created_at, u.username, (SELECT COUNT(*) FROM theory_likes WHERE theory_id = t.id) as likes FROM theories t JOIN users u ON t.user_id = u.id WHERE t.status='approved' ORDER BY t.created_at DESC LIMIT ? OFFSET ?");
        if ($stmt) { $stmt->bind_param("ii", $per, $offset); $stmt->execute(); $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close(); }
        json_success(['total' => $total, 'page' => $page, 'per_page' => $per, 'items' => $items]);

    case 'leaderboard':
        $top_users = [];
        $r = $conn->query("SELECT id, username, reputation_points FROM users ORDER BY reputation_points DESC LIMIT 50");
        if ($r) $top_users = $r->fetch_all(MYSQLI_ASSOC);
        json_success(['users' => $top_users]);

    case 'lore':
        $type = $_GET['type'] ?? 'characters';
        $valid = ['characters', 'devil_fruits', 'arcs', 'timeline'];
        if (!in_array($type, $valid)) json_error('Invalid lore type');
        $items = [];
        $r = $conn->query("SELECT * FROM `$type` ORDER BY id ASC");
        if ($r) $items = $r->fetch_all(MYSQLI_ASSOC);
        json_success(['type' => $type, 'items' => $items]);

    case 'search':
        $q = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING);
        if (empty($q)) json_error('Missing search query');
        $terms = '+' . implode(' +', explode(' ', $q));
        $results = [];
        $r = $conn->query("(SELECT 'article' as type, wa.id, wa.title, wa.slug, wa.updated_at as date, u.username FROM wiki_articles wa JOIN users u ON wa.user_id=u.id WHERE MATCH(wa.title,wa.content) AGAINST('$terms' IN BOOLEAN MODE) AND wa.status='approved' LIMIT 10) UNION ALL (SELECT 'theory' as type, t.id, t.title, t.slug, t.created_at as date, u.username FROM theories t JOIN users u ON t.user_id=u.id WHERE MATCH(t.title,t.content) AGAINST('$terms' IN BOOLEAN MODE) AND t.status='approved' LIMIT 10)");
        if ($r) $results = $r->fetch_all(MYSQLI_ASSOC);
        json_success(['query' => $q, 'results' => $results]);

    default:
        header('Content-Type: text/plain');
        echo "OPWiki API\n\nAvailable endpoints:\n  ?action=articles&page=1&per_page=10\n  ?action=articles&id=5\n  ?action=theories&page=1&per_page=10\n  ?action=theories&id=5\n  ?action=leaderboard\n  ?action=lore&type=characters\n  ?action=search&q=luffy\n";
}
