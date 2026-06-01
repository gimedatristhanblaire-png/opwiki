<?php
$c = new mysqli('sql305.infinityfree.com', 'if0_42065833', 'yamatekudisai1', 'if0_42065833_opwiki');
$fixes = [
    "Kuzan" => "Admiral",
    "Borsalino" => "Admiral",
    "Issho" => "Admiral",
    "Buggy" => "Emperor",
];
foreach ($fixes as $name => $level) {
    $c->query("UPDATE characters SET danger_level='$level' WHERE name='$name'");
    echo "$name -> $level<br>";
}
echo "<h2>Done!</h2>";
