<?php
$page_title = 'Revision History';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = BASE_URL . 'wiki/revisions.php?article_id=' . urlencode($_GET['article_id'] ?? '');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

require_once __DIR__ . '/../includes/header.php';

$article_id = filter_input(INPUT_GET, 'article_id', FILTER_VALIDATE_INT);

if (!$article_id) {
    echo '<section><div class="container"><p class="msg-error">Invalid article ID.</p></div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$is_current_user_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Handle admin restore
$restore_msg = '';
if ($is_current_user_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_revision'])) {
    $restore_id = filter_input(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT);
    if ($restore_id) {
        $rq = $conn->prepare("SELECT article_id, title, content FROM article_revisions WHERE id = ?");
        $rq->bind_param('i', $restore_id);
        $rq->execute();
        $rr = $rq->get_result()->fetch_assoc();
        $rq->close();
        if ($rr && (int)$rr['article_id'] === $article_id) {
            $uq = $conn->prepare("UPDATE wiki_articles SET title = ?, content = ?, updated_at = NOW(), last_edited_by = ? WHERE id = ?");
            $uid = (int)$_SESSION['user_id'];
            $uq->bind_param('ssii', $rr['title'], $rr['content'], $uid, $article_id);
            if ($uq->execute()) {
                $restore_msg = '<div class="parchment-card card-success"><p class="msg-success" style="text-align:center;">☑️ Revision #' . $restore_id . ' restored successfully.</p></div>';
                // Log the restore as a new revision
                $log = $conn->prepare("INSERT INTO article_revisions (article_id, user_id, title, content, change_summary) VALUES (?, ?, ?, ?, 'Admin restore from revision #" . $restore_id . "')");
                $log->bind_param('iiss', $article_id, $uid, $rr['title'], $rr['content']);
                $log->execute();
                $log->close();
            }
            $uq->close();
        }
    }
    if (!$restore_msg) $restore_msg = '<div class="parchment-card card-error" style="margin-bottom:16px;"><p class="msg-error" style="text-align:center;">❌ Restore failed.</p></div>';
}

$sql_article = "SELECT user_id, title, slug FROM wiki_articles WHERE id = ?";
$stmt_article = $conn->prepare($sql_article);
$stmt_article->bind_param("i", $article_id);
$stmt_article->execute();
$result_article = $stmt_article->get_result();
$article = $result_article->fetch_assoc();
$stmt_article->close();

if (!$article) {
    echo '<section><div class="container"><p class="msg-error">Article not found.</p></div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

if (!$is_current_user_admin && (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $article['user_id'])) {
    echo '<section><div class="container"><p class="msg-error">You do not have permission to view this revision history.</p></div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$sql_revisions = "SELECT ar.id, ar.title, ar.created_at, ar.change_summary, u.username
                  FROM article_revisions ar
                  JOIN users u ON ar.user_id = u.id
                  WHERE ar.article_id = ?
                  ORDER BY ar.created_at DESC";
$stmt_revisions = $conn->prepare($sql_revisions);
$stmt_revisions->bind_param("i", $article_id);
$stmt_revisions->execute();
$result_revisions = $stmt_revisions->get_result();
?>
<section id="revision-history">
    <div class="container">
        <h2>Revision History: <?php echo htmlspecialchars($article['title']); ?></h2>
        <p><a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($article['slug']); ?>">&laquo; Back to Article</a></p>
        <?php echo $restore_msg; ?>
        <?php if ($result_revisions->num_rows === 0): ?>
            <p>No revisions found.</p>
        <?php else: ?>
            <?php $revisions_data = []; while ($rev = $result_revisions->fetch_assoc()) { $revisions_data[] = $rev; } ?>
            <form id="diff-form" method="GET" action="<?php echo BASE_URL; ?>wiki/diff.php">
                <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
                <table>
                    <thead>
                        <tr>
                            <th>Compare</th>
                            <th>Revision #</th>
                            <th>Date</th>
                            <th>Title</th>
                            <th>User</th>
                            <th>Summary</th>
                            <th>View</th>
                            <?php if ($is_current_user_admin): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = count($revisions_data); ?>
                        <?php foreach ($revisions_data as $revision): ?>
                            <tr>
                                <td data-label="Compare"><input type="checkbox" name="rid[]" value="<?php echo $revision['id']; ?>" class="diff-checkbox"></td>
                                <td data-label="Revision #"><?php echo $count--; ?></td>
                                <td data-label="Date"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($revision['created_at']))); ?></td>
                                <td data-label="Title"><?php echo htmlspecialchars(mb_substr($revision['title'], 0, 60)) . (mb_strlen($revision['title']) > 60 ? '...' : ''); ?></td>
                                <td data-label="User"><?php echo htmlspecialchars($revision['username']); ?></td>
                                <td data-label="Summary"><?php echo $revision['change_summary'] ? htmlspecialchars($revision['change_summary']) : '<em>none</em>'; ?></td>
                                <td data-label="View"><a href="<?php echo BASE_URL; ?>wiki/revision.php?id=<?php echo $revision['id']; ?>">View</a></td>
                                <?php if ($is_current_user_admin): ?>
                                <td data-label="Actions">
                                    <form method="POST" class="form-inline" onsubmit="return confirm('Restore this revision? The current article will be overwritten.');">
                                        <input type="hidden" name="revision_id" value="<?php echo $revision['id']; ?>">
                                        <button type="submit" name="restore_revision" class="btn-sm revision-compare-btn">Restore</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="submit" class="btn" id="compare-btn" disabled>Compare Selected</button></p>
            </form>
            <script>
            (function() {
                var checkboxes = document.querySelectorAll('.diff-checkbox');
                var btn = document.getElementById('compare-btn');
                if (!checkboxes.length || !btn) return;
                function update() {
                    var checked = [];
                    checkboxes.forEach(function(c) { if (c.checked) checked.push(c); });
                    btn.disabled = checked.length !== 2;
                }
                checkboxes.forEach(function(c) { c.addEventListener('change', update); });
                btn.addEventListener('click', function(e) {
                    var checked = [];
                    document.querySelectorAll('.diff-checkbox:checked').forEach(function(c) { checked.push(c.value); });
                    if (checked.length === 2) {
                        e.preventDefault();
                        window.location.href = '<?php echo BASE_URL; ?>wiki/diff.php?from=' + checked[0] + '&to=' + checked[1];
                    }
                });
            })();
            </script>
        <?php endif; ?>
    </div>
</section>
<?php
require_once __DIR__ . '/../includes/footer.php';
