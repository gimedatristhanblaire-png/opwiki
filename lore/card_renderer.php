<?php
// Lore Card Renderer v2 — complete wanted poster card system

if (!function_exists('lore_avatar_color')) {
    function lore_avatar_color($name) {
        $colors = ['#1A3A5C','#2E7D32','#C62828','#6A1B9A','#E65100','#00838F','#37474F','#4E342E','#1565C0','#827717','#4A148C','#B71C1C','#1B5E20','#01579B','#3E2723','#880E4F','#004D40','#263238','#D84315','#33691E','#0D47A1','#F57F17','#AD1457','#00695C','#5D4037','#4527A0','#BF360C'];
        return $colors[crc32($name) % count($colors)];
    }
}
if (!function_exists('lore_initials')) {
    function lore_initials($name) {
        $parts = explode(' ', $name);
        $initials = '';
        foreach ($parts as $p) { if (!empty($p)) $initials .= strtoupper($p[0]); }
        return substr($initials, 0, 3);
    }
}

function render_lore_card($item, $type, $conn = null) {
    if (!$item) return '';
    switch ($type) {
        case 'characters': return render_character_card($item, $conn);
        case 'devil_fruits': return render_df_card($item, $conn);
        case 'arcs': return render_arc_card($item);
        case 'timeline': return render_timeline_card($item, $conn);
        default: return render_fallback_card($item);
    }
}

// ─── Meter renderer ───
function render_meter($label, $value, $max = 100, $color = '#D4A843', $icon = '') {
    $pct = min(100, max(0, ($value / $max) * 100));
    return '<div class="lore-meter"><span class="lore-meter-label">' . $icon . ' ' . htmlspecialchars($label) . '</span>
      <div class="lore-meter-bar"><div class="lore-meter-fill" style="width:' . $pct . '%;background:' . $color . ';"></div></div>
      <span class="lore-meter-value">' . $value . '</span></div>';
}

function render_danger_meter($level) {
    $map = ['Emperor'=>['🔥',95,'#C62828'],'Legendary'=>['⚡',85,'#E65100'],'Warlord'=>['⚔️',70,'#D4A843'],'Admiral'=>['⚓',75,'#1A237E'],'High'=>['⚠️',55,'#F57F17'],'Moderate'=>['●',35,'#2E7D32'],'Low'=>['○',15,'#00838F'],'Pet'=>['🐾',5,'#90CAF9'],'Minimal'=>['○',5,'#90CAF9'],'Harmless'=>['○',5,'#81C784'],'Normal'=>['●',20,'#78909C'],'Critical'=>['☠️',90,'#6A1B9A'],'Catastrophic'=>['💀',98,'#B71C1C'],'Dangerous'=>['⚡',75,'#D84315']];
    $m = $map[$level] ?? ['●',20,'#78909C'];
    return render_meter('Danger Level', $m[1], 100, $m[2], $m[0]);
}

function render_strength_meter($level) {
    $map = ['SSS'=>['👑',99,'#FFD700'],'SS'=>['💀',90,'#C62828'],'S'=>['🔥',80,'#E65100'],'A'=>['⚡',65,'#D4A843'],'B'=>['●',45,'#2E7D32'],'C'=>['○',25,'#00838F'],'D'=>['○',10,'#90CAF9']];
    $m = $map[$level] ?? ['○',10,'#90CAF9'];
    return render_meter('Power', $m[1], 100, $m[2], $m[0]);
}

