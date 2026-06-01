<?php
// Update DB image paths to use PNG instead of SVG
$conn = new mysqli('sql305.infinityfree.com', 'if0_42065833', 'yamatekudisai1', 'if0_42065833_opwiki');

// Characters with new PNG images (id >= 29)
$chars = [
    29 => 'sakazuki.png', 30 => 'kuzan.png', 31 => 'borsalino.png',
    32 => 'issho.png', 33 => 'monkey-d-dragon.png', 34 => 'sabo.png',
    35 => 'crocodile.png', 36 => 'buggy.png', 37 => 'bartholomew-kuma.png',
    38 => 'killer.png', 39 => 'basil-hawkins.png', 40 => 'x-drake.png',
    41 => 'capone-bege.png', 42 => 'jewelry-bonney.png', 43 => 'scratchmen-apoo.png',
    44 => 'urouge.png', 45 => 'enel.png', 46 => 'rob-lucci.png',
    47 => 'yamato.png', 48 => 'charlotte-katakuri.png', 49 => 'marco.png',
    50 => 'king.png', 51 => 'queen.png', 52 => 'koby.png',
];

echo "<h2>Updating character images...</h2>";
foreach ($chars as $id => $img) {
    $path = 'assets/images/characters/' . $img;
    $conn->query("UPDATE characters SET image='$path' WHERE id=$id");
    echo "Char $id -> $path<br>";
}

// Devil fruits with new PNG images (id >= 26)
$dfs = [
    26 => 'magu-magu-no-mi.png', 27 => 'zushi-zushi-no-mi.png',
    28 => 'nikyu-nikyu-no-mi.png', 29 => 'wara-wara-no-mi.png',
    30 => 'ryu-ryu-no-mi-model-allosaurus.png', 31 => 'shiro-shiro-no-mi.png',
    32 => 'toshi-toshi-no-mi.png', 33 => 'oto-oto-no-mi.png',
    34 => 'neko-neko-no-mi-model-leopard.png', 35 => 'mochi-mochi-no-mi.png',
    36 => 'ryu-ryu-no-mi-model-pteranodon.png', 37 => 'ryu-ryu-no-mi-model-brachiosaurus.png',
    38 => 'uo-uo-no-mi-model-seiryu.png', 39 => 'bari-bari-no-mi.png',
];

echo "<h2>Updating devil fruit images...</h2>";
foreach ($dfs as $id => $img) {
    $path = 'assets/images/devil_fruits/' . $img;
    $conn->query("UPDATE devil_fruits SET image='$path' WHERE id=$id");
    echo "DF $id -> $path<br>";
}

echo "<h2>Done!</h2>";
