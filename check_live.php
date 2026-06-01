<?php
$c = new mysqli('sql305.infinityfree.com', 'if0_42065833', 'yamatekudisai1', 'if0_42065833_opwiki');
$r = $c->query('SELECT id, name, alias FROM characters ORDER BY id');
echo "<h2>Characters in DB</h2><table border=1><tr><th>ID</th><th>Name</th><th>Alias</th></tr>";
while ($x = $r->fetch_assoc()) {
    echo "<tr><td>{$x['id']}</td><td>{$x['name']}</td><td>{$x['alias']}</td></tr>";
}
echo "</table>";
echo "Total: " . $c->query('SELECT COUNT(*) FROM characters')->fetch_row()[0];