// ─── CHARACTER CARD ───
function render_character_card($c, $conn) {
    $name = htmlspecialchars($c['name']);
    $alias = htmlspecialchars($c['alias'] ?? '');
    $jname = htmlspecialchars($c['japanese_name'] ?? '');
    $romanji = htmlspecialchars($c['romanji'] ?? '');
    $affil = htmlspecialchars($c['affiliation'] ?? '');
    $position = htmlspecialchars($c['position'] ?? '');
    $origin = htmlspecialchars($c['origin'] ?? '');
    $first_app = htmlspecialchars($c['first_appearance'] ?? '');
    $height = htmlspecialchars($c['height'] ?? '');
    $birthday = htmlspecialchars($c['birthday'] ?? '');
    $danger = htmlspecialchars($c['danger_level'] ?? 'Normal');
    $bounty = (int)($c['bounty'] ?? 0);
    $status = htmlspecialchars($c['status'] ?? 'Unknown');
    $haki = htmlspecialchars($c['haki_types'] ?? '');
    $df = htmlspecialchars($c['devil_fruit'] ?? '');
    if (empty($df) && !empty($c['devil_fruit_id']) && $conn) {
        $r = $conn->query("SELECT name FROM devil_fruits WHERE id=" . (int)$c['devil_fruit_id']);
        if ($r && $r->num_rows) $df = htmlspecialchars($r->fetch_assoc()['name']);
    }
    $debut = htmlspecialchars($c['debut_arc'] ?? '');
    if (empty($debut) && !empty($c['debut_arc_id']) && $conn) {
        $r = $conn->query("SELECT name FROM arcs WHERE id=" . (int)$c['debut_arc_id']);
        if ($r && $r->num_rows) $debut = htmlspecialchars($r->fetch_assoc()['name']);
    }
    $desc = htmlspecialchars($c['description'] ?? '');

    $raw = $c['image'] ?? '';
    $img = !empty($raw) ? ((strpos($raw, 'http') === 0 || strpos($raw, '/') === 0) ? htmlspecialchars($raw) : BASE_URL . htmlspecialchars($raw)) : '';

    $status_icon = '●'; $status_class = 'status-alive';
    if (stripos($status, 'dead') !== false || stripos($status, 'deceased') !== false) { $status_icon = '✕'; $status_class = 'status-dead'; }
    elseif (stripos($status, 'unknown') !== false) { $status_icon = '?'; $status_class = 'status-unknown'; }

    $bounty_formatted = $bounty > 0 ? '฿ ' . number_format($bounty) : '—';
    $bounty_class = $bounty >= 1000000000 ? 'bounty-legendary' : ($bounty >= 100000000 ? 'bounty-high' : 'bounty-normal');

    $h = '<div class="lore-card-v2 lore-card-character">';
    $h .= '<div class="wanted-poster-header">
              <div class="wanted-stamp">WANTED</div>
              <div class="wanted-sub">DEAD OR ALIVE</div>
              <div class="wanted-marine-seal">⚓</div>
           </div>';
    if ($danger === 'Emperor') $h .= '<div class="wanted-emperor-badge">EMPEROR</div>';
    elseif ($danger === 'Legendary') $h .= '<div class="wanted-legendary-badge">LEGENDARY</div>';
    elseif ($danger === 'Warlord') $h .= '<div class="wanted-warlord-badge">WARLORD</div>';
    elseif ($danger === 'Admiral') $h .= '<div class="wanted-admiral-badge">ADMIRAL</div>';
    $h .= '<div class="wanted-image">
              <div class="wanted-image-frame">
                  <img src="' . $img . '" alt="' . $name . '" loading="lazy">
              </div>
           </div>';
    $h .= '<div class="wanted-name">' . $name . '</div>';
    if ($alias) $h .= '<div class="wanted-alias">"' . $alias . '"</div>';
    if ($position) $h .= '<div class="wanted-position">' . $position . '</div>';
    if ($affil) $h .= '<div class="wanted-affil">' . $affil . '</div>';
    $h .= '<div class="wanted-bounty-row ' . $bounty_class . '">
              <span class="wanted-bounty-label">BOUNTY</span>
              <span class="wanted-bounty-value bounty-shine">' . $bounty_formatted . '</span>
           </div>';
    $h .= '<div class="wanted-status ' . $status_class . '"><span class="status-dot">' . $status_icon . '</span> ' . $status . '</div>';

    // Expandable body
    $h .= '<div class="lore-card-body-v2">';
    if ($df) $h .= '<div class="lore-field"><span class="lore-field-label">🍎 Devil Fruit</span><span>' . $df . '</span></div>';
    if ($haki) $h .= '<div class="lore-field"><span class="lore-field-label">⚡ Haki</span><span>' . $haki . '</span></div>';
    if ($origin) $h .= '<div class="lore-field"><span class="lore-field-label">🌍 Origin</span><span>' . $origin . '</span></div>';
    if ($first_app) $h .= '<div class="lore-field"><span class="lore-field-label">📖 First Appearance</span><span>' . $first_app . '</span></div>';
    if ($height) $h .= '<div class="lore-field"><span class="lore-field-label">📏 Height</span><span>' . $height . '</span></div>';
    if ($birthday) $h .= '<div class="lore-field"><span class="lore-field-label">🎂 Birthday</span><span>' . $birthday . '</span></div>';
    if ($debut) $h .= '<div class="lore-field"><span class="lore-field-label">📜 Debut Arc</span><span>' . $debut . '</span></div>';
    if ($jname) $h .= '<div class="lore-field"><span class="lore-field-label">🗾 Japanese</span><span>' . $jname . ($romanji ? ' (' . $romanji . ')' : '') . '</span></div>';
    // Danger meter
    $h .= '<div class="lore-meter-section">' . render_danger_meter($danger) . '</div>';
    if ($desc) $h .= '<div class="lore-field lore-field-full"><span class="lore-field-label">📜 Description</span><p>' . $desc . '</p></div>';
    $h .= '</div>';

    // Actions
    $h .= '<div class="lore-card-actions">
              <button class="lore-card-expand-v2" onclick="this.closest(\'.lore-card-v2\').classList.toggle(\'expanded\')">📜 View Details</button>
              <div class="lore-action-row">
                  <a href="' . BASE_URL . 'lore/view.php?type=characters&id=' . $c['id'] . '" class="lore-action-btn lore-action-view">View Full Report →</a>
                  <button class="lore-action-btn lore-action-bookmark" onclick="showToast(\'Bookmarked!\')">🔖</button>
                  <button class="lore-action-btn lore-action-share" onclick="navigator.clipboard.writeText(window.location.origin+\'' . BASE_URL . 'lore/view.php?type=characters&id=' . $c['id'] . '\').then(()=>showToast(\'Link copied!\'))">📤</button>
              </div>
           </div>';
    $h .= '</div>';
    return $h;
}

