<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db_connection.php';

echo "<pre>";

// Helper
function i($conn, $sql) {
    if ($conn->query($sql)) {
        echo "OK: " . substr($sql, 0, 60) . "...\n";
    } else {
        echo "ERR: " . $conn->error . "\n";
    }
}

// ─── CHARACTERS ───
$chars = [
    ['Sakazuki','Akainu','Marines','Fleet Admiral','North Blue (Unknown)','Chapter 397 / Episode 278','308 cm','August 16','Legendary',0,'Alive','Current Fleet Admiral of the Marines. Ate the Magu Magu no Mi. Killed Portgas D. Ace at Marineford.',397,278,'Haoshoku, Busoshoku, Kenbunshoku','Magu Magu no Mi'],
    ['Kuzan','Aokiji','Blackbeard Pirates','Former Admiral / Tenth Ship Captain','South Blue (Unknown)','Chapter 303 / Episode 227','298 cm','September 21','Legendary',0,'Alive','Former Marine Admiral. Ate the Hie Hie no Mi. Left the Marines after losing to Akainu.',303,227,'Busoshoku, Kenbunshoku','Hie Hie no Mi'],
    ['Borsalino','Kizaru','Marines','Admiral','Grand Line (Unknown)','Chapter 504 / Episode 398','302 cm','November 23','Legendary',0,'Alive','Marine Admiral. Ate the Pika Pika no Mi. Light-speed abilities.',504,398,'Busoshoku, Kenbunshoku','Pika Pika no Mi'],
    ['Issho','Fujitora','Marines','Admiral','Grand Line (Unknown)','Chapter 701 / Episode 630','270 cm','August 23','High',0,'Alive','Marine Admiral. A blind swordsman who ate the Zushi Zushi no Mi.',701,630,'Busoshoku, Kenbunshoku','Zushi Zushi no Mi'],
    ['Monkey D. Dragon','Revolutionary Dragon','Revolutionary Army','Supreme Commander','East Blue (Unknown)','Chapter 100 / Episode 52','256 cm','October 5','Legendary',0,'Alive','Supreme Commander of the Revolutionary Army. Father of Luffy. Son of Garp.',100,52,'Haoshoku, Busoshoku, Kenbunshoku',NULL],
    ['Sabo','Flame Emperor Sabo','Revolutionary Army','Chief of Staff','East Blue (Foosha Village)','Chapter 583 / Episode 494','187 cm','March 20','High',602000000,'Alive','Chief of Staff of the Revolutionary Army. Luffy and Ace sworn brother. Ate the Mera Mera no Mi.',583,494,'Busoshoku, Kenbunshoku','Mera Mera no Mi'],
    ['Crocodile','Sir Crocodile','Cross Guild','President / Warlord','Grand Line (Unknown)','Chapter 126 / Episode 76','253 cm','September 5','Warlord',1965000000,'Alive','President of Cross Guild. Former Warlord. Ate the Suna Suna no Mi.',126,76,'Busoshoku, Kenbunshoku','Suna Suna no Mi'],
    ['Buggy','Buggy the Clown','Cross Guild','Captain / Emperor','Grand Line (Unknown)','Chapter 8 / Episode 4','192 cm','August 8','Warlord',3189000000,'Alive','One of the Four Emperors. Co-leader of Cross Guild. Ate the Bara Bara no Mi.',8,4,NULL,'Bara Bara no Mi'],
    ['Bartholomew Kuma','Tyrant Kuma','Revolutionary Army','Former Warlord / Pacifista','South Blue (Unknown)','Chapter 233 / Episode 151','689 cm','February 9','Warlord',0,'Unknown','Former Warlord. Ate the Nikyu Nikyu no Mi. Turned into a Pacifista.',233,151,'Busoshoku, Kenbunshoku','Nikyu Nikyu no Mi'],
    ['Killer','Massacre Soldier Killer','Kid Pirates','First Mate','South Blue (Unknown)','Chapter 498 / Episode 392','195 cm','March 25','Moderate',200000000,'Deceased','First mate of the Kid Pirates. Masked swordsman.',498,392,NULL,NULL],
    ['Basil Hawkins','Magician Hawkins','Hawkins Pirates','Captain','North Blue (Unknown)','Chapter 498 / Episode 392','210 cm','September 9','Moderate',320000000,'Deceased','Captain of the Hawkins Pirates. Ate the Wara Wara no Mi.',498,392,'Busoshoku, Kenbunshoku','Wara Wara no Mi'],
    ['X Drake','Red Flag Drake','Drake Pirates / SWORD','Captain / Marine Captain','North Blue (Unknown)','Chapter 498 / Episode 392','233 cm','October 6','Moderate',222000000,'Alive','Captain of the Drake Pirates. Secretly Marine SWORD. Ate an Ancient Zoan.',498,392,'Busoshoku, Kenbunshoku','Ryu Ryu no Mi, Model: Allosaurus'],
    ['Capone Bege','Gangster Bege','Fire Tank Pirates','Captain','West Blue (Unknown)','Chapter 498 / Episode 392','166 cm','January 17','Moderate',350000000,'Alive','Captain of the Fire Tank Pirates. Ate the Shiro Shiro no Mi.',498,392,'Busoshoku, Kenbunshoku','Shiro Shiro no Mi'],
    ['Jewelry Bonney','Big Eater Bonney','Bonney Pirates','Captain','South Blue (Unknown)','Chapter 498 / Episode 392','174 cm','September 1','Moderate',320000000,'Alive','Captain of the Bonney Pirates. Ate the Toshi Toshi no Mi. Daughter of Kuma.',498,392,'Busoshoku, Kenbunshoku','Toshi Toshi no Mi'],
    ['Scratchmen Apoo','Roar of the Sea Apoo','On Air Pirates','Captain','Grand Line (Long Ring Long Land)','Chapter 498 / Episode 392','256 cm','March 19','Moderate',350000000,'Alive','Captain of the On Air Pirates. Ate the Oto Oto no Mi.',498,392,'Kenbunshoku','Oto Oto no Mi'],
    ['Urouge','Mad Monk Urouge','Urouge Pirates','Captain','Grand Line (Sky Island)','Chapter 498 / Episode 392','388 cm','August 1','Moderate',0,'Alive','Captain of the Urouge Pirates. Converts damage into strength.',498,392,'Busoshoku, Kenbunshoku',NULL],
    ['Enel','God Enel','None','Former God of Skypeia','Grand Line (Skypeia)','Chapter 237 / Episode 152','209 cm','May 6','Legendary',0,'Alive','Former God of Skypeia. Ate the Goro Goro no Mi. Now on the Moon.',237,152,'Kenbunshoku','Goro Goro no Mi'],
    ['Rob Lucci','Cipher Pol Lucci','World Government','CP0 Agent','Grand Line (Unknown)','Chapter 323 / Episode 230','212 cm','June 2','High',0,'Alive','CP0 agent. Ate the Neko Neko no Mi Model: Leopard.',323,230,'Busoshoku, Kenbunshoku','Neko Neko no Mi, Model: Leopard'],
    ['Yamato','Yamato of Onigashima','Kozuki Family','Guardian of Wano','Wano Country','Chapter 970 / Episode 963','263 cm','November 3','High',0,'Alive','Daughter of Kaido. Ate the Inu Inu no Mi Model: Okuchi no Makami.',970,963,'Haoshoku, Busoshoku, Kenbunshoku','Inu Inu no Mi, Model: Okuchi no Makami'],
    ['Charlotte Katakuri','Thousand Arms Katakuri','Big Mom Pirates','1st Sweet Commander','Grand Line (Totto Land)','Chapter 851 / Episode 801','509 cm','November 25','High',1057000000,'Alive','1st Sweet Commander. Ate the Mochi Mochi no Mi.',851,801,'Haoshoku, Busoshoku, Kenbunshoku','Mochi Mochi no Mi'],
    ['Marco','Phoenix Marco','Whitebeard Pirates','1st Division Commander','Grand Line (Sphinx)','Chapter 234 / Episode 151','203 cm','October 5','High',1374000000,'Alive','1st Division Commander. Ate the Tori Tori no Mi Model: Phoenix.',234,151,'Busoshoku, Kenbunshoku','Tori Tori no Mi, Model: Phoenix'],
    ['King','Wildfire King','Beast Pirates','Lead Performer (All-Star)','Grand Line (Unknown)','Chapter 925 / Episode 917','613 cm','December 1','High',1390000000,'Deceased','Lead Performer. Ate the Ryu Ryu no Mi Model: Pteranodon. Lunarian.',925,917,'Busoshoku, Kenbunshoku','Ryu Ryu no Mi, Model: Pteranodon'],
    ['Queen','Plague Queen','Beast Pirates','Lead Performer (All-Star)','Grand Line (Unknown)','Chapter 925 / Episode 917','612 cm','July 13','High',1320000000,'Deceased','Lead Performer. Ate the Ryu Ryu no Mi Model: Brachiosaurus. Scientist.',925,917,'Busoshoku, Kenbunshoku','Ryu Ryu no Mi, Model: Brachiosaurus'],
    ['Koby','Koby of the Black Cage','Marines','Marine Captain','East Blue (Unknown)','Chapter 2 / Episode 1','155 cm','May 13','Moderate',0,'Alive','Marine Captain. SWORD member. Luffy first friend. Observation Haki user.',2,1,'Kenbunshoku',NULL],
];

