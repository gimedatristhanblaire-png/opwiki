<?php
// AJAX endpoint for lore browsing — returns rendered cards
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/card_renderer.php';

header('Content-Type: text/html; charset=utf-8');

$type = $_GET['type'] ?? 'characters';
$query = trim($_GET['q'] ?? '');
$valid_types = ['characters', 'devil_fruits', 'arcs', 'timeline'];
if (!in_array($type, $valid_types)) $type = 'characters';

$table = $type;
$title_field = $type === 'timeline' ? 'title' : 'name';
$order = ($type === 'arcs') ? 'arc_number ASC' : "$title_field ASC";

if ($query !== '') {
    $q = $conn->real_escape_string($query);
    $like = "LIKE '%$q%'";
    $conditions = ["$title_field $like"];
    if ($type === 'characters') {
        $conditions[] = "affiliation $like"; $conditions[] = "japanese_name $like";
        $conditions[] = "romanji $like"; $conditions[] = "status $like";
    } elseif ($type === 'devil_fruits') {
        $conditions[] = "japanese_name $like"; $conditions[] = "type $like"; $conditions[] = "current_holder $like";
    } elseif ($type === 'arcs') {
        $conditions[] = "saga $like"; $conditions[] = "description $like";
    } elseif ($type === 'timeline') {
        $conditions[] = "description $like"; $conditions[] = "event_date $like";
    }
    $sql = "SELECT * FROM `$table` WHERE " . implode(' OR ', $conditions) . " ORDER BY $order";
} else {
    $sql = "SELECT * FROM `$table` ORDER BY $order";
}

$items = [];
$r = $conn->query($sql);
if ($r) { while ($row = $r->fetch_assoc()) { $items[] = $row; } }

if (empty($items)) {
    echo '<div class="lore-empty">🔍 No results found' . ($query ? ' for "' . htmlspecialchars($query) . '"' : '') . '.</div>';
} else {
    foreach ($items as $item) {
        echo render_lore_card($item, $type, $conn);
    }
}
