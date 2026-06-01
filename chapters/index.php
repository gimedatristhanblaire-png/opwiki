<?php
$page_title = 'Chapter & Episode Hub';
$meta_description = 'Browse One Piece chapters and episodes. Track your journey through the Grand Line.';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';

$total_ch = $conn->query("SELECT COUNT(*) as c FROM chapters")->fetch_assoc()['c'];
$total_ep = $conn->query("SELECT COUNT(*) as c FROM episodes")->fetch_assoc()['c'];
$latest_ch = $conn->query("SELECT chapter_number, title FROM chapters ORDER BY chapter_number DESC LIMIT 1")->fetch_assoc();
$latest_ep = $conn->query("SELECT episode_number, title FROM episodes ORDER BY episode_number DESC LIMIT 1")->fetch_assoc();

$view = $_GET['view'] ?? 'chapters';
$arc_filter = isset($_GET['arc']) ? (int)$_GET['arc'] : 0;

// Saga color & icon mapping
$saga_theme = [
    'East Blue'       => ['color' => '#2196F3', 'icon' => '🌊', 'desc' => 'The journey begins'],
    'Alabasta'        => ['color' => '#FF9800', 'icon' => '🏜️', 'desc' => 'Desert kingdom war'],
    'Skypiea'         => ['color' => '#E0E0E0', 'icon' => '☁️', 'desc' => 'Adventure in the sky'],
    'Water 7'         => ['color' => '#1565C0', 'icon' => '🚂', 'desc' => 'City of water & intrigue'],
    'Thriller Bark'   => ['color' => '#4A148C', 'icon' => '🎃', 'desc' => 'Ghost island horror'],
    'Summit War'      => ['color' => '#C62828', 'icon' => '⚔️', 'desc' => 'The great war'],
    'Fish-Man Island' => ['color' => '#00BCD4', 'icon' => '🐟', 'desc' => 'Underwater paradise'],
    'Dressrosa'       => ['color' => '#FF5722', 'icon' => '🏰', 'desc' => 'Romance colosseum'],
    'Whole Cake Island' => ['color' => '#E91E63', 'icon' => '🍰', 'desc' => 'Yonko territory'],
    'Wano'            => ['color' => '#7B1FA2', 'icon' => '🗾', 'desc' => 'Samurai country'],
    'Final Saga'      => ['color' => '#00E676', 'icon' => '👑', 'desc' => 'The final journey'],
];
$default_theme = ['color' => '#D4A843', 'icon' => '📚', 'desc' => ''];