foreach ($chars as $c) {
    $sql = "INSERT IGNORE INTO characters (name,alias,affiliation,position,origin,first_appearance,height,birthday,danger_level,bounty,status,description,manga_debut_chapter,anime_debut_episode,haki_types,devil_fruit) VALUES (";
    foreach ($c as $v) {
        $sql .= ($v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'") . ",";
    }
    $sql = rtrim($sql, ',') . ")";
    i($conn, $sql);
}

// ─── DEVIL FRUITS ───
$dfs = [
    ['Magu Magu no Mi','Logia','Allows the user to create, control, and turn into magma. Considered the most destructive Logia.','Sakazuki (Akainu)','Unknown','SS','Seawater, Seastone, Haki',98,90,'Legendary'],
    ['Zushi Zushi no Mi','Paramecia','Allows the user to manipulate gravity. Can levitate objects, crush enemies, and summon meteors.','Issho (Fujitora)','Unknown','S','Seawater, Seastone, Haki',88,80,'Legendary'],
    ['Nikyu Nikyu no Mi','Paramecia','Allows the user to repel anything they touch with their paw pads, including pain and fatigue.','Bartholomew Kuma','Unknown','S','Seawater, Seastone',85,85,'High'],
    ['Wara Wara no Mi','Paramecia','Allows the user to create straw dolls and transfer damage through voodoo connections.','Basil Hawkins','Unknown','B','Seawater, Seastone',60,50,'Moderate'],
    ['Ryu Ryu no Mi, Model: Allosaurus','Ancient Zoan','Allows the user to transform into an allosaurus. Grants immense strength and durability.','X Drake','Unknown','A','Seawater, Seastone',75,70,'High'],
    ['Shiro Shiro no Mi','Paramecia','Allows the user to turn their body into a mobile fortress with multiple floors.','Capone Bege','Unknown','B','Seawater, Seastone',55,65,'Moderate'],
    ['Toshi Toshi no Mi','Paramecia','Allows the user to manipulate ages of themselves and others.','Jewelry Bonney','Unknown','A','Seawater, Seastone',70,80,'High'],
    ['Oto Oto no Mi','Paramecia','Allows the user to convert body parts into musical instruments and produce sound waves.','Scratchmen Apoo','Unknown','B','Seawater, Seastone, Haki',58,50,'Moderate'],
    ['Neko Neko no Mi, Model: Leopard','Zoan','Allows the user to transform into a leopard. Grants enhanced speed and strength.','Rob Lucci','Yes','A','Seawater, Seastone',78,60,'High'],
    ['Mochi Mochi no Mi','Special Paramecia','Allows the user to create and transform into mochi. Has Logia-like properties.','Charlotte Katakuri','Yes','S','Seawater, Seastone, Haki',92,80,'Legendary'],
    ['Ryu Ryu no Mi, Model: Pteranodon','Ancient Zoan','Allows the user to transform into a pteranodon. Grants flight and aerial attacks.','King','Unknown','A','Seawater, Seastone',82,75,'High'],
    ['Ryu Ryu no Mi, Model: Brachiosaurus','Ancient Zoan','Allows the user to transform into a brachiosaurus. Grants immense size and strength.','Queen','Unknown','A','Seawater, Seastone',80,70,'High'],
    ['Uo Uo no Mi, Model: Seiryu','Mythical Zoan','Allows the user to transform into a giant azure dragon. Grants flight, fire breath, and immense power.','Kaido','Yes','SSS','Seawater, Seastone, Haki',99,98,'Legendary'],
    ['Bari Bari no Mi','Paramecia','Allows the user to generate near-indestructible barriers from their hands.','Bartolomeo','Unknown','A','Seawater, Seastone',65,50,'Moderate'],
];

foreach ($dfs as $df) {
    $fields = ['name','type','description','current_holder','awakening','strength_level','weakness','combat_rating','rarity_meter','threat_level'];
    $sql = "INSERT IGNORE INTO devil_fruits (" . implode(',', $fields) . ") VALUES (";
    foreach ($df as $v) {
        $sql .= "'" . $conn->real_escape_string($v) . "',";
    }
    $sql = rtrim($sql, ',') . ")";
    i($conn, $sql);
}

echo "\nDone! Check totals above.";
