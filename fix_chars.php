<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db_connection.php';

echo "<pre>";

// 1. Fix Charlotte Linlin (id 16) - had Boa Hancock's data
$conn->query("UPDATE characters SET 
    alias='Big Mom',
    origin='Grand Line (Totto Land)',
    height='880 cm',
    description='Formerly one of the Four Emperors. Captain of the Big Mom Pirates and matriarch of the Charlotte family. Ate the Soru Soru no Mi.',
    haki_types='Haoshoku, Busoshoku, Kenbunshoku',
    devil_fruit='Soru Soru no Mi'
    WHERE id=16");
echo "Fixed Charlotte Linlin (id 16): " . $conn->affected_rows . " rows\n";

// 2. Fix Boa Hancock (id 26) - had Charlotte Linlin's data
$conn->query("UPDATE characters SET 
    alias='Pirate Empress',
    origin='Grand Line (Amazon Lily)',
    height='191 cm',
    description='Empress of Amazon Lily and captain of the Kuja Pirates. Former Warlord. Ate the Mero Mero no Mi. Known as the most beautiful woman in the world.',
    haki_types='Haoshoku, Busoshoku, Kenbunshoku',
    devil_fruit='Mero Mero no Mi'
    WHERE id=26");
echo "Fixed Boa Hancock (id 26): " . $conn->affected_rows . " rows\n";

// 3. Delete duplicate Kurohige (id 28)
$conn->query("DELETE FROM characters WHERE id=28");
echo "Deleted duplicate Kurohige (id 28): " . $conn->affected_rows . " rows\n";

// 4. Fill missing haki_types and devil_fruit
$conn->query("UPDATE characters SET haki_types='Kenbunshoku' WHERE id=5 AND (haki_types IS NULL OR haki_types='')");
echo "Usopp haki: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET haki_types='Busoshoku, Kenbunshoku' WHERE id=10 AND (haki_types IS NULL OR haki_types='')");
echo "Jinbe haki: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET haki_types='Haoshoku, Busoshoku, Kenbunshoku' WHERE id=11 AND (haki_types IS NULL OR haki_types='')");
echo "Roger haki: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET haki_types='Haoshoku, Busoshoku, Kenbunshoku' WHERE id=12 AND (haki_types IS NULL OR haki_types='')");
echo "Rayleigh haki: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET haki_types='Haoshoku, Busoshoku, Kenbunshoku', devil_fruit='Uo Uo no Mi, Model: Seiryu' WHERE id=15 AND (haki_types IS NULL OR haki_types='')");
echo "Kaido: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET haki_types='Busoshoku, Kenbunshoku', devil_fruit='Jiki Jiki no Mi' WHERE id=18 AND (haki_types IS NULL OR haki_types='')");
echo "Kid: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET haki_types='Busoshoku, Kenbunshoku', devil_fruit='Mera Mera no Mi' WHERE id=19 AND (haki_types IS NULL OR haki_types='')");
echo "Ace: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET haki_types='Haoshoku, Busoshoku, Kenbunshoku' WHERE id=20 AND (haki_types IS NULL OR haki_types='')");
echo "Garp haki: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET haki_types='Haoshoku, Busoshoku, Kenbunshoku', devil_fruit='Hito Hito no Mi, Model: Daibutsu' WHERE id=21 AND (haki_types IS NULL OR haki_types='')");
echo "Sengoku: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET haki_types='Haoshoku, Busoshoku, Kenbunshoku' WHERE id=22 AND (haki_types IS NULL OR haki_types='')");
echo "Oden haki: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET haki_types='Busoshoku, Kenbunshoku' WHERE id=24 AND (haki_types IS NULL OR haki_types='')");
echo "Mihawk haki: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET devil_fruit='Moku Moku no Mi' WHERE id=25 AND (devil_fruit IS NULL OR devil_fruit='')");
echo "Smoker DF: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET haki_types='Haoshoku, Busoshoku, Kenbunshoku', devil_fruit='Ito Ito no Mi' WHERE id=27 AND (haki_types IS NULL OR haki_types='')");
echo "Doflamingo: " . $conn->affected_rows . "\n";

// 5. Clear corrupted japanese_name fields (mojibake from bad SQL import)
$conn->query("UPDATE devil_fruits SET japanese_name='' WHERE japanese_name IS NOT NULL AND japanese_name != '' AND (japanese_name LIKE '%?%' OR japanese_name LIKE 'known as%')");
echo "Cleared corrupted DF japanese_name: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET japanese_name='' WHERE japanese_name IS NOT NULL AND japanese_name != '' AND japanese_name LIKE '%?%'");
echo "Cleared corrupted char japanese_name: " . $conn->affected_rows . "\n";
$conn->query("UPDATE arcs SET japanese_name='' WHERE japanese_name IS NOT NULL AND japanese_name != '' AND japanese_name LIKE '%?%'");
echo "Cleared corrupted arc japanese_name: " . $conn->affected_rows . "\n";
$conn->query("UPDATE characters SET romanji='' WHERE romanji IS NOT NULL AND romanji != '' AND romanji LIKE '%?%'");
echo "Cleared corrupted char romanji: " . $conn->affected_rows . "\n";

echo "\nDone!";
