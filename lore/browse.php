<?php
$page_title = 'One Piece Lore Database — Wanted Posters';
$meta_description = 'Browse the Grand Line archives — characters, Devil Fruits, arcs, and timeline events.';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/card_renderer.php';

$type = $_GET['type'] ?? 'characters';
$valid_types = ['characters', 'devil_fruits', 'arcs', 'timeline'];
if (!in_array($type, $valid_types)) $type = 'characters';

$table = $type;
$title_field = $type === 'timeline' ? 'title' : 'name';
$order = ($type === 'arcs') ? 'arc_number ASC' : "$title_field ASC";

$items = [];
$sql = "SELECT * FROM `$table` ORDER BY $order";
$r = $conn->query($sql);
if ($r) { while ($row = $r->fetch_assoc()) { $items[] = $row; } }

$type_labels = ['characters' => 'Characters', 'devil_fruits' => 'Devil Fruits', 'arcs' => 'Story Arcs', 'timeline' => 'Timeline'];
$emojis = ['characters' => '🏴‍☠️', 'devil_fruits' => '🍎', 'arcs' => '🌊', 'timeline' => '⏳'];

$counts = [];
foreach ($valid_types as $t) {
    $ct = $conn->query("SELECT COUNT(*) as c FROM $t");
    $counts[$t] = $ct ? $ct->fetch_assoc()['c'] : 0;
}
?>
<section id="lore-browse-v2" class="lore-section">
    <div class="container">
        <div class="lore-header">
            <h2 class="lore-heading"><span class="lore-heading-icon">🏴‍☠️</span> Grand Line Archives</h2>
            <p class="lore-subtitle">Marine Intelligence Division — Bounty Records & Historical Documents</p>
        </div>

        <?php echo render_filter_tabs($type); ?>
        <?php echo render_search_bar('Search ' . $type_labels[$type] . '...'); ?>

        <div class="lore-type-count">
            <?php foreach ($valid_types as $t): ?>
                <span class="lore-type-stat <?php echo $t === $type ? 'active' : ''; ?>" data-type="<?php echo $t; ?>">
                    <?php echo $emojis[$t]; ?> <?php echo $type_labels[$t]; ?>: <strong><?php echo $counts[$t]; ?></strong>
                </span>
            <?php endforeach; ?>
        </div>

        <div id="lore-results" class="lore-grid-v2">
            <?php if (empty($items)): ?>
                <div class="lore-empty">No entries found in this category.</div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <?php echo render_lore_card($item, $type, $conn); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <p class="lore-back-link"><a href="<?php echo BASE_URL; ?>lore/" class="btn">&laquo; Back to Lore Hub</a></p>

        <!-- Category Cards -->
        <?php
        $browse_cats = [
            'characters' => ['🏴‍☠️', 'Characters', 'Wanted Posters', '#C62828', 'browse.php?type=characters'],
            'devil_fruits' => ['🍎', 'Devil Fruits', 'Encyclopedia Entries', '#2E7D32', 'browse.php?type=devil_fruits'],
            'arcs' => ['🌊', 'Story Arcs', 'Campaign Reports', '#1565C0', 'browse.php?type=arcs'],
            'timeline' => ['⏳', 'Timeline', 'Log Entries', '#6A1B9A', 'browse.php?type=timeline'],
            'morgans' => ['📰', 'Morgans Treasury', 'Release Archive', '#C62828', 'timeline.php'],
            'chapters' => ['📚', 'Chapters', 'Manga & Anime Releases', '#1565C0', '../chapters/'],
            'rankings' => ['👑', 'Grand Line Rankings', 'Top Contributors', '#D4A843', '../leaderboard/'],
            'discovery' => ['🎲', 'Random Discovery', 'Surprise Entry', '#00838F', 'browse.php?type=characters'],
        ];
        ?>
        <div class="lore-hub-categories">
            <?php foreach ($browse_cats as $info): ?>
            <a href="<?php echo $info[4]; ?>" class="lore-hub-cat" style="--cat-color: <?php echo $info[3]; ?>;">
                <div class="lore-hub-cat-icon"><?php echo $info[0]; ?></div>
                <div class="lore-hub-cat-name"><?php echo $info[1]; ?></div>
                <div class="lore-hub-cat-sub"><?php echo $info[2]; ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script src="<?php echo BASE_URL; ?>assets/js/browse.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
