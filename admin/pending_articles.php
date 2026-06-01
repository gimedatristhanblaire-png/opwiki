<?php
$page_title = 'Manage Pending Articles';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    $_SESSION['redirect_to'] = BASE_URL . 'admin/pending_articles.php';
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? null;
$is_current_user_admin = ($user_role === 'admin');

if (!$is_current_user_admin && $user_role === null && $user_id !== null) {
    $is_current_user_admin = is_admin($user_id, $conn);
}

if (!$is_current_user_admin) {
    header('Location: ' . BASE_URL);
    exit();
}

$pending_articles = [];
$display_message = '';

$sql = "SELECT wa.id, wa.title, wa.slug, wa.category, wa.status, wa.created_at, u.username
        FROM wiki_articles wa
        JOIN users u ON wa.user_id = u.id
        WHERE wa.status IN ('pending', 'rejected')
        ORDER BY wa.status ASC, wa.created_at DESC";
$result = $conn->query($sql);
if ($result === false) {
    error_log("admin/pending_articles.php: Error fetching articles - " . $conn->error);
    $display_message = "<p style='color:red;'>Could not load articles. Please try again later.</p>";
} elseif ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pending_articles[] = $row;
    }
} else {
    $display_message = "<p>No articles are pending review.</p>";
}

$admin_feedback_html = '';
if (isset($_SESSION['admin_feedback'])) {
    $admin_feedback = $_SESSION['admin_feedback'];
    unset($_SESSION['admin_feedback']);
    $parts = explode(':', $admin_feedback, 2);
    if (count($parts) === 2) {
        list($type, $message_text) = $parts;
        $feedback_class = ($type === 'success') ? 'alert-success' : 'alert-error';
        $admin_feedback_html = "<p class='alert {$feedback_class}'><strong>" . ucfirst($type) . ":</strong> " . htmlspecialchars($message_text) . "</p>";
    }
}

$csrf_token = generate_csrf_token();

require_once __DIR__ . '/../includes/header.php';
?>

<section id="admin-pending-articles">
    <div class="container">
        <h2>Manage Articles Pending Review</h2>

        <?php echo $admin_feedback_html; ?>
        <?php echo $display_message; ?>

        <?php if (!empty($pending_articles)): ?>
            <p>Below are the articles submitted by users that require your attention.</p>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Submitted On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_articles as $article): ?>
                        <tr>
                            <td data-label="Title">
                                <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($article['slug']); ?>">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </a>
                            </td>
                            <td data-label="Category"><?php echo htmlspecialchars($article['category']); ?></td>
                            <td data-label="Author"><?php echo htmlspecialchars($article['username']); ?></td>
                            <td data-label="Status" style="font-weight: bold; color: <?php echo ($article['status'] === 'pending' ? 'orange' : 'red'); ?>;">
                                <?php echo ucfirst(htmlspecialchars($article['status'])); ?>
                            </td>
                            <td data-label="Submitted On"><?php echo date('F j, Y H:i', strtotime($article['created_at'])); ?></td>
                            <td data-label="Actions">
                                <a href="<?php echo BASE_URL; ?>admin/manage_article.php?id=<?php echo $article['id']; ?>&action=approve&csrf_token=<?php echo $csrf_token; ?>" class="btn-action btn-approve">Approve</a>
                                <a href="<?php echo BASE_URL; ?>admin/manage_article.php?id=<?php echo $article['id']; ?>&action=reject&csrf_token=<?php echo $csrf_token; ?>" class="btn-action btn-reject">Reject</a>
                                <a href="<?php echo BASE_URL; ?>wiki/submit.php?id=<?php echo $article['id']; ?>" class="btn-action btn-edit">Edit</a>
                                <a href="<?php echo BASE_URL; ?>admin/manage_article.php?id=<?php echo $article['id']; ?>&action=delete&csrf_token=<?php echo $csrf_token; ?>" class="btn-action btn-reject">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
