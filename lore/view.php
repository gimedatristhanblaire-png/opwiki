<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/card_renderer.php';

$type = $_GET['type'] ?? 'characters';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$valid_types = ['characters', 'devil_fruits', 'arcs', 'timeline'];
if (!in_array($type, $valid_types) || !$id) {
    echo "<div class='container'><p>Invalid request.</p></div>";
    require_once __DIR__ . '/../includes/footer.php'; exit();
}

$table = $type;
$stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ?");
$item = null;
if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $r = $stmt->get_result();
    $item = $r->fetch_assoc();
    $stmt->close();
}
if (!$item) {
    echo "<div class='container'><p>Entry not found.</p></div>";
    require_once __DIR__ . '/../includes/footer.php'; exit();
}

$title_field = ($type === 'timeline') ? 'title' : 'name';
$page_title = $item[$title_field] . ' | Lore Detail';
$meta_description = substr(strip_tags($item['description'] ?? ''), 0, 160);

$type_labels = ['characters' => 'Characters', 'devil_fruits' => 'Devil Fruits', 'arcs' => 'Story Arcs', 'timeline' => 'Timeline'];
$emojis = ['characters' => '🏴‍☠️', 'devil_fruits' => '🍎', 'arcs' => '🌊', 'timeline' => '⏳'];