// ─── DEVIL FRUIT CARD ───
function render_df_card($df, $conn = null) {
    $name = htmlspecialchars($df['name']);
    $jname = htmlspecialchars($df['japanese_name'] ?? '');
    $type = htmlspecialchars($df['type'] ?? 'Unknown');
    $holder = htmlspecialchars($df['current_holder'] ?? '');
    if (empty($holder) && !empty($df['current_holder_id']) && $conn) {
        $r = $conn->query("SELECT name FROM characters WHERE id=" . (int)$df['current_holder_id']);
        if ($r && $r->num_rows) $holder = htmlspecialchars($r->fetch_assoc()['name']);
    }
    $awakening = htmlspecialchars($df['awakening'] ?? 'Unknown');
    $debut_ch = htmlspecialchars($df['debut_chapter'] ?? '');
    $strength = htmlspecialchars($df['strength_level'] ?? 'C');
    $weakness = htmlspecialchars($df['weakness'] ?? '');
    $combat = (int)($df['combat_rating'] ?? 0);
    $rarity = (int)($df['rarity_meter'] ?? 0);
    $threat = htmlspecialchars($df['threat_level'] ?? 'Low');
    $desc = htmlspecialchars($df['description'] ?? '');

    $raw = $df['image'] ?? '';
    $img = !empty($raw) ? ((strpos($raw, 'http') === 0 || strpos($raw, '/') === 0) ? htmlspecialchars($raw) : BASE_URL . htmlspecialchars($raw)) : '';

    $type_class = 'df-type-' . strtolower(str_replace(' ', '-', $type));
    $awake_icon = $awakening === 'Yes' ? '✅ Awakened' : ($awakening === 'Unknown' ? '❓ Unknown' : '❌ Not Awakened');

    $rarity_colors = [0=>'#90CAF9',25=>'#2E7D32',50=>'#D4A843',75=>'#E65100',90=>'#C62828'];
    $rcolor = '#90CAF9';
    foreach ($rarity_colors as $t=>$c) { if ($rarity >= $t) $rcolor = $c; }

    $h = '<div class="lore-card-v2 lore-card-df">';
    $h .= '<div class="df-header">
              <div class="df-archive-badge">DEVIL FRUIT ARCHIVE</div>
              <div class="df-type-badge ' . $type_class . '">' . $type . '</div>
              <div class="df-rarity-glow" style="box-shadow:0 0 20px ' . $rcolor . '88, inset 0 0 30px ' . $rcolor . '44;"></div>
           </div>';
    $h .= '<div class="df-image">
              <div class="df-image-frame" style="border-color:' . $rcolor . ';">
                  <img src="' . $img . '" alt="' . $name . '" loading="lazy">
              </div>
           </div>';
    $h .= '<div class="df-name">' . $name . '</div>';
    if ($jname) $h .= '<div class="df-jname">' . $jname . '</div>';
    if ($holder) $h .= '<div class="df-holder">⚔️ ' . $holder . '</div>';

    $h .= '<div class="lore-card-body-v2">';
    if ($debut_ch) $h .= '<div class="lore-field"><span class="lore-field-label">📖 Debut</span><span>' . $debut_ch . '</span></div>';
    if ($weakness) $h .= '<div class="lore-field"><span class="lore-field-label">⚠️ Weakness</span><span>' . $weakness . '</span></div>';
    $h .= '<div class="lore-field"><span class="lore-field-label">🌀 Awakening</span><span>' . $awake_icon . '</span></div>';
    $h .= '<div class="lore-meter-section">';
    $h .= render_strength_meter($strength);
    $h .= render_meter('Combat Rating', $combat, 100, '#C62828', '⚔️');
    $h .= render_meter('Rarity', $rarity, 100, '#D4A843', '💎');
    $h .= '</div>';
    if ($desc) $h .= '<div class="lore-field lore-field-full"><span class="lore-field-label">📜 Description</span><p>' . $desc . '</p></div>';
    $h .= '</div>';

    $h .= '<div class="lore-card-actions">
              <button class="lore-card-expand-v2" onclick="this.closest(\'.lore-card-v2\').classList.toggle(\'expanded\')">📜 Read More</button>
              <div class="lore-action-row">
                  <a href="' . BASE_URL . 'lore/view.php?type=devil_fruits&id=' . $df['id'] . '" class="lore-action-btn lore-action-view">View Full Report →</a>
                  <button class="lore-action-btn lore-action-bookmark" onclick="showToast(\'Bookmarked!\')">🔖</button>
                  <button class="lore-action-btn lore-action-share" onclick="navigator.clipboard.writeText(window.location.origin+\'' . BASE_URL . 'lore/view.php?type=devil_fruits&id=' . $df['id'] . '\').then(()=>showToast(\'Link copied!\'))">📤</button>
              </div>
           </div>';
    $h .= '</div>';
    return $h;
}

