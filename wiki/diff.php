<?php
$page_title = 'Compare Revisions';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/Parsedown.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = BASE_URL . 'wiki/revisions.php?article_id=' . urlencode($_GET['article_id'] ?? '');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

require_once __DIR__ . '/../includes/header.php';

$from_id = filter_input(INPUT_GET, 'from', FILTER_VALIDATE_INT);
$to_id = filter_input(INPUT_GET, 'to', FILTER_VALIDATE_INT);

if (!$from_id || !$to_id) {
    echo '<section><div class="container"><p class="msg-error">Invalid revision IDs.</p></div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$is_current_user_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

$revisions = [];
$sql = "SELECT ar.id, ar.article_id, ar.title, ar.content, ar.created_at, ar.change_summary, u.username
        FROM article_revisions ar JOIN users u ON ar.user_id = u.id
        WHERE ar.id IN (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $from_id, $to_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $revisions[$row['id']] = $row;
}
$stmt->close();

if (count($revisions) < 2) {
    echo '<section><div class="container"><p class="msg-error">One or both revisions not found.</p></div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$from = $revisions[$from_id];
$to = $revisions[$to_id];

if (!$is_current_user_admin) {
    $stmt_check = $conn->prepare("SELECT user_id FROM wiki_articles WHERE id = ?");
    $stmt_check->bind_param("i", $from['article_id']);
    $stmt_check->execute();
    $owner = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();
    if (!$owner || $_SESSION['user_id'] != $owner['user_id']) {
        echo '<section><div class="container"><p class="msg-error">Permission denied.</p></div></section>';
        require_once __DIR__ . '/../includes/footer.php';
        exit();
    }
}

$Parsedown = new Parsedown();
$from_content = $Parsedown->text($from['content']);
$to_content = $Parsedown->text($to['content']);
?>

<section id="revision-diff">
    <div class="container">
        <h2>Comparing Revisions</h2>
        <p><a href="<?php echo BASE_URL; ?>wiki/revisions.php?article_id=<?php echo $from['article_id']; ?>">&laquo; Back to Revision History</a></p>
        <div class="diff-container">
            <div class="diff-col">
                <h3>Older Revision</h3>
                <p class="diff-meta">by <?php echo htmlspecialchars($from['username']); ?> on <?php echo date('M j, Y H:i', strtotime($from['created_at'])); ?></p>
                <?php if ($from['change_summary']): ?><p class="diff-summary">Summary: <?php echo htmlspecialchars($from['change_summary']); ?></p><?php endif; ?>
                <div class="diff-content"><?php echo $from_content; ?></div>
            </div>
            <div class="diff-col">
                <h3>Newer Revision</h3>
                <p class="diff-meta">by <?php echo htmlspecialchars($to['username']); ?> on <?php echo date('M j, Y H:i', strtotime($to['created_at'])); ?></p>
                <?php if ($to['change_summary']): ?><p class="diff-summary">Summary: <?php echo htmlspecialchars($to['change_summary']); ?></p><?php endif; ?>
                <div class="diff-content"><?php echo $to_content; ?></div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
