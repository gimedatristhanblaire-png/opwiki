<?php
header('Location: lore/');
exit;
$offset = ($page - 1) * $per_page;

$changes = [];

require_once __DIR__ . '/includes/functions.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;
$is_admin = ($user_role === 'admin');
if (!$is_admin && $user_role === null && $user_id !== null) {
    $is_admin = is_admin($user_id, $conn);
}

$theory_filter = $is_admin ? '' : " WHERE t.status = 'approved'";

$sql = "(SELECT 'article' as type, wa.id, wa.title, wa.slug, wa.updated_at as changed_at, wa.created_at, u.username, u.id as author_id, wa.status, wa.spoiler_level, wa.category
         FROM wiki_articles wa JOIN users u ON wa.user_id = u.id)
        UNION ALL
        (SELECT 'theory' as type, t.id, t.title, t.slug, t.updated_at as changed_at, t.created_at, u.username, u.id as author_id, t.status, t.spoiler_level, '' as category
         FROM theories t JOIN users u ON t.user_id = u.id$theory_filter)
        ORDER BY changed_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $changes[] = $row;
    }
    $stmt->close();
}

$total = 0;
$theory_count_sql = $is_admin ? "(SELECT COUNT(*) FROM theories)" : "(SELECT COUNT(*) FROM theories WHERE status='approved')";
$result_count = $conn->query("SELECT (SELECT COUNT(*) FROM wiki_articles) + $theory_count_sql as total");
if ($result_count) {
    $row = $result_count->fetch_assoc();
    $total = $row['total'];
}
$total_pages = ceil($total / $per_page);

?>
<section id="archive-activity-log" class="newspaper-page">
    <div class="newspaper-container">
        <div class="newspaper-masthead">
            <div class="masthead-badge">📜 WORLD GOVERNMENT ARCHIVES</div>
            <h1 class="masthead-title">Archive Activity Log</h1>
            <p class="masthead-sub">Recent edits, submissions, and intelligence reports</p>
            <div class="masthead-line">━━━━━━━━━━━━━━━━━━━━</div>
        </div>

        <?php if (empty($changes)): ?>
            <div class="newspaper-empty">No activity recorded yet. The archives await...</div>
        <?php else: ?>
            <div class="archive-log-list">
                <?php foreach ($changes as $c):
                    $time_tag = time_ago($c['changed_at']);
                    $is_new = (strtotime($c['changed_at']) - strtotime($c['created_at'])) < 60;
                    $type_icon = $c['type'] === 'article' ? '📄' : '💭';
                    $type_label = $c['type'] === 'article' ? 'ARCHIVE ENTRY' : 'THEORY FILE';
                    $category_label = $c['category'] ? ' · ' . htmlspecialchars($c['category']) : '';
                    $spoiler_badge = $c['spoiler_level'] > 0 ? '<span class="edit-badge edit-spoiler">⚠️ SPOILER Lv.' . $c['spoiler_level'] . '</span>' : '';
                    $status_badge = '';
                    if ($c['status'] === 'pending') $status_badge = '<span class="edit-badge edit-pending">⏳ PENDING REVIEW</span>';
                    elseif ($c['status'] === 'rejected') $status_badge = '<span class="edit-badge edit-rejected">❌ DENIED</span>';
                ?>
                <div class="archive-log-entry">
                    <div class="log-entry-icon"><?php echo $type_icon; ?></div>
                    <div class="log-entry-body">
                        <div class="log-entry-header">
                            <span class="log-type-label"><?php echo $type_label; ?></span>
                            <?php if ($is_new): ?><span class="edit-badge edit-new">🆕 NEW</span><?php endif; ?>
                            <?php echo $spoiler_badge; ?>
                            <?php echo $status_badge; ?>
                        </div>
                        <a href="<?php echo BASE_URL . ($c['type'] === 'article' ? 'wiki/view.php?slug=' : 'theories/view.php?slug=') . urlencode($c['slug']); ?>" class="log-entry-title">
                            <?php echo htmlspecialchars($c['title']); ?>
                        </a>
                        <div class="log-entry-meta">
                            <span class="log-author">✍️ <a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $c['author_id']; ?>"><?php echo htmlspecialchars($c['username']); ?></a></span>
                            <span class="log-category"><?php echo $category_label; ?></span>
                            <span class="log-time edit-time-badge">📅 Edited <?php echo $time_tag; ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination changes-spacer">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo BASE_URL; ?>changes.php?page=<?php echo $page - 1; ?>">&laquo; Previous Page</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo BASE_URL; ?>changes.php?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo BASE_URL; ?>changes.php?page=<?php echo $page + 1; ?>">Next Page &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