// ─── ARC CARD ───
function render_arc_card($arc) {
    $name = htmlspecialchars($arc['name']);
    $jname = htmlspecialchars($arc['japanese_name'] ?? '');
    $saga = htmlspecialchars($arc['saga'] ?? '');
    $ch = htmlspecialchars($arc['chapters'] ?? '');
    $ep = htmlspecialchars($arc['episodes'] ?? '');
    $loc = htmlspecialchars($arc['location'] ?? '');
    $tpos = htmlspecialchars($arc['timeline_position'] ?? '');
    $villains = htmlspecialchars($arc['key_villains'] ?? '');
    $events = htmlspecialchars($arc['major_events'] ?? '');
    $deaths = htmlspecialchars($arc['major_deaths'] ?? '');
    $tragedy = (int)($arc['tragedy_meter'] ?? 0);
    $hype = (int)($arc['hype_rating'] ?? 0);
    $lore_imp = htmlspecialchars($arc['lore_importance'] ?? 'Normal');
    $desc = htmlspecialchars($arc['description'] ?? '');
    $num = (int)($arc['arc_number'] ?? 0);

    $saga_colors = ['East Blue'=>'#4A90D9','Alabasta'=>'#E6A817','Sky Island'=>'#90CAF9','Water 7'=>'#1565C0','Thriller Bark'=>'#4A148C','Summit War'=>'#C62828','Fish-Man Island'=>'#00838F','Dressrosa'=>'#E65100','Four Emperors'=>'#2E7D32','Final Saga'=>'#000000'];
    $saga_color = $saga_colors[$saga] ?? '#37474F';
    $acolor = lore_avatar_color($name);
    $raw = $arc['image'] ?? '';
    $img = '';
    if (!empty($raw)) {
        $img = (strpos($raw, 'http') === 0 || strpos($raw, '/') === 0) ? htmlspecialchars($raw) : BASE_URL . htmlspecialchars($raw);
    }

    $imp_badges = ['Legendary'=>'🔥','Critical'=>'⚡','Admiral'=>'⚓','High'=>'●','Normal'=>'○'];
    $imp_icon = $imp_badges[$lore_imp] ?? '○';

    $h = '<div class="lore-card-v2 lore-card-arc">';
    $h .= '<div class="arc-top" style="background:linear-gradient(135deg, ' . $saga_color . '33, transparent);">';
    if ($img) $h .= '<div class="arc-banner"><img src="' . $img . '" alt="' . $name . '" loading="lazy"></div>';
    $h .= '<div class="arc-saga-badge" style="background:' . $saga_color . ';">' . htmlspecialchars(explode(' ', $saga)[0]) . '</div>';
    $h .= '<div class="arc-archive-label">STORY ARC</div>';
    $h .= '</div>';
    $h .= '<div class="arc-content">';
    $h .= '<div class="arc-number">#' . $num . '</div>';
    $h .= '<div class="arc-name">' . $name . '</div>';
    if ($jname) $h .= '<div class="arc-jname">' . $jname . '</div>';
    if ($loc) $h .= '<div class="arc-location">📍 ' . $loc . '</div>';
    if ($ch || $ep) $h .= '<div class="arc-stats"><span>📖 ' . $ch . '</span><span>🎬 ' . $ep . '</span></div>';
    if ($tpos) $h .= '<div class="arc-timeline-pos">⏳ ' . $tpos . '</div>';
    $h .= '</div>';

    $h .= '<div class="lore-card-body-v2">';
    if ($villains) $h .= '<div class="lore-field"><span class="lore-field-label">👹 Key Villains</span><span>' . $villains . '</span></div>';
    if ($deaths) $h .= '<div class="lore-field"><span class="lore-field-label">💀 Major Deaths</span><span>' . $deaths . '</span></div>';
    if ($events) $h .= '<div class="lore-field"><span class="lore-field-label">⚡ Major Events</span><span>' . $events . '</span></div>';
    $h .= '<div class="lore-field"><span class="lore-field-label">🏆 Lore Importance</span><span>' . $imp_icon . ' ' . $lore_imp . '</span></div>';
    $h .= '<div class="lore-meter-section">';
    $h .= render_meter('Hype Rating', $hype, 100, '#E65100', '🔥');
    $h .= render_meter('Tragedy Level', $tragedy, 100, '#4A148C', '💔');
    $h .= '</div>';
    if ($desc) $h .= '<div class="lore-field lore-field-full"><span class="lore-field-label">📜 Overview</span><p>' . $desc . '</p></div>';
    $h .= '</div>';

    $h .= '<div class="lore-card-actions">
              <button class="lore-card-expand-v2" onclick="this.closest(\'.lore-card-v2\').classList.toggle(\'expanded\')">📜 Arc Details</button>
              <div class="lore-action-row">
                  <a href="' . BASE_URL . 'lore/view.php?type=arcs&id=' . $arc['id'] . '" class="lore-action-btn lore-action-view">View Full Report →</a>
                  <button class="lore-action-btn lore-action-bookmark" onclick="showToast(\'Bookmarked!\')">🔖</button>
                  <button class="lore-action-btn lore-action-share" onclick="navigator.clipboard.writeText(window.location.origin+\'' . BASE_URL . 'lore/view.php?type=arcs&id=' . $arc['id'] . '\').then(()=>showToast(\'Link copied!\'))">📤</button>
              </div>
           </div>';
    $h .= '</div>';
    return $h;
}