$related_articles = [];
if ($type === 'characters') {
    $name = $item['name'];
    $r = $conn->query("SELECT id, title, slug FROM wiki_articles WHERE status='approved' AND (title LIKE '%" . $conn->real_escape_string($name) . "%' OR content LIKE '%" . $conn->real_escape_string($name) . "%') LIMIT 5");
    if ($r) while ($row = $r->fetch_assoc()) $related_articles[] = $row;
}
$e = function($col) use ($item) { return htmlspecialchars($item[$col] ?? ''); };
?>
<section id="lore-detail-v2" class="lore-section">
    <div class="container">
        <p class="lore-breadcrumb">
            <a href="<?php echo BASE_URL; ?>lore/">🏴‍☠️ Archives</a>
            <span class="crumb-sep">›</span>
            <a href="<?php echo BASE_URL; ?>lore/browse.php?type=<?php echo $type; ?>"><?php echo $emojis[$type] . ' ' . $type_labels[$type]; ?></a>
            <span class="crumb-sep">›</span>
            <span><?php echo htmlspecialchars($item[$title_field]); ?></span>
        </p>

        <div class="lore-detail-layout">
            <!-- Card Column -->
            <div class="lore-detail-card">
                <?php echo render_lore_card($item, $type, $conn); ?>

            </div>

            <!-- Content Column -->
            <div class="lore-detail-content parchment-card">
                <div class="lore-detail-header">
                    <h1><?php echo $e($title_field); ?></h1>
                    <?php if (!empty($item['alias'])): ?><p class="lore-detail-jname">"<?php echo $e('alias'); ?>"</p><?php endif; ?>
                    <?php if (!empty($item['japanese_name'])): ?>
                        <p class="lore-detail-jname"><?php echo $e('japanese_name'); ?>
                        <?php if (!empty($item['romanji'])): ?><span class="lore-detail-romanji">(<?php echo $e('romanji'); ?>)</span><?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($item['description'])): ?>
                    <div class="lore-detail-desc">
                        <h3>📜 Details</h3>
                        <div class="lore-detail-text"><?php echo nl2br($e('description')); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Character Fields -->
                <?php if ($type === 'characters'): ?>
                <div class="lore-detail-stats">
                    <h3>⚔️ Combat Data</h3>
                    <div class="lore-detail-stats-grid">
                        <?php if (!empty($item['position'])): ?><div class="lore-stat-box"><span class="lore-stat-label">🎯 Position</span><span class="lore-stat-value"><?php echo $e('position'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['affiliation'])): ?><div class="lore-stat-box"><span class="lore-stat-label">🏴‍☠️ Affiliation</span><span class="lore-stat-value"><?php echo $e('affiliation'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['bounty'])): ?><div class="lore-stat-box"><span class="lore-stat-label">฿ Bounty</span><span class="lore-stat-value"><?php echo number_format((int)$item['bounty']); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['status'])): ?><div class="lore-stat-box"><span class="lore-stat-label">📊 Status</span><span class="lore-stat-value"><?php echo $e('status'); ?></span></div><?php endif; ?>
                        <?php
                        $df_name = $item['devil_fruit'] ?? '';
                        if (empty($df_name) && !empty($item['devil_fruit_id'])) {
                            $r = $conn->query("SELECT name FROM devil_fruits WHERE id=" . (int)$item['devil_fruit_id']);
                            if ($r && $r->num_rows) $df_name = $r->fetch_assoc()['name'];
                        }
                        if (!empty($df_name)): ?><div class="lore-stat-box"><span class="lore-stat-label">🍎 Devil Fruit</span><span class="lore-stat-value"><?php echo htmlspecialchars($df_name); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['haki_types'])): ?><div class="lore-stat-box"><span class="lore-stat-label">⚡ Haki</span><span class="lore-stat-value"><?php echo $e('haki_types'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['origin'])): ?><div class="lore-stat-box"><span class="lore-stat-label">🌍 Origin</span><span class="lore-stat-value"><?php echo $e('origin'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['first_appearance'])): ?><div class="lore-stat-box"><span class="lore-stat-label">📖 First Appearance</span><span class="lore-stat-value"><?php echo $e('first_appearance'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['height'])): ?><div class="lore-stat-box"><span class="lore-stat-label">📏 Height</span><span class="lore-stat-value"><?php echo $e('height'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['birthday'])): ?><div class="lore-stat-box"><span class="lore-stat-label">🎂 Birthday</span><span class="lore-stat-value"><?php echo $e('birthday'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['danger_level'])): ?><div class="lore-stat-box"><span class="lore-stat-label">⚠️ Danger Level</span><span class="lore-stat-value"><?php echo $e('danger_level'); ?></span></div><?php endif; ?>
                        <?php
                        $debut_name = $item['debut_arc'] ?? '';
                        if (empty($debut_name) && !empty($item['debut_arc_id'])) {
                            $r = $conn->query("SELECT name FROM arcs WHERE id=" . (int)$item['debut_arc_id']);
                            if ($r && $r->num_rows) $debut_name = $r->fetch_assoc()['name'];
                        }
                        if (!empty($debut_name)): ?><div class="lore-stat-box"><span class="lore-stat-label">📜 Debut Arc</span><span class="lore-stat-value"><?php echo htmlspecialchars($debut_name); ?></span></div><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Devil Fruit Fields -->
                <?php if ($type === 'devil_fruits'): ?>
                <div class="lore-detail-stats">
                    <h3>🍎 Fruit Data</h3>
                    <div class="lore-detail-stats-grid">
                        <div class="lore-stat-box"><span class="lore-stat-label">🔖 Type</span><span class="lore-stat-value"><?php echo $e('type'); ?></span></div>
                        <?php
                        $holder_name = $item['current_holder'] ?? '';
                        if (empty($holder_name) && !empty($item['current_holder_id'])) {
                            $r = $conn->query("SELECT name FROM characters WHERE id=" . (int)$item['current_holder_id']);
                            if ($r && $r->num_rows) $holder_name = $r->fetch_assoc()['name'];
                        }
                        if (!empty($holder_name)): ?><div class="lore-stat-box"><span class="lore-stat-label">⚔️ Current Holder</span><span class="lore-stat-value"><?php echo htmlspecialchars($holder_name); ?></span></div><?php endif; ?>
                        <div class="lore-stat-box"><span class="lore-stat-label">🌀 Awakening</span><span class="lore-stat-value"><?php echo $e('awakening'); ?></span></div>
                        <?php if (!empty($item['debut_chapter'])): ?><div class="lore-stat-box"><span class="lore-stat-label">📖 Debut Chapter</span><span class="lore-stat-value"><?php echo $e('debut_chapter'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['strength_level'])): ?><div class="lore-stat-box"><span class="lore-stat-label">💪 Power Level</span><span class="lore-stat-value"><?php echo $e('strength_level'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['weakness'])): ?><div class="lore-stat-box"><span class="lore-stat-label">⚠️ Weakness</span><span class="lore-stat-value"><?php echo $e('weakness'); ?></span></div><?php endif; ?>
                        <div class="lore-stat-box"><span class="lore-stat-label">⚔️ Combat Rating</span><span class="lore-stat-value"><?php echo (int)($item['combat_rating'] ?? 0); ?>/100</span></div>
                        <div class="lore-stat-box"><span class="lore-stat-label">💎 Rarity</span><span class="lore-stat-value"><?php echo (int)($item['rarity_meter'] ?? 0); ?>%</span></div>
                        <?php if (!empty($item['threat_level'])): ?><div class="lore-stat-box"><span class="lore-stat-label">☠️ Threat Level</span><span class="lore-stat-value"><?php echo $e('threat_level'); ?></span></div><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Arc Fields -->
                <?php if ($type === 'arcs'): ?>
                <div class="lore-detail-stats">
                    <h3>⚡ Campaign Report</h3>
                    <div class="lore-detail-stats-grid">
                        <?php if (!empty($item['saga'])): ?><div class="lore-stat-box"><span class="lore-stat-label">🏆 Saga</span><span class="lore-stat-value"><?php echo $e('saga'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['arc_number'])): ?><div class="lore-stat-box"><span class="lore-stat-label">🔢 Arc #</span><span class="lore-stat-value"><?php echo (int)$item['arc_number']; ?></span></div><?php endif; ?>
                        <?php if (!empty($item['chapters'])): ?><div class="lore-stat-box"><span class="lore-stat-label">📖 Chapters</span><span class="lore-stat-value"><?php echo $e('chapters'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['episodes'])): ?><div class="lore-stat-box"><span class="lore-stat-label">🎬 Episodes</span><span class="lore-stat-value"><?php echo $e('episodes'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['location'])): ?><div class="lore-stat-box"><span class="lore-stat-label">📍 Location</span><span class="lore-stat-value"><?php echo $e('location'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['timeline_position'])): ?><div class="lore-stat-box"><span class="lore-stat-label">⏳ Timeline</span><span class="lore-stat-value"><?php echo $e('timeline_position'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['key_villains'])): ?><div class="lore-stat-box"><span class="lore-stat-label">👹 Key Villains</span><span class="lore-stat-value"><?php echo $e('key_villains'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['major_deaths'])): ?><div class="lore-stat-box"><span class="lore-stat-label">💀 Major Deaths</span><span class="lore-stat-value"><?php echo $e('major_deaths'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['major_events'])): ?><div class="lore-stat-box"><span class="lore-stat-label">⚡ Major Events</span><span class="lore-stat-value"><?php echo $e('major_events'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['lore_importance'])): ?><div class="lore-stat-box"><span class="lore-stat-label">🏆 Lore Importance</span><span class="lore-stat-value"><?php echo $e('lore_importance'); ?></span></div><?php endif; ?>
                        <div class="lore-stat-box"><span class="lore-stat-label">🔥 Hype Rating</span><span class="lore-stat-value"><?php echo (int)($item['hype_rating'] ?? 0); ?>/100</span></div>
                        <div class="lore-stat-box"><span class="lore-stat-label">💔 Tragedy</span><span class="lore-stat-value"><?php echo (int)($item['tragedy_meter'] ?? 0); ?>/100</span></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Timeline Fields -->
                <?php if ($type === 'timeline'): ?>
                <div class="lore-detail-stats">
                    <h3>⏳ Event Data</h3>
                    <div class="lore-detail-stats-grid">
                        <?php if (!empty($item['event_date'])): ?><div class="lore-stat-box"><span class="lore-stat-label">📅 Date</span><span class="lore-stat-value"><?php echo $e('event_date'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['importance'])): ?><div class="lore-stat-box"><span class="lore-stat-label">🏆 Importance</span><span class="lore-stat-value"><?php echo $e('importance'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['participants'])): ?><div class="lore-stat-box"><span class="lore-stat-label">👥 Participants</span><span class="lore-stat-value"><?php echo $e('participants'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['canon_status'])): ?><div class="lore-stat-box"><span class="lore-stat-label">✅ Canon Status</span><span class="lore-stat-value"><?php echo $e('canon_status'); ?></span></div><?php endif; ?>
                        <?php if (!empty($item['arc_id'])): $arc_r = $conn->query("SELECT name FROM arcs WHERE id=" . (int)$item['arc_id']); if ($arc_r && $a = $arc_r->fetch_assoc()): ?>
                        <div class="lore-stat-box"><span class="lore-stat-label">🌊 Related Arc</span><span class="lore-stat-value"><?php echo htmlspecialchars($a['name']); ?></span></div>
                        <?php endif; endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($related_articles)): ?>
                <div class="lore-detail-related">
                    <h3>📖 Related Articles</h3>
                    <ul><?php foreach ($related_articles as $a): ?>
                        <li><a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($a['slug']); ?>"><?php echo htmlspecialchars($a['title']); ?></a></li>
                    <?php endforeach; ?></ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
