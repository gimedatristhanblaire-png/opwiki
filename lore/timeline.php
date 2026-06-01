<?php
$page_title = 'Official One Piece Release Archive';
$meta_description = 'Complete manga and anime release chronology — chapter releases, episode air dates, character debuts, and arc publication history.';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/card_renderer.php';

$category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_STRING);
$valid_cats = ['', 'manga', 'anime', 'character_debut', 'arc_release', 'legendary'];
if (!in_array($category, $valid_cats)) $category = '';

$where = $category ? " WHERE t.category = '" . $conn->real_escape_string($category) . "'" : '';
$sql = "SELECT t.*, a.name as arc_name 
        FROM timeline t 
        LEFT JOIN arcs a ON t.arc_id = a.id 
        $where 
        ORDER BY t.id ASC";

$events = [];
$r = $conn->query($sql);
if ($r) { while ($row = $r->fetch_assoc()) $events[] = $row; }

// Group by year
$years = [];
$total_by_cat = [];
foreach ($events as $e) {
    $ts = null;
    if (!empty($e['release_date'])) $ts = strtotime($e['release_date']);
    elseif (!empty($e['event_date'])) $ts = strtotime($e['event_date']);
    $year = $ts ? (int)date('Y', $ts) : 0;
    if ($year < 1990) $year = 0;
    if (!isset($years[$year])) $years[$year] = [];
    $years[$year][] = $e;
    $cat = $e['category'] ?? 'legendary';
    if (!isset($total_by_cat[$cat])) $total_by_cat[$cat] = 0;
    $total_by_cat[$cat]++;
}
ksort($years);

// Category counts for filter display
$total_all = count($events);
?>
<section id="release-archive" class="newspaper-page">
    <div class="newspaper-container">
        <!-- Header -->
        <div class="newspaper-masthead">
            <div class="masthead-badge">📰 WORLD ECONOMY NEWS PAPER</div>
            <h1 class="masthead-title release-masthead-title">OFFICIAL RELEASE ARCHIVE</h1>
            <p class="masthead-sub">Complete chronology of One Piece publication history</p>
            <div class="masthead-line">━━━━━━━━━━━━━━━━━━━━</div>
        </div>

        <!-- Category Filters -->
        <div class="release-filter-wrapper">
            <?php echo render_release_filter_tabs($category); ?>
            <div class="release-filter-stats">
                <span><?php echo $total_all; ?> total entries</span>
                <?php foreach (['manga', 'anime', 'character_debut', 'arc_release', 'legendary'] as $c): ?>
                    <?php if (isset($total_by_cat[$c])): ?>
                        <span class="release-stat-dot" style="background:var(--release-<?php echo $c; ?>);"></span>
                        <span><?php echo $total_by_cat[$c]; ?> <?php echo ucfirst(str_replace('_', ' ', $c)); ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Timeline Entries -->
        <?php if (empty($events)): ?>
            <div class="newspaper-empty">⏳ No entries found for this category.</div>
        <?php else: ?>
            <div class="release-timeline-rail">
                <?php foreach ($years as $year => $evts): ?>
                <div class="release-year-block" id="year-<?php echo $year; ?>">
                    <div class="release-year-header">
                        <span class="release-year-label"><?php echo $year ?: 'Unknown Era'; ?></span>
                        <span class="release-year-count"><?php echo count($evts); ?> event<?php echo count($evts) !== 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="release-year-entries">
                        <?php foreach ($evts as $e): ?>
                        <?php echo render_release_entry($e, $conn); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="newspaper-back-link"><a href="<?php echo BASE_URL; ?>lore/" class="btn">&laquo; Back to Lore Hub</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