// ─── NEWSPAPER TIMELINE CARD (Morgans Edition) ───
function render_timeline_card($tl, $conn = null) {
    $title = htmlspecialchars($tl['title']);
    $date = htmlspecialchars($tl['event_date'] ?? '');
    $desc = htmlspecialchars($tl['description'] ?? '');
    $importance = htmlspecialchars($tl['importance'] ?? 'Normal');
    $participants = htmlspecialchars($tl['participants'] ?? '');
    $canon = htmlspecialchars($tl['canon_status'] ?? 'Canon');
    $id = (int)($tl['id'] ?? 0);

    $arc_name = '';
    if (!empty($tl['arc_id']) && $conn) {
        $ar = $conn->query("SELECT name FROM arcs WHERE id=" . (int)$tl['arc_id']);
        if ($ar && $ar->num_rows) { $a = $ar->fetch_assoc(); $arc_name = htmlspecialchars($a['name']); }
    }

    $year = $date;
    if (preg_match('/(\d{4})/', $date, $m)) $year = (int)$m[1];
    else $year = 0;

    $imp_class = 'imp-' . strtolower($importance);
    $is_major = strtolower($importance) === 'major';

    $h = '<div class="newspaper-card" id="np-' . $id . '">';
    $h .= '<div class="newspaper-card-inner">';
    if ($is_major) $h .= '<div class="newspaper-extra-badge">📰 EXTRA!</div>';
    $h .= '<div class="newspaper-stamp">BIG NEWS!</div>';
    $h .= '<div class="newspaper-archive">WORLD TIMELINE ARCHIVE #' . str_pad($id, 3, '0', STR_PAD_LEFT) . '</div>';
    if ($year) $h .= '<div class="newspaper-year numeral-lg">' . $year . '</div>';
    $h .= '<div class="newspaper-type-badge ' . $imp_class . '">' . ($is_major ? '🔥 MAJOR INCIDENT' : '📌 HISTORICAL EVENT') . '</div>';
    $h .= '<h3 class="newspaper-title">' . $title . '</h3>';
    $h .= '<div class="newspaper-date">' . $date . '</div>';
    if ($arc_name) $h .= '<div class="newspaper-arc">🌊 Related Arc: <strong>' . $arc_name . '</strong></div>';
    if ($participants) $h .= '<div class="newspaper-participants">👥 ' . $participants . '</div>';
    $h .= '<div class="newspaper-divider">─ ─ ─ ─ ─ ─ ─ ─ ─</div>';
    if ($desc) $h .= '<div class="newspaper-body"><span class="newspaper-dropcap">' . mb_substr($desc, 0, 1) . '</span>' . htmlspecialchars(substr($desc, 1)) . '</div>';
    $h .= '<div class="newspaper-confidential stamp-rotate">CONFIDENTIAL</div>';

    // Related articles & theories
    if ($conn && $desc) {
        $rel_arts = [];
        $ra = $conn->query("SELECT id, title, slug FROM wiki_articles WHERE status='approved' AND content LIKE '%" . $conn->real_escape_string(substr($desc, 0, 60)) . "%' LIMIT 3");
        if ($ra) while ($row = $ra->fetch_assoc()) $rel_arts[] = $row;
        $rel_theories = [];
        $rt = $conn->query("SELECT id, title, slug FROM theories WHERE status='approved' AND (content LIKE '%" . $conn->real_escape_string(substr($title, 0, 60)) . "%') LIMIT 3");
        if ($rt) while ($row = $rt->fetch_assoc()) $rel_theories[] = $row;

        if (!empty($rel_arts) || !empty($rel_theories)) {
            $h .= '<div class="newspaper-related">';
            if (!empty($rel_arts)) {
                $h .= '<div class="newspaper-related-section"><span class="newspaper-related-label">📰 Related Reports</span>';
                foreach ($rel_arts as $a) $h .= '<a href="' . BASE_URL . 'wiki/view.php?slug=' . urlencode($a['slug']) . '" class="newspaper-related-link">' . htmlspecialchars($a['title']) . '</a>';
                $h .= '</div>';
            }
            if (!empty($rel_theories)) {
                $h .= '<div class="newspaper-related-section"><span class="newspaper-related-label">🔮 Conspiracy Theories</span>';
                foreach ($rel_theories as $t) $h .= '<a href="' . BASE_URL . 'theories/view.php?slug=' . urlencode($t['slug']) . '" class="newspaper-related-link">' . htmlspecialchars($t['title']) . '</a>';
                $h .= '</div>';
            }
            $h .= '</div>';
        }
    }

    $h .= '<div class="newspaper-footer"><a href="' . BASE_URL . 'lore/view.php?type=timeline&id=' . $id . '" class="newspaper-read-more">READ FULL REPORT →</a></div>';
    $h .= '</div></div>';
    return $h;
}

