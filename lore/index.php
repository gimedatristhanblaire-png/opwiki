<?php
$page_title = 'One Piece Lore Database — Grand Line Archives';
$meta_description = 'Explore the world of One Piece — characters, Devil Fruits, arcs, and timeline events. Marine Intelligence Database.';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/card_renderer.php';

$counts = [];
$tables = ['characters' => 'Characters', 'devil_fruits' => 'Devil Fruits', 'arcs' => 'Story Arcs', 'timeline' => 'Events'];
foreach ($tables as $key => $label) {
    $r = $conn->query("SELECT COUNT(*) as c FROM $key");
    $counts[$key] = $r ? (int)$r->fetch_assoc()['c'] : 0;
}

$r = $conn->query("SELECT COUNT(*) as c FROM chapters"); $c_count = ($r && $r->num_rows) ? (int)$r->fetch_assoc()['c'] : 0;
$r = $conn->query("SELECT COUNT(*) as c FROM episodes"); $e_count = ($r && $r->num_rows) ? (int)$r->fetch_assoc()['c'] : 0;
$counts['chapters'] = $c_count + $e_count;

$r = $conn->query("SELECT * FROM characters ORDER BY RAND() LIMIT 1"); $random_char = ($r && $r->num_rows) ? $r->fetch_assoc() : null;
$recent_timeline = $conn->query("SELECT * FROM timeline ORDER BY id DESC LIMIT 3");

$r = $conn->query("SELECT chapter_number, title, release_date FROM chapters ORDER BY chapter_number DESC LIMIT 1"); $latest_chapter = ($r && $r->num_rows) ? $r->fetch_assoc() : null;
$r = $conn->query("SELECT episode_number, title, air_date FROM episodes ORDER BY episode_number DESC LIMIT 1"); $latest_episode = ($r && $r->num_rows) ? $r->fetch_assoc() : null;

$recent_articles = $conn->query("SELECT id, title, slug FROM wiki_articles WHERE status='approved' ORDER BY created_at DESC LIMIT 5");
$recent_theories = $conn->query("SELECT id, title, slug FROM theories WHERE status='approved' ORDER BY created_at DESC LIMIT 5");

