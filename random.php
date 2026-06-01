<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db_connection.php';

// Pick a random content type and redirect
$sources = [
    ['table' => 'wiki_articles', 'where' => "status='approved'", 'url' => 'wiki/view.php?slug=', 'slug_field' => 'slug'],
    ['table' => 'theories', 'where' => "status='approved'", 'url' => 'theories/view.php?slug=', 'slug_field' => 'slug'],
    ['table' => 'characters', 'where' => '1=1', 'url' => 'lore/view.php?type=characters&id=', 'slug_field' => 'id'],
    ['table' => 'devil_fruits', 'where' => '1=1', 'url' => 'lore/view.php?type=devil_fruits&id=', 'slug_field' => 'id'],
    ['table' => 'arcs', 'where' => '1=1', 'url' => 'lore/view.php?type=arcs&id=', 'slug_field' => 'id'],
    ['table' => 'timeline', 'where' => '1=1', 'url' => 'lore/view.php?type=timeline&id=', 'slug_field' => 'id'],
];

// Filter to only sources that have data
$available = [];
foreach ($sources as $s) {
    $r = $conn->query("SELECT COUNT(*) as c FROM {$s['table']} WHERE {$s['where']}");
    if ($r && $r->fetch_assoc()['c'] > 0) $available[] = $s;
}

if (empty($available)) {
    header('Location: ' . BASE_URL . 'wiki/');
    exit();
}

$pick = $available[array_rand($available)];
$r = $conn->query("SELECT {$pick['slug_field']} as target FROM {$pick['table']} WHERE {$pick['where']} ORDER BY RAND() LIMIT 1");
if ($r && $row = $r->fetch_assoc()) {
    header('Location: ' . BASE_URL . $pick['url'] . urlencode($row['target']));
} else {
    header('Location: ' . BASE_URL . 'wiki/');
}
exit();