// ─── BREAKING NEWS CARD (top-of-page hero) ───
function render_breaking_news_card($tl, $conn = null) {
    $title = htmlspecialchars($tl['title']);
    $date = htmlspecialchars($tl['event_date'] ?? '');
    $desc = htmlspecialchars($tl['description'] ?? '');
    $participants = htmlspecialchars($tl['participants'] ?? '');
    $id = (int)($tl['id'] ?? 0);

    $arc_name = '';
    if (!empty($tl['arc_id']) && $conn) {
        $ar = $conn->query("SELECT name FROM arcs WHERE id=" . (int)$tl['arc_id']);
        if ($ar && $ar->num_rows) { $a = $ar->fetch_assoc(); $arc_name = htmlspecialchars($a['name']); }
    }

    $year = $date;
    if (preg_match('/(\d{4})/', $date, $m)) $year = (int)$m[1];
    else $year = 0;

    $h = '<div class="breaking-news-card">';
    $h .= '<div class="breaking-news-banner">🔥 BREAKING NEWS</div>';
    $h .= '<div class="breaking-news-content">';
    $h .= '<div class="breaking-extra-badge">📰 EXTRA! EXTRA!</div>';
    if ($year) $h .= '<div class="breaking-news-year">' . $year . '</div>';
    $h .= '<h2 class="breaking-news-title">' . $title . '</h2>';
    $h .= '<div class="breaking-news-date">' . $date . '</div>';
    if ($arc_name) $h .= '<div class="breaking-news-arc">STORY ARC: <strong>' . $arc_name . '</strong></div>';
    if ($participants) $h .= '<div class="breaking-news-participants">⚔️ ' . $participants . '</div>';
    $h .= '<div class="breaking-news-body">' . $desc . '</div>';
    $h .= '<div class="breaking-news-footer"><a href="' . BASE_URL . 'lore/view.php?type=timeline&id=' . $id . '" class="breaking-news-btn">READ FULL ANALYSIS →</a></div>';
    $h .= '</div></div>';
    return $h;
}

// ─── CONTROVERSY METER ───
function render_controversy_meter($score) {
    $score = max(0, min(100, (int)$score));
    $pct = $score;
    $label = $score >= 75 ? '☠️ HEATED WAR' : ($score >= 45 ? '🔥 WARM DEBATE' : '❄️ COLD FACTS');
    $color = $score >= 75 ? '#C62828' : ($score >= 45 ? '#E65100' : '#2E7D32');
    $bg = $score >= 75 ? 'rgba(198,40,40,0.1)' : ($score >= 45 ? 'rgba(230,81,0,0.1)' : 'rgba(46,125,50,0.1)');
    $grad = $score >= 75 ? 'linear-gradient(90deg, #E65100, #C62828)' : ($score >= 45 ? 'linear-gradient(90deg, #F57F17, #E65100)' : 'linear-gradient(90deg, #2E7D32, #F57F17)');
    return '<div class="controversy-meter" style="background:' . $bg . ';border-left:3px solid ' . $color . ';">
        <div class="controversy-meter-header">
            <span class="controversy-meter-icon">⚔️</span>
            <span class="controversy-meter-label">CONTROVERSY METER</span>
            <span class="controversy-meter-score" style="color:' . $color . ';">' . $score . '%</span>
        </div>
        <div class="controversy-meter-bar">
            <div class="controversy-meter-fill" style="width:' . $pct . '%;background:' . $grad . ';"></div>
        </div>
        <div class="controversy-meter-status">' . $label . '</div>
    </div>';
}

// ─── COMMUNITY THEORY BADGE ───
function render_theory_badge($vote_score, $status, $has_admin_edit = false) {
    $h = '<div class="theory-badges-row">';
    $badges = [];
    if ($vote_score > 20) $badges[] = '<span class="theory-badge badge-peak" title="Highly voted theory">🔥 Peak Fiction</span>';
    elseif ($vote_score > 5) $badges[] = '<span class="theory-badge badge-possible" title="Gaining traction">⚡ Possible</span>';
    if ($vote_score < 0) $badges[] = '<span class="theory-badge badge-crackpot" title="Controversial">❄️ Crackpot</span>';
    if ($status === 'approved' && $has_admin_edit) $badges[] = '<span class="theory-badge badge-oda" title="Admin approved">👨‍🍳 ODA COOKED?</span>';
    if (empty($badges)) $badges[] = '<span class="theory-badge badge-new">🆕 New</span>';
    $h .= implode(' ', $badges) . '</div>';
    return $h;
}

function render_fallback_card($item) {
    return '<div class="lore-card-v2"><p class="lore-empty">Unknown lore type</p></div>';
}

// ─── Filter Tabs ───
function render_filter_tabs($active_type) {
    $tabs = ['characters'=>['🏴‍☠️','Characters'],'devil_fruits'=>['🍎','Devil Fruits'],'arcs'=>['🌊','Story Arcs'],'timeline'=>['⏳','Timeline']];
    $h = '<div class="lore-filter-tabs">';
    foreach ($tabs as $key => $info) {
        $active = $key === $active_type ? ' active' : '';
        $h .= '<button class="lore-filter-tab' . $active . '" data-type="' . $key . '">' . $info[0] . ' ' . $info[1] . '</button>';
    }
    $h .= '</div>';
    return $h;
}

function render_search_bar($placeholder = 'Search lore...') {
    return '<div class="lore-search-bar">
                <span class="lore-search-icon">🔍</span>
                <input type="text" id="lore-search-input" placeholder="' . htmlspecialchars($placeholder) . '" autocomplete="off">
                <button class="lore-search-clear" id="lore-search-clear">✕</button>
            </div>';
}

