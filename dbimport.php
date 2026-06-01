<?php
error_reporting(-1);
ini_set('display_errors', 1);
require_once __DIR__ . '/includes/db_connection.php';
$conn->query("SET FOREIGN_KEY_CHECKS=0");
$conn->query("SET NAMES utf8mb4");
$sql = file_get_contents(__DIR__ . '/opwiki_db_clean.sql');
if (!$sql) { die("Can't read SQL file"); }
if ($conn->multi_query($sql)) {
    echo "Import started OK<br>";
} else {
    die("Initial error: " . $conn->error);
}
$i = 0;
do {
    if ($err = $conn->error) { echo "Error at statement: $err<br>\n"; break; }
    $i++;
    if ($conn->more_results()) {
        if (!$conn->next_result()) {
            if ($err = $conn->error) { echo "Error at result $i: $err<br>\n"; break; }
        }
    }
} while ($conn->more_results());
echo "Processed $i statements<br>";
$conn->query("SET FOREIGN_KEY_CHECKS=1");
echo "Done";
?>