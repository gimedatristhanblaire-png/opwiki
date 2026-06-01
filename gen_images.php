<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db_connection.php';

echo "<pre>";

function make_svg($name, $path, $bg = '1A3A5C', $fg = 'F5C518') {
    $parts = explode(' ', $name);
    $initials = '';
    foreach ($parts as $p) { if (!empty($p)) $initials .= strtoupper($p[0]); }
    $initials = substr($initials, 0, 3);
    
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#' . $bg . '"/>
      <stop offset="100%" style="stop-color:#' . sprintf("%02x%02x%02x", min(255,hexdec(substr($bg,0,2))+40), min(255,hexdec(substr($bg,2,2))+40), min(255,hexdec(substr($bg,4,2))+40)) . '"/>
    </linearGradient>
  </defs>
  <rect width="400" height="400" rx="30" fill="url(#bg)"/>
  <text x="200" y="200" text-anchor="middle" dominant-baseline="central" font-family="Arial,Impact,sans-serif" font-weight="700" font-size="120px" fill="#' . $fg . '">' . htmlspecialchars($initials) . '</text>
  <text x="200" y="340" text-anchor="middle" font-family="Arial,sans-serif" font-size="24px" fill="rgba(255,255,255,0.6)">' . htmlspecialchars($name) . '</text>
</svg>';
    
    file_put_contents($path, $svg);
}

$dir = __DIR__ . '/assets/images/characters/';

// Colors for each character (pairs of bg,fg)
$colors = [
    'Sakazuki' => ['C62828','FFCDD2'],
    'Kuzan' => ['1565C0','BBDEFB'],
    'Borsalino' => ['F9A825','FFF9C4'],
    'Issho' => ['4A148C','CE93D8'],
    'Monkey D. Dragon' => ['1B5E20','C8E6C9'],
    'Sabo' => ['BF360C','FFCCBC'],
    'Crocodile' => ['4E342E','D7CCC8'],
    'Buggy' => ['E91E63','F8BBD0'],
    'Bartholomew Kuma' => ['37474F','CFD8DC'],
    'Killer' => ['795548','D7CCC8'],
    'Basil Hawkins' => ['455A64','CFD8DC'],
    'X Drake' => ['006064','B2EBF2'],
    'Capone Bege' => ['3E2723','D7CCC8'],
    'Jewelry Bonney' => ['AD1457','F48FB1'],
    'Scratchmen Apoo' => ['00695C','B2DFDB'],
    'Urouge' => ['4A148C','CE93D8'],
    'Enel' => ['FFF176','F57F17'],
    'Rob Lucci' => ['212121','BDBDBD'],
    'Yamato' => ['1A237E','C5CAE9'],
    'Charlotte Katakuri' => ['880E4F','F48FB1'],
    'Marco' => ['1B5E20','A5D6A7'],
    'King' => ['1A1A2E','E0E0E0'],
    'Queen' => ['BF360C','FFAB91'],
    'Koby' => ['0D47A1','BBDEFB'],
];

$r = $conn->query("SELECT id, name FROM characters WHERE id >= 29 AND id <= 52 ORDER BY id");
while ($row = $r->fetch_assoc()) {
    $id = $row['id'];
    $name = $row['name'];
    $slug = strtolower(str_replace(' ', '-', $name));
    $slug = str_replace(['(',')',',','.'], '', $slug);
    $slug = str_replace(['--'], '-', $slug);
    $filename = $slug . '.svg';
    $path = $dir . $filename;
    
    $bg = isset($colors[$name]) ? $colors[$name][0] : sprintf("%06x", mt_rand(0, 0xFFFFFF));
    $fg = isset($colors[$name]) ? $colors[$name][1] : 'FFFFFF';
    
    make_svg($name, $path, $bg, $fg);
    
    $rel_path = 'assets/images/characters/' . $filename;
    $conn->query("UPDATE characters SET image='$rel_path' WHERE id=$id");
    echo "Created: $filename (id=$id)\n";
}

// Devil fruits
$df_dir = __DIR__ . '/assets/images/devil_fruits/';
$df_colors = [
    'Magu Magu no Mi' => ['C62828','FFCDD2'],
    'Zushi Zushi no Mi' => ['4A148C','CE93D8'],
    'Nikyu Nikyu no Mi' => ['37474F','FFAB91'],
    'Wara Wara no Mi' => ['F9A825','FFF9C4'],
    'Ryu Ryu no Mi, Model: Allosaurus' => ['2E7D32','A5D6A7'],
    'Shiro Shiro no Mi' => ['3E2723','D7CCC8'],
    'Toshi Toshi no Mi' => ['AD1457','F48FB1'],
    'Oto Oto no Mi' => ['00695C','B2DFDB'],
    'Neko Neko no Mi, Model: Leopard' => ['212121','BDBDBD'],
    'Mochi Mochi no Mi' => ['880E4F','F48FB1'],
    'Ryu Ryu no Mi, Model: Pteranodon' => ['1A1A2E','E0E0E0'],
    'Ryu Ryu no Mi, Model: Brachiosaurus' => ['BF360C','FFAB91'],
    'Uo Uo no Mi, Model: Seiryu' => ['0D47A1','BBDEFB'],
    'Bari Bari no Mi' => ['1565C0','81D4FA'],
];

$df_r = $conn->query("SELECT id, name FROM devil_fruits WHERE id >= 26 AND id <= 39 ORDER BY id");
while ($row = $df_r->fetch_assoc()) {
    $id = $row['id'];
    $name = $row['name'];
    $slug = strtolower(str_replace([' ', ','], '-', $name));
    $slug = str_replace(['--'], '-', $slug);
    $slug = str_replace(['.', ':'], '', $slug);
    $filename = $slug . '.svg';
    $path = $df_dir . $filename;
    
    $bg = isset($df_colors[$name]) ? $df_colors[$name][0] : sprintf("%06x", mt_rand(0, 0xFFFFFF));
    $fg = isset($df_colors[$name]) ? $df_colors[$name][1] : 'FFFFFF';
    
    make_svg($name, $path, $bg, $fg);
    
    echo "Created DF: $filename (id=$id)\n";
}

echo "\nDone!";