// ─── RELEASE ARCHIVE ENTRY DISPATCHER ───
function render_release_entry($item, $conn = null) {
    $cat = $item['category'] ?? 'legendary';
    switch ($cat) {
        case 'manga': return render_manga_release($item);
        case 'anime': return render_anime_release($item);
        case 'character_debut': return render_character_debut_release($item, $conn);
        case 'arc_release': return render_arc_release_entry($item, $conn);
        default: return render_legendary_release($item, $conn);
    }
}

// ─── MANGA CHAPTER RELEASE CARD ───
function render_manga_release($item) {
    $title = htmlspecialchars($item['title']);
    $ch = (int)($item['chapter_number'] ?? 0);
    $date = !empty($item['release_date']) ? date('F j, Y', strtotime($item['release_date'])) : htmlspecialchars($item['event_date'] ?? '');
    $desc = htmlspecialchars($item['description'] ?? '');
    $imp = strtolower($item['importance'] ?? 'normal');
    $id = (int)($item['id'] ?? 0);
    $is_major = $imp === 'major';

    $h = '<div class="release-card release-manga">';
    $h .= '<div class="release-card-badge">📖 MANGA RELEASE</div>';
    if ($is_major) $h .= '<div class="release-major-flag">★ MAJOR RELEASE</div>';
    $h .= '<div class="release-card-chapter">CHAPTER ' . $ch . '</div>';
    $h .= '<div class="release-card-title">' . $title . '</div>';
    $h .= '<div class="release-card-date">' . $date . '</div>';
    $h .= '<div class="release-card-divider"></div>';
    if ($desc) $h .= '<div class="release-card-desc">' . $desc . '</div>';
    $h .= '<div class="release-card-actions"><a href="' . BASE_URL . 'lore/view.php?type=timeline&id=' . $id . '" class="release-card-link">Read Entry →</a></div>';
    $h .= '</div>';
    return $h;
}

// ─── ANIME EPISODE RELEASE CARD ───
function render_anime_release($item) {
    $title = htmlspecialchars($item['title']);
    $ep = (int)($item['episode_number'] ?? 0);
    $date = !empty($item['release_date']) ? date('F j, Y', strtotime($item['release_date'])) : htmlspecialchars($item['event_date'] ?? '');
    $desc = htmlspecialchars($item['description'] ?? '');
    $imp = strtolower($item['importance'] ?? 'normal');
    $id = (int)($item['id'] ?? 0);
    $is_major = $imp === 'major';

    $h = '<div class="release-card release-anime">';
    $h .= '<div class="release-card-badge">🎬 ANIME RELEASE</div>';
    if ($is_major) $h .= '<div class="release-major-flag">★ MAJOR RELEASE</div>';
    $h .= '<div class="release-card-chapter">EPISODE ' . $ep . '</div>';
    $h .= '<div class="release-card-title">' . $title . '</div>';
    $h .= '<div class="release-card-date">' . $date . '</div>';
    $h .= '<div class="release-card-divider"></div>';
    if ($desc) $h .= '<div class="release-card-desc">' . $desc . '</div>';
    $h .= '<div class="release-card-actions"><a href="' . BASE_URL . 'lore/view.php?type=timeline&id=' . $id . '" class="release-card-link">Read Entry →</a></div>';
    $h .= '</div>';
    return $h;
}

// ─── CHARACTER DEBUT RELEASE CARD ───
function render_character_debut_release($item, $conn = null) {
    $title = htmlspecialchars($item['title']);
    $date = !empty($item['release_date']) ? date('F j, Y', strtotime($item['release_date'])) : htmlspecialchars($item['event_date'] ?? '');
    $desc = htmlspecialchars($item['description'] ?? '');
    $id = (int)($item['id'] ?? 0);
    $char_id = (int)($item['character_id'] ?? 0);

    // Get character details
    $char_name = '';
    $char_img = '';
    $manga_ch = '';
    $anime_ep = '';
    if ($char_id && $conn) {
        $cq = $conn->query("SELECT name, manga_debut_chapter, anime_debut_episode FROM characters WHERE id = " . $char_id);
        if ($cq && $cq->num_rows) {
            $cd = $cq->fetch_assoc();
            $char_name = htmlspecialchars($cd['name']);
            $manga_ch = $cd['manga_debut_chapter'] ? 'Ch. ' . $cd['manga_debut_chapter'] : '';
            $anime_ep = $cd['anime_debut_episode'] ? 'Ep. ' . $cd['anime_debut_episode'] : '';
        }
    }

    $h = '<div class="release-card release-debut">';
    $h .= '<div class="release-card-badge">🏴‍☠️ CHARACTER DEBUT</div>';
    $h .= '<div class="release-debut-avatar"><img src="' . BASE_URL . 'lore/avatar.php?name=' . urlencode($char_name ?: $title) . '&bg=D4A843&color=fff&size=40" alt=""></div>';
    $h .= '<div class="release-card-title">' . ($char_name ?: $title) . '</div>';
    $h .= '<div class="release-card-date">' . $date . '</div>';
    if ($manga_ch || $anime_ep) {
        $h .= '<div class="release-debut-refs">';
        if ($manga_ch) $h .= '<span class="release-debut-ref">📖 ' . $manga_ch . '</span>';
        if ($anime_ep) $h .= '<span class="release-debut-ref">🎬 ' . $anime_ep . '</span>';
        $h .= '</div>';
    }
    $h .= '<div class="release-card-divider"></div>';
    if ($desc) $h .= '<div class="release-card-desc">' . $desc . '</div>';
    $h .= '<div class="release-card-actions"><a href="' . BASE_URL . 'lore/view.php?type=timeline&id=' . $id . '" class="release-card-link">View Debut →</a></div>';
    $h .= '</div>';
    return $h;
}

