<?php
error_reporting(-1);
ini_set('display_errors', 1);
require_once __DIR__ . '/includes/db_connection.php';
echo "<h2>DB Check v2</h2>\n<pre>\n";
echo "Host: " . DB_HOST . "\nDB: " . DB_NAME . "\n\n";

// Check if connection works
if (!$conn || $conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected OK\n\n";

// List tables
$tables_r = $conn->query("SHOW TABLES");
if ($tables_r) {
    echo "Tables in " . DB_NAME . ":\n";
    $table_count = 0;
    while ($row = $tables_r->fetch_array()) {
        echo "  - " . $row[0] . "\n";
        $table_count++;
    }
    echo "Total: $table_count tables\n\n";
} else {
    echo "SHOW TABLES failed: " . $conn->error . "\n\n";
}

// Check key tables
$key_tables = ['characters', 'devil_fruits', 'arcs', 'wiki_articles', 'theories', 'users'];
foreach ($key_tables as $t) {
    $r = $conn->query("SELECT COUNT(*) as c FROM `$t`");
    if ($r) {
        $row = $r->fetch_assoc();
        echo "$t: {$row['c']} rows\n";
    } else {
        echo "$t: ERROR - " . $conn->error . "\n";
    }
}

echo "\nSample characters:\n";
$r = $conn->query("SELECT name, affiliation FROM characters LIMIT 5");
if ($r) { while ($row = $r->fetch_assoc()) { echo "  {$row['name']} ({$row['affiliation']})\n"; } }
else { echo "Query failed: " . $conn->error; }

echo "\n</pre>";
?>