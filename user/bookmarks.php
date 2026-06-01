<?php
$page_title = 'My Bookmarks';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_interactive.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = BASE_URL . 'user/bookmarks.php';
    header('Location: ' . BASE_URL . 'auth/login.php'); exit();
}

$user_id = $_SESSION['user_id'];
$bookmarks = get_bookmarks($user_id, $conn);
?>
<section id="user-bookmarks">
    <div class="container">
        <h2>My Bookmarks</h2>
        <?php if (empty($bookmarks)): ?>
            <p>You haven't bookmarked any articles or theories yet.</p>
        <?php else: ?>
            <ul class="bookmark-list">
                <?php foreach ($bookmarks as $b):
                    $url = ($b['type'] === 'article') ? 'wiki/view.php?slug=' . urlencode($b['slug']) : 'theories/view.php?slug=' . urlencode($b['slug']);
                ?>
                    <li class="bookmark-item">
                        <a href="<?php echo BASE_URL . $url; ?>"><?php echo htmlspecialchars($b['title']); ?></a>
                        <small class="bookmark-meta">(<?php echo ucfirst($b['type']); ?>) &bull; Bookmarked <?php echo time_ago($b['bookmarked_at']); ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p><a href="<?php echo BASE_URL; ?>user/profile.php">&laquo; Back to Profile</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
