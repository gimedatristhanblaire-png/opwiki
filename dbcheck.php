<?php
require_once __DIR__ . '/includes/db_connection.php';
echo "<h2>DB Check</h2>";
echo "<pre>Host: " . DB_HOST . "\nDB: " . DB_NAME . "\nUser: " . DB_USER . "\n\n";

$tables = ['characters', 'devil_fruits', 'arcs', 'wiki_articles', 'theories', 'users'];
foreach ($tables as $t) {
    $r = $conn->query("SELECT COUNT(*) as c FROM $t");
    if ($r) { $row = $r->fetch_assoc(); echo "$t: {$row['c']} rows\n"; }
    else { echo "$t: ERROR - " . $conn->error . "\n"; }
}
echo "</pre>";
?>