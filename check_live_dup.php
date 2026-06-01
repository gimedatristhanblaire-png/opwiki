<?php
$c = new mysqli('sql305.infinityfree.com', 'if0_42065833', 'yamatekudisai1', 'if0_42065833_opwiki');

echo "<h2>Duplicate Check</h2>";

// Check characters with same name
$r = $c->query("SELECT name, COUNT(*) as cnt, GROUP_CONCAT(id) as ids FROM characters GROUP BY name HAVING cnt > 1");
if ($r->num_rows > 0) {
    echo "<h3>Duplicate Characters:</h3><ul>";
    while ($x = $r->fetch_assoc()) {
        echo "<li>{$x['name']} (IDs: {$x['ids']}) - COUNT: {$x['cnt']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No duplicate character names found.</p>";
}

// Check characters with same alias
$r = $c->query("SELECT alias, COUNT(*) as cnt, GROUP_ID() as ids FROM characters GROUP BY alias HAVING cnt > 1");
if ($r->num_rows > 0) {
    echo "<h3>Duplicate Aliases:</h3><ul>";
    while ($x = $r->fetch_assoc()) {
        echo "<li>{$x['alias']} (IDs: {$x['ids']}) - COUNT: {$x['cnt']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No duplicate aliases found.</p>";
}

// Show ALL characters ordered by id
$r = $c->query("SELECT id, name, alias FROM characters ORDER BY id");
echo "<h3>All Characters:</h3><table border=1><tr><th>ID</th><th>Name</th><th>Alias</th></tr>";
while ($x = $r->fetch_assoc()) {
    echo "<tr><td>{$x['id']}</td><td>{$x['name']}</td><td>{$x['alias']}</td></tr>";
}
echo "</table>";
echo "<p>Total: " . $c->query('SELECT COUNT(*) FROM characters')->fetch_row()[0] . "</p>";

echo "<h2>FIX: Delete Duplicates</h2>";
echo "<p>If duplicates exist, <a href='fix_live_dup.php'>click here to fix</a></p>";
