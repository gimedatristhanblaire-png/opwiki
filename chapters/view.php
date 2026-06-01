<?php
$page_title = 'Chapter / Episode';
$meta_description = 'One Piece chapter and episode details.';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';

$type = $_GET['type'] ?? 'chapter';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: ' . BASE_URL . 'chapters/'); exit(); }

if ($type === 'episode') {
    $stmt = $conn->prepare("SELECT ep.*, a.name as arc_name, a.saga FROM episodes ep JOIN arcs a ON ep.arc_id = a.id WHERE ep.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$item) { header('Location: ' . BASE_URL . 'chapters/'); exit(); }
    $page_title = 'Episode ' . $item['episode_number'];
    $prev = $conn->query("SELECT id, episode_number FROM episodes WHERE episode_number < {$item['episode_number']} ORDER BY episode_number DESC LIMIT 1")->fetch_assoc();
    $next = $conn->query("SELECT id, episode_number FROM episodes WHERE episode_number > {$item['episode_number']} ORDER BY episode_number ASC LIMIT 1")->fetch_assoc();
} else {
    $stmt = $conn->prepare("SELECT ch.*, a.name as arc_name, a.saga, a.id as arc_id FROM chapters ch JOIN arcs a ON ch.arc_id = a.id WHERE ch.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$item) { header('Location: ' . BASE_URL . 'chapters/'); exit(); }
    $page_title = 'Chapter ' . $item['chapter_number'];
    $prev = $conn->query("SELECT id, chapter_number FROM chapters WHERE chapter_number < {$item['chapter_number']} ORDER BY chapter_number DESC LIMIT 1")->fetch_assoc();
    $next = $conn->query("SELECT id, chapter_number FROM chapters WHERE chapter_number > {$item['chapter_number']} ORDER BY chapter_number ASC LIMIT 1")->fetch_assoc();
}

require_once __DIR__ . '/../includes/header.php';
$is_ep = $type === 'episode';
?>
<section class="chapter-detail">
    <div class="container">
        <div class="chapters-view-container">
            <div class="parchment-card chapters-view-card">
                <div class="chapters-view-header">
                    <div>
                        <span class="chapters-view-type-label">
                            <?php echo $is_ep ? '🎬 Episode' : '📖 Chapter'; ?>
                        </span>
                        <h1 class="chapters-view-title">
                            #<?php echo $is_ep ? $item['episode_number'] : $item['chapter_number']; ?>
                        </h1>
                    </div>
                    <div class="chapters-view-meta-right">
                        <div class="chapters-view-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="chapters-view-item-arc">
                            <?php echo htmlspecialchars($item['saga']); ?> Saga — <?php echo htmlspecialchars($item['arc_name']); ?>
                        </div>
                    </div>
                </div>

                <div class="chapters-view-info-box">
                    <div class="chapters-view-info-row">
                        <div><span class="chapters-view-info-label">Release Date</span><br><strong class="chapters-view-info-value"><?php echo $is_ep ? ($item['air_date'] ?? 'TBD') : ($item['release_date'] ?? 'TBD'); ?></strong></div>
                        <?php if (!$is_ep && $item['volume']): ?>
                        <div><span class="chapters-view-info-label">Volume</span><br><strong class="chapters-view-info-value"><?php echo htmlspecialchars($item['volume']); ?></strong></div>
                        <?php endif; ?>
                        <div><span class="chapters-view-info-label">Story Arc</span><br><a href="<?php echo BASE_URL; ?>lore/view.php?type=arcs&id=<?php echo $item['arc_id']; ?>" class="chapters-view-info-link"><?php echo htmlspecialchars($item['arc_name']); ?></a></div>
                    </div>
                </div>

                <div class="chapters-view-nav">
                    <?php if ($prev): ?>
                    <a href="<?php echo BASE_URL; ?>chapters/view.php?type=<?php echo $type; ?>&id=<?php echo $prev['id']; ?>" class="ai-btn chapters-view-nav-btn">← #<?php echo $is_ep ? $prev['episode_number'] : $prev['chapter_number']; ?></a>
                    <?php else: ?>
                    <div class="chapters-view-nav-spacer"></div>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>chapters/" class="ai-btn chapters-view-nav-center">📚 All Chapters</a>
                    <?php if ($next): ?>
                    <a href="<?php echo BASE_URL; ?>chapters/view.php?type=<?php echo $type; ?>&id=<?php echo $next['id']; ?>" class="ai-btn chapters-view-nav-right">#<?php echo $is_ep ? $next['episode_number'] : $next['chapter_number']; ?> →</a>
                    <?php else: ?>
                    <div class="chapters-view-nav-spacer"></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            // Show timeline entries related to this chapter/episode
            $num = $is_ep ? $item['episode_number'] : $item['chapter_number'];
            $field = $is_ep ? 'episode_number' : 'chapter_number';
            $tl = $conn->query("SELECT id, title, description, category FROM timeline WHERE $field = $num LIMIT 5");
            if ($tl && $tl->num_rows > 0):
            ?>
            <div class="timeline-related-card">
                <h3 class="timeline-related-title">📰 Related Timeline Events</h3>
                <?php while ($t = $tl->fetch_assoc()): ?>
                <div class="timeline-related-item">
                    <a href="<?php echo BASE_URL; ?>lore/view.php?type=timeline&id=<?php echo $t['id']; ?>" class="timeline-related-link">
                        <strong><?php echo htmlspecialchars($t['title']); ?></strong>
                        <span class="timeline-related-cat">[<?php echo $t['category']; ?>]</span>
                    </a>
                    <?php if ($t['description']): ?>
                    <div class="timeline-related-desc"><?php echo htmlspecialchars($t['description']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