// Arc tracker data — saga progress
$saga_progress = [];
$sg = $conn->query("SELECT saga, COUNT(*) as arc_count FROM arcs GROUP BY saga ORDER BY MIN(arc_number)");
if ($sg) {
    while ($s = $sg->fetch_assoc()) {
        $saga_name = $s['saga'];
        // Determine completion: all sagas except Final Saga are complete (manga/anime finished)
        $is_final = ($saga_name === 'Final Saga');
        $total_arcs = (int)$s['arc_count'];
        // Get how many arcs have been fully released (all arcs have chapters)
        $completed = $conn->query("SELECT COUNT(*) as c FROM arcs WHERE saga='$saga_name'");
        $completed_count = $completed ? (int)$completed->fetch_assoc()['c'] : $total_arcs;
        $saga_progress[] = [
            'name' => $saga_name,
            'total' => $total_arcs,
            'completed' => $completed_count,
            'pct' => $is_final ? 60 : 100,
        ];
    }
}
?>
<section id="lore-hub-v2" class="lore-section">
    <div class="container">
        <!-- Header -->
        <div class="lore-hub-header">
            <div class="lore-hub-badge">🏴‍☠️ MARINE INTELLIGENCE DIVISION</div>
            <h1 class="lore-hub-title">Grand Line Archives</h1>
            <p class="lore-hub-sub">Classified bounty records, Devil Fruit encyclopedias, and historical documents from the world of One Piece.</p>
        </div>

        <!-- Category Cards -->
        <div class="lore-hub-categories">
            <?php
            $cats = [
                'characters' => ['🏴‍☠️', 'Characters', 'Wanted Posters', '#C62828', 'browse.php?type=characters'],
                'devil_fruits' => ['🍎', 'Devil Fruits', 'Encyclopedia Entries', '#2E7D32', 'browse.php?type=devil_fruits'],
                'arcs' => ['🌊', 'Story Arcs', 'Campaign Reports', '#1565C0', 'browse.php?type=arcs'],
                'timeline' => ['⏳', 'Timeline', 'Log Entries', '#6A1B9A', 'browse.php?type=timeline'],
                'morgans' => ['📰', 'Morgans Treasury', 'Release Archive', '#C62828', 'timeline.php'],
                'chapters' => ['📚', 'Chapters', 'Manga & Anime Releases', '#1565C0', '../chapters/'],
                'rankings' => ['👑', 'Grand Line Rankings', 'Top Contributors', '#D4A843', '../leaderboard/'],
                'discovery' => ['🎲', 'Random Discovery', 'Surprise Entry', '#00838F', 'browse.php?type=characters'],
            ];
            foreach ($cats as $key => $info):
                $extra = '';
                if ($key === 'chapters') {
                    $mc = $conn->query("SELECT COUNT(*) as c FROM chapters")->fetch_assoc()['c'] ?? 0;
                    $ec = $conn->query("SELECT COUNT(*) as c FROM episodes")->fetch_assoc()['c'] ?? 0;
                    $extra = ' · ' . number_format($mc) . ' ch · ' . number_format($ec) . ' ep';
                }
            ?>
            <a href="<?php echo $info[4]; ?>" class="lore-hub-cat" style="--cat-color: <?php echo $info[3]; ?>;">
                <div class="lore-hub-cat-icon"><?php echo $info[0]; ?></div>
                <div class="lore-hub-cat-name"><?php echo $info[1]; ?></div>
                <div class="lore-hub-cat-sub"><?php echo $info[2]; ?></div>
                <div class="lore-hub-cat-count"><?php echo number_format($counts[$key] ?? 0); ?> entries<?php echo $extra; ?></div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Grand Line Journey — Arc Tracker -->
        <?php
        $all_arcs = $conn->query("SELECT id, name, saga, arc_number, chapters, episodes, key_villains, location, manga_start_chapter, manga_end_chapter, anime_start_episode, anime_end_episode FROM arcs ORDER BY arc_number");
        $journey_arcs = [];
        if ($all_arcs) {
            while ($a = $all_arcs->fetch_assoc()) {
                $journey_arcs[] = $a;
            }
        }
        $arcs_by_saga = [];
        foreach ($journey_arcs as $a) {
            $arcs_by_saga[$a['saga']][] = $a;
        }
        $saga_order = array_keys($arcs_by_saga);
        $saga_slug_map = [
            'East Blue' => 'east-blue',
            'Alabasta' => 'alabasta',
            'Sky Island' => 'skypiea',
            'Water 7' => 'water-7',
            'Thriller Bark' => 'thriller-bark',
            'Summit War' => 'summit-war',
            'Fish-Man Island' => 'fish-man-island',
            'Dressrosa' => 'dressrosa',
            'Four Emperors' => 'four-emperors',
            'Final Saga' => 'final-saga',
        ];
        $current_arc = !empty($journey_arcs) ? end($journey_arcs) : null;
        $current_arc_id = $current_arc ? $current_arc['id'] : 0;
        $saga_icons = ['🏴‍☠️','🔥','☁️','🌊','💀','⚔️','🐟','🌹','👑','⭐'];
        ?>
        <div class="grand-line-journey">
            <div class="journey-header">
                <span class="journey-icon">⛵</span>
                <h2 class="journey-title">The Grand Line Journey</h2>
                <p class="journey-sub">Following the Straw Hats across the seas</p>
            </div>
            <div class="journey-route">
                <?php foreach ($saga_order as $si => $saga_name):
                    $saga_arcs = $arcs_by_saga[$saga_name];
                    $slug = $saga_slug_map[$saga_name] ?? strtolower(str_replace(' ', '-', $saga_name));
                    $is_last = ($si === count($saga_order) - 1);
                    $arc_count = count($saga_arcs);
                    $first_arc = $saga_arcs[0];
                    $last_arc = end($saga_arcs);
                    $ch_start = $first_arc['manga_start_chapter'] ?? '';
                    $ch_end = $last_arc['manga_end_chapter'] ?? '';
                    $ch_total = $ch_start && $ch_end ? 'Ch. ' . $ch_start . '–' . $ch_end : '';
                    $icon = $saga_icons[$si] ?? '🏝️';
                    $has_current = $current_arc && $current_arc['saga'] === $saga_name;
                ?>
                <a href="<?php echo BASE_URL; ?>lore/browse.php?type=arcs" class="journey-saga-card <?php echo $has_current ? 'saga-current' : ''; ?>">
                    <div class="saga-card-icon"><?php echo $icon; ?></div>
                    <div class="saga-card-name"><?php echo htmlspecialchars($saga_name); ?> Saga</div>
                    <div class="saga-card-arcs"><?php echo $arc_count; ?> arc<?php echo $arc_count > 1 ? 's' : ''; ?></div>
                    <?php if ($ch_total): ?>
                    <div class="saga-card-chapters"><?php echo $ch_total; ?></div>
                    <?php endif; ?>
                    <?php if ($has_current && $current_arc): ?>
                    <div class="saga-card-current">📍 <?php echo htmlspecialchars($current_arc['name']); ?></div>
                    <?php endif; ?>
                </a>
                <?php if (!$is_last): ?>
                <div class="journey-connector">
                    <div class="connect-line"></div>
                    <div class="connect-icon">⛵</div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="journey-footer">
                <span class="journey-compass">🧭</span>
                <span class="journey-total"><?php echo count($journey_arcs); ?> islands charted across <?php echo count($saga_order); ?> seas</span>
            </div>
        </div>

        <!-- Featured Content Grid -->
        <div class="lore-hub-featured">
            <?php if ($latest_chapter): ?>
            <div class="lore-hub-feat-card">
                <div class="lore-hub-feat-label">📖 LATEST CHAPTER</div>
                <div class="lore-release-card">
                    <div class="lore-release-number">Chapter #<?php echo $latest_chapter['chapter_number']; ?></div>
                    <div class="lore-release-title"><?php echo htmlspecialchars($latest_chapter['title']); ?></div>
                    <div class="lore-release-date"><?php echo date('M j, Y', strtotime($latest_chapter['release_date'])); ?></div>
                    <a href="<?php echo BASE_URL; ?>chapters/view.php?type=chapter&id=<?php echo $latest_chapter['chapter_number']; ?>" class="btn-sm lore-view-btn">View Chapter</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($latest_episode): ?>
            <div class="lore-hub-feat-card">
                <div class="lore-hub-feat-label">🎬 LATEST EPISODE</div>
                <div class="lore-release-card">
                    <div class="lore-release-number">Episode #<?php echo $latest_episode['episode_number']; ?></div>
                    <div class="lore-release-title"><?php echo htmlspecialchars($latest_episode['title']); ?></div>
                    <div class="lore-release-date"><?php echo date('M j, Y', strtotime($latest_episode['air_date'])); ?></div>
                    <a href="<?php echo BASE_URL; ?>chapters/view.php?type=episode&id=<?php echo $latest_episode['episode_number']; ?>" class="btn-sm lore-view-btn">View Episode</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($random_char): ?>
            <div class="lore-hub-feat-card">
                <div class="lore-hub-feat-label">🎲 RANDOM BOUNTY</div>
                <?php echo render_lore_card($random_char, 'characters', $conn); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Timeline -->
        <?php if ($recent_timeline && $recent_timeline->num_rows > 0): ?>
        <div class="lore-hub-timeline">
            <div class="lore-hub-section-title">⏳ Recent History</div>
            <div class="lore-hub-timeline-list">
                <?php while ($tl = $recent_timeline->fetch_assoc()): ?>
                    <?php echo render_lore_card($tl, 'timeline', $conn); ?>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Wiki & Theories -->
        <div class="lore-hub-links">
            <div class="lore-hub-link-card">
                <div class="lore-hub-link-icon">📖</div>
                <h3>Related Wiki Articles</h3>
                <?php if ($recent_articles && $recent_articles->num_rows > 0): ?>
                <ul>
                    <?php while ($a = $recent_articles->fetch_assoc()): ?>
                    <li><a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($a['slug']); ?>"><?php echo htmlspecialchars($a['title']); ?></a></li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?><p class="lore-empty">No articles yet.</p><?php endif; ?>
                <a href="<?php echo BASE_URL; ?>wiki/" class="btn-sm">Browse All Articles</a>
            </div>
            <div class="lore-hub-link-card">
                <div class="lore-hub-link-icon">💭</div>
                <h3>Related Theories</h3>
                <?php if ($recent_theories && $recent_theories->num_rows > 0): ?>
                <ul>
                    <?php while ($t = $recent_theories->fetch_assoc()): ?>
                    <li><a href="<?php echo BASE_URL; ?>theories/view.php?slug=<?php echo urlencode($t['slug']); ?>"><?php echo htmlspecialchars($t['title']); ?></a></li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?><p class="lore-empty">No theories yet.</p><?php endif; ?>
                <a href="<?php echo BASE_URL; ?>theories/" class="btn-sm">Browse All Theories</a>
            </div>
        </div>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <p class="lore-admin-link"><a href="manage.php" class="btn">⚙️ Manage Lore Database</a></p>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
