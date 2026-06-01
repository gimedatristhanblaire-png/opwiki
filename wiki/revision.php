<?php
$page_title = 'View Revision';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = BASE_URL . 'wiki/revision.php?id=' . urlencode($_GET['id'] ?? '');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

require_once __DIR__ . '/../includes/header.php';

$revision_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$revision_id) {
    echo '<section><div class="container"><p class="msg-error">Invalid revision ID.</p></div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$is_current_user_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

$sql_revision = "SELECT ar.id, ar.article_id, ar.title, ar.content, ar.created_at, ar.user_id, u.username
                 FROM article_revisions ar
                 JOIN users u ON ar.user_id = u.id
                 WHERE ar.id = ?";
$stmt_revision = $conn->prepare($sql_revision);
$stmt_revision->bind_param("i", $revision_id);
$stmt_revision->execute();
$result_revision = $stmt_revision->get_result();
$revision = $result_revision->fetch_assoc();
$stmt_revision->close();

if (!$revision) {
    echo '<section><div class="container"><p class="msg-error">Revision not found.</p></div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

if (!$is_current_user_admin) {
    $sql_owner = "SELECT user_id FROM wiki_articles WHERE id = ?";
    $stmt_owner = $conn->prepare($sql_owner);
    $stmt_owner->bind_param("i", $revision['article_id']);
    $stmt_owner->execute();
    $result_owner = $stmt_owner->get_result();
    $owner = $result_owner->fetch_assoc();
    $stmt_owner->close();
    if (!$owner || $_SESSION['user_id'] != $owner['user_id']) {
        echo '<section><div class="container"><p class="msg-error">You do not have permission to view this revision.</p></div></section>';
        require_once __DIR__ . '/../includes/footer.php';
        exit();
    }
}
?>
<section id="view-revision">
    <div class="container">
        <h2><?php echo htmlspecialchars($revision['title']); ?></h2>
        <div class="article-meta">
            <p>Saved by <strong><?php echo htmlspecialchars($revision['username']); ?></strong> on <em><?php echo htmlspecialchars(date('F j, Y H:i', strtotime($revision['created_at']))); ?></em></p>
        </div>
        <div class="article-content">
            <?php
            require_once __DIR__ . '/../includes/Parsedown.php';
            $Parsedown = new Parsedown();
            echo $Parsedown->text(htmlspecialchars($revision['content']));
            ?>
        </div>
        <p><a href="<?php echo BASE_URL; ?>wiki/revisions.php?article_id=<?php echo $revision['article_id']; ?>">&laquo; Back to Revision History</a></p>
    </div>
</section>
<?php
require_once __DIR__ . '/../includes/footer.php';
