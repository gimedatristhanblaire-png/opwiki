<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db_connection.php';

header('Content-Type: application/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc><?php echo BASE_URL; ?></loc><priority>1.0</priority></url>
    <url><loc><?php echo BASE_URL; ?>wiki/</loc><priority>0.9</priority></url>
    <url><loc><?php echo BASE_URL; ?>theories/</loc><priority>0.9</priority></url>
    <url><loc><?php echo BASE_URL; ?>leaderboard/</loc><priority>0.7</priority></url>
    <url><loc><?php echo BASE_URL; ?>lore/</loc><priority>0.9</priority></url>
    <url><loc><?php echo BASE_URL; ?>lore/browse.php?type=characters</loc><priority>0.8</priority></url>
    <url><loc><?php echo BASE_URL; ?>lore/browse.php?type=devil_fruits</loc><priority>0.8</priority></url>
    <url><loc><?php echo BASE_URL; ?>lore/browse.php?type=arcs</loc><priority>0.8</priority></url>
    <url><loc><?php echo BASE_URL; ?>lore/browse.php?type=timeline</loc><priority>0.8</priority></url>
    <url><loc><?php echo BASE_URL; ?>lore/timeline.php</loc><priority>0.8</priority></url>
    <url><loc><?php echo BASE_URL; ?>chapters/</loc><priority>0.8</priority></url>
    <url><loc><?php echo BASE_URL; ?>rss.php</loc><priority>0.5</priority></url>
<?php
$result6 = $conn->query("SELECT id FROM chapters WHERE id % 50 = 1");
if ($result6) {
    while ($row = $result6->fetch_assoc()) {
        echo "    <url><loc>" . BASE_URL . "chapters/view.php?type=chapter&id=" . $row['id'] . "</loc><priority>0.3</priority></url>\n";
    }
}
$result7 = $conn->query("SELECT id FROM episodes WHERE id % 50 = 1");
if ($result7) {
    while ($row = $result7->fetch_assoc()) {
        echo "    <url><loc>" . BASE_URL . "chapters/view.php?type=episode&id=" . $row['id'] . "</loc><priority>0.3</priority></url>\n";
    }
}
?>
<?php
$result = $conn->query("SELECT slug, updated_at FROM wiki_articles WHERE status='approved'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "    <url><loc>" . BASE_URL . "wiki/view.php?slug=" . urlencode($row['slug']) . "</loc><lastmod>" . date('Y-m-d', strtotime($row['updated_at'])) . "</lastmod><priority>0.8</priority></url>\n";
    }
}
$result2 = $conn->query("SELECT slug, updated_at FROM theories WHERE status='approved'");
if ($result2) {
    while ($row = $result2->fetch_assoc()) {
        echo "    <url><loc>" . BASE_URL . "theories/view.php?slug=" . urlencode($row['slug']) . "</loc><lastmod>" . date('Y-m-d', strtotime($row['updated_at'])) . "</lastmod><priority>0.8</priority></url>\n";
    }
}
$result3 = $conn->query("SELECT id FROM characters");
if ($result3) {
    while ($row = $result3->fetch_assoc()) {
        echo "    <url><loc>" . BASE_URL . "lore/view.php?type=characters&id=" . $row['id'] . "</loc><priority>0.6</priority></url>\n";
    }
}
$result4 = $conn->query("SELECT id FROM devil_fruits");
if ($result4) {
    while ($row = $result4->fetch_assoc()) {
        echo "    <url><loc>" . BASE_URL . "lore/view.php?type=devil_fruits&id=" . $row['id'] . "</loc><priority>0.6</priority></url>\n";
    }
}
$result5 = $conn->query("SELECT id FROM arcs");
if ($result5) {
    while ($row = $result5->fetch_assoc()) {
        echo "    <url><loc>" . BASE_URL . "lore/view.php?type=arcs&id=" . $row['id'] . "</loc><priority>0.6</priority></url>\n";
    }
}
?>
</urlset>