// ─── ARC RELEASE ENTRY CARD ───
function render_arc_release_entry($item, $conn = null) {
    $title = htmlspecialchars($item['title']);
    $date = !empty($item['release_date']) ? date('F j, Y', strtotime($item['release_date'])) : htmlspecialchars($item['event_date'] ?? '');
    $desc = htmlspecialchars($item['description'] ?? '');
    $id = (int)($item['id'] ?? 0);
    $arc_id = (int)($item['arc_id'] ?? 0);

    // Get arc details
    $manga_ch = '';
    $anime_ep = '';
    $arc_name = '';
    if ($arc_id && $conn) {
        $aq = $conn->query("SELECT name, manga_start_chapter, manga_end_chapter, anime_start_episode, anime_end_episode FROM arcs WHERE id = " . $arc_id);
        if ($aq && $aq->num_rows) {
            $ad = $aq->fetch_assoc();
            $arc_name = htmlspecialchars($ad['name']);
            if ($ad['manga_start_chapter'] && $ad['manga_end_chapter']) $manga_ch = 'Ch. ' . $ad['manga_start_chapter'] . '-' . $ad['manga_end_chapter'];
            if ($ad['anime_start_episode'] && $ad['anime_end_episode']) $anime_ep = 'Ep. ' . $ad['anime_start_episode'] . '-' . $ad['anime_end_episode'];
        }
    }

    $h = '<div class="release-card release-arc">';
    $h .= '<div class="release-card-badge">🌊 ARC RELEASE</div>';
    $h .= '<div class="release-card-title">' . ($arc_name ?: $title) . '</div>';
    $h .= '<div class="release-card-date">' . $date . '</div>';
    if ($manga_ch || $anime_ep) {
        $h .= '<div class="release-debut-refs">';
        if ($manga_ch) $h .= '<span class="release-debut-ref">📖 ' . $manga_ch . '</span>';
        if ($anime_ep) $h .= '<span class="release-debut-ref">🎬 ' . $anime_ep . '</span>';
        $h .= '</div>';
    }
    $h .= '<div class="release-card-divider"></div>';
    if ($desc) $h .= '<div class="release-card-desc">' . $desc . '</div>';
    $h .= '<div class="release-card-actions"><a href="' . BASE_URL . 'lore/view.php?type=timeline&id=' . $id . '" class="release-card-link">View Arc →</a></div>';
    $h .= '</div>';
    return $h;
}

// ─── LEGENDARY MOMENT (existing style adapted for release archive) ───
function render_legendary_release($item, $conn = null) {
    $title = htmlspecialchars($item['title']);
    $date = !empty($item['release_date']) ? date('F j, Y', strtotime($item['release_date'])) : htmlspecialchars($item['event_date'] ?? '');
    $desc = htmlspecialchars($item['description'] ?? '');
    $id = (int)($item['id'] ?? 0);
    $imp = strtolower($item['importance'] ?? 'normal');
    $is_major = $imp === 'major';

    $year = 0;
    if (preg_match('/(\d{4})/', $date, $m)) $year = (int)$m[1];

    $h = '<div class="release-card release-legendary">';
    $h .= '<div class="release-card-badge">🔥 LEGENDARY MOMENT</div>';
    if ($is_major) $h .= '<div class="release-major-flag">★ MONUMENTAL</div>';
    if ($year) $h .= '<div class="release-legendary-year">' . $year . '</div>';
    $h .= '<div class="release-card-title">' . $title . '</div>';
    $h .= '<div class="release-card-date">' . $date . '</div>';
    $h .= '<div class="release-card-divider"></div>';
    if ($desc) $h .= '<div class="release-card-desc">' . $desc . '</div>';
    $h .= '<div class="release-card-actions"><a href="' . BASE_URL . 'lore/view.php?type=timeline&id=' . $id . '" class="release-card-link">Read Entry →</a></div>';
    $h .= '</div>';
    return $h;
}

// ─── RELEASE ARCHIVE FILTER TABS ───
function render_release_filter_tabs($active_category) {
    $tabs = [
        '' => ['📰', 'All'],
        'manga' => ['📖', 'Manga'],
        'anime' => ['🎬', 'Anime'],
        'character_debut' => ['🏴‍☠️', 'Debuts'],
        'arc_release' => ['🌊', 'Arcs'],
        'legendary' => ['🔥', 'Legendary'],
    ];
    $h = '<div class="release-filter-tabs">';
    foreach ($tabs as $key => $info) {
        $active = $key === $active_category ? ' active' : '';
        $h .= '<a href="' . BASE_URL . 'lore/timeline.php' . ($key ? '?category=' . $key : '') . '" class="release-filter-tab' . $active . '">' . $info[0] . ' ' . $info[1] . '</a>';
    }
    $h .= '</div>';
    return $h;
}