// Fetch arcs grouped by saga
$sagas = $conn->query("SELECT saga, MIN(arc_number) as min_num FROM arcs GROUP BY saga ORDER BY min_num");
$all_arcs = $conn->query("SELECT id, name, arc_number, saga, chapters, episodes, hype_rating, tragedy_meter, location, description FROM arcs ORDER BY arc_number");
$arcs_by_saga = [];
while ($a = $all_arcs->fetch_assoc()) {
    $arcs_by_saga[$a['saga']][] = $a;
}
?>
<section id="chapter-hub">
    <div class="container">
        <!-- Cinematic Header -->
        <div class="section-header"><span class="section-icon">📚</span> Chapter &amp; Episode Hub</div>
        <p class="chapters-subtitle">Track your journey through the Grand Line — from Romance Dawn to the Final Saga.</p>

        <!-- Stats -->
        <div class="chapters-stats-row">
            <div class="parchment-card chapters-stat-card">
                <div class="chapters-stat-number"><?php echo $total_ch; ?></div>
                <div class="chapters-stat-label">Chapters</div>
                <?php if ($latest_ch): ?><div class="chapters-stat-latest">Latest: #<?php echo $latest_ch['chapter_number']; ?> — <?php echo htmlspecialchars($latest_ch['title']); ?></div><?php endif; ?>
            </div>
            <div class="parchment-card chapters-stat-card">
                <div class="chapters-stat-number"><?php echo $total_ep; ?></div>
                <div class="chapters-stat-label">Episodes</div>
                <?php if ($latest_ep): ?><div class="chapters-stat-latest">Latest: #<?php echo $latest_ep['episode_number']; ?> — <?php echo htmlspecialchars($latest_ep['title']); ?></div><?php endif; ?>
            </div>
        </div>

        <!-- View Toggle + Arc Filter -->
        <div class="chapters-toolbar">
            <div class="chapters-toggle">
                <a href="?view=chapters<?php echo $arc_filter ? '&arc='.$arc_filter : ''; ?>" class="ai-btn <?php echo $view==='chapters'?'chapters-toggle-active':''; ?>">📖 Chapters</a>
                <a href="?view=episodes<?php echo $arc_filter ? '&arc='.$arc_filter : ''; ?>" class="ai-btn <?php echo $view==='episodes'?'chapters-toggle-active':''; ?>">🎬 Episodes</a>
            </div>
            <div>
                <form method="GET" class="chapters-filter-form">
                    <?php if ($view): ?><input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>"><?php endif; ?>
                    <select name="arc" onchange="this.form.submit()" class="chapters-arc-select">
                        <option value="0">All Arcs</option>
                        <?php $arcs_all = $conn->query("SELECT id, name, saga FROM arcs ORDER BY arc_number"); while ($a = $arcs_all->fetch_assoc()): ?>
                        <option value="<?php echo $a['id']; ?>" <?php echo $arc_filter==$a['id']?'selected':''; ?>><?php echo htmlspecialchars($a['saga'] . ' — ' . $a['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>
        </div>

        <!-- Saga Accordion -->
        <div class="saga-accordion">
            <?php while ($saga_row = $sagas->fetch_assoc()):
                $saga_name = $saga_row['saga'];
                $theme = $saga_theme[$saga_name] ?? $default_theme;
                $arcs_in_saga = $arcs_by_saga[$saga_name] ?? [];
            ?>
            <div class="saga-group">
                <div class="saga-header">
                    <div class="saga-color-bar" style="background:<?php echo $theme['color']; ?>;"></div>
                    <span class="saga-header-icon"><?php echo $theme['icon']; ?></span>
                    <div class="saga-header-info">
                        <div class="saga-header-title"><?php echo htmlspecialchars($saga_name); ?> Saga</div>
                        <div class="saga-header-sub"><?php echo $theme['desc']; ?> • <?php echo count($arcs_in_saga); ?> arc<?php echo count($arcs_in_saga) !== 1 ? 's' : ''; ?></div>
                    </div>
                    <span class="saga-toggle">▼</span>
                </div>
                <div class="saga-arcs">
                    <div class="saga-arcs-inner">
                        <?php foreach ($arcs_in_saga as $arc): ?>
                        <a href="<?php echo BASE_URL; ?>lore/view.php?type=arc&id=<?php echo $arc['id']; ?>" class="arc-card-horizontal">
                            <div class="arc-card-image" style="background:<?php echo $theme['color']; ?>22;">
                                <div class="arc-card-image-overlay" style="background:linear-gradient(135deg, <?php echo $theme['color']; ?>44, transparent);"></div>
                                <span class="arc-card-image-icon"><?php echo $theme['icon']; ?></span>
                            </div>
                            <div class="arc-card-info">
                                <div class="arc-card-info-title"><?php echo htmlspecialchars($arc['name']); ?> <span class="arc-card-number-badge">#<?php echo $arc['arc_number']; ?></span></div>
                                <div class="arc-card-info-stats">
                                    <span>📖 Ch. <?php echo $arc['chapters'] ?: '—'; ?></span>
                                    <span>🎬 Ep. <?php echo $arc['episodes'] ?: '—'; ?></span>
                                    <?php if ($arc['location']): ?><span>📍 <?php echo htmlspecialchars($arc['location']); ?></span><?php endif; ?>
                                </div>
                                <?php if ($arc['description']): ?>
                                <div class="arc-card-info-summary"><?php echo htmlspecialchars(substr(strip_tags($arc['description']), 0, 180)); ?></div>
                                <?php endif; ?>
                                <div class="arc-card-info-meta">
                                    <?php if ($arc['hype_rating']): ?>
                                    <span class="arc-card-tag" style="background:<?php echo $theme['color']; ?>22;color:<?php echo $theme['color']; ?>;">🔥 <?php echo $arc['hype_rating']; ?>/100</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        <?php if (empty($arcs_in_saga)): ?>
                        <div class="chapters-empty">No arcs in this saga yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Chapter/Episode Detail Section -->
        <?php
        if ($view === 'chapters'):
            $where = $arc_filter ? "WHERE arc_id = $arc_filter" : '';
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $per_page = 50;
            $offset = ($page - 1) * $per_page;
            $total = $conn->query("SELECT COUNT(*) as c FROM chapters $where")->fetch_assoc()['c'];
            $pages = ceil($total / $per_page);
            $chapters = $conn->query("SELECT ch.*, a.name as arc_name, a.saga FROM chapters ch JOIN arcs a ON ch.arc_id=a.id $where ORDER BY ch.chapter_number DESC LIMIT $per_page OFFSET $offset");
        ?>
        <div class="chapters-list-section">
            <div class="chapters-list-title">
                📖 Chapter List
                <?php
                if ($arc_filter) {
                    $arc_name = $conn->query("SELECT name FROM arcs WHERE id = $arc_filter");
                    if ($arc_name && $rn = $arc_name->fetch_assoc()) echo '— ' . htmlspecialchars($rn['name']);
                } else {
                    echo '— All Chapters';
                }
                ?>
                <span class="chapters-list-count">(<?php echo number_format($total); ?> total)</span>
            </div>
            <div class="chapter-list">
                <?php while ($c = $chapters->fetch_assoc()): ?>
                <a href="<?php echo BASE_URL; ?>chapters/view.php?type=chapter&id=<?php echo $c['id']; ?>" class="chapter-item parchment-card chapters-list-item">
                    <div class="chapters-list-row">
                        <div>
                            <strong class="chapters-list-num">#<?php echo $c['chapter_number']; ?></strong>
                            <span class="chapters-list-name"><?php echo htmlspecialchars($c['title']); ?></span>
                            <span class="chapters-list-saga">— <?php echo htmlspecialchars($c['saga']); ?></span>
                        </div>
                        <div class="chapters-list-meta">
                            <?php if ($c['release_date']): ?><span>📅 <?php echo $c['release_date']; ?></span><?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
            <?php if ($pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?view=chapters&page=<?php echo $i; ?><?php echo $arc_filter ? '&arc='.$arc_filter : ''; ?>" class="<?php echo $i===$page?'active':''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else:
            $where = $arc_filter ? "WHERE arc_id = $arc_filter" : '';
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $per_page = 50;
            $offset = ($page - 1) * $per_page;
            $total = $conn->query("SELECT COUNT(*) as c FROM episodes $where")->fetch_assoc()['c'];
            $pages = ceil($total / $per_page);
            $episodes = $conn->query("SELECT ep.*, a.name as arc_name FROM episodes ep JOIN arcs a ON ep.arc_id=a.id $where ORDER BY ep.episode_number DESC LIMIT $per_page OFFSET $offset");
        ?>
        <div class="chapters-list-section">
            <div class="chapters-list-title">
                🎬 Episode List
                <?php
                if ($arc_filter) {
                    $arc_name = $conn->query("SELECT name FROM arcs WHERE id = $arc_filter");
                    if ($arc_name && $rn = $arc_name->fetch_assoc()) echo '— ' . htmlspecialchars($rn['name']);
                }
                ?>
                <span class="chapters-list-count">(<?php echo number_format($total); ?> total)</span>
            </div>
            <div class="episode-list">
                <?php while ($e = $episodes->fetch_assoc()): ?>
                <a href="<?php echo BASE_URL; ?>chapters/view.php?type=episode&id=<?php echo $e['id']; ?>" class="chapter-item parchment-card chapters-list-item">
                    <div class="chapters-list-row">
                        <div>
                            <strong class="chapters-list-num">#<?php echo $e['episode_number']; ?></strong>
                            <span class="chapters-list-name"><?php echo htmlspecialchars($e['title']); ?></span>
                        </div>
                        <div class="chapters-list-meta">
                            <?php if ($e['air_date']): ?><span>📅 <?php echo $e['air_date']; ?></span><?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
            <?php if ($pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?view=episodes&page=<?php echo $i; ?><?php echo $arc_filter ? '&arc='.$arc_filter : ''; ?>" class="<?php echo $i===$page?'active':''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
