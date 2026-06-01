<?php
$c = new mysqli('sql305.infinityfree.com', 'if0_42065833', 'yamatekudisai1', 'if0_42065833_opwiki');

// Find duplicates by name
$r = $c->query("SELECT name, GROUP_CONCAT(id ORDER BY id) as ids, COUNT(*) as cnt FROM characters GROUP BY name HAVING cnt > 1");
$found = false;
while ($x = $r->fetch_assoc()) {
    $found = true;
    $ids = explode(',', $x['ids']);
    $keep = $ids[0]; // keep lowest id
    array_shift($ids); // remove first one
    $delete = implode(',', $ids);
    echo "<p>Duplicates: {$x['name']} - Keeping ID {$keep}, Deleting IDs: {$delete}</p>";
    $c->query("DELETE FROM characters WHERE id IN ($delete)");
    echo "<p>Deleted {$x['name']} duplicates.</p>";
}

if (!$found) {
    echo "<p>No duplicate character names found.</p>";
}

// Also check for Kurohige (id 28) - duplicate of Marshall D. Teach
$r = $c->query("SELECT id FROM characters WHERE id=28");
if ($r->num_rows > 0) {
    $c->query("DELETE FROM characters WHERE id=28");
    echo "<p>Deleted Kurohige (id 28) - duplicate of Marshall D. Teach.</p>";
}

// Check auto_increment
$r = $c->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA='if0_42065833_opwiki' AND TABLE_NAME='characters'");
$x = $r->fetch_assoc();
echo "<p>Auto increment: {$x['AUTO_INCREMENT']}</p>";

echo "<h3>Final character list:</h3>";
echo "<table border=1><tr><th>ID</th><th>Name</th></tr>";
$r = $c->query("SELECT id, name FROM characters ORDER BY id");
while ($x = $r->fetch_assoc()) {
    echo "<tr><td>{$x['id']}</td><td>{$x['name']}</td></tr>";
}
echo "</table>";
echo "<p>Total: " . $c->query('SELECT COUNT(*) FROM characters')->fetch_row()[0] . "</p>";
