<?php
if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || !is_staff($_SESSION['user_id'], $conn)) {
    header('Location: ' . BASE_URL);
    exit();
}

$page_title = 'Manage Comments';
$message = '';

if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    $comment_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($comment_id && verify_csrf_token($_GET['csrf_token'])) {
        $stmt = $conn->prepare("UPDATE comments SET deleted = 1 WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            $stmt->close();
            $message = "<p style='color:green;'>Comment deleted.</p>";
        }
    } else {
        $message = "<p style='color:red;'>Invalid request.</p>";
    }
}

if (isset($_GET['restore']) && isset($_GET['csrf_token'])) {
    $comment_id = filter_input(INPUT_GET, 'restore', FILTER_VALIDATE_INT);
    if ($comment_id && verify_csrf_token($_GET['csrf_token'])) {
        $stmt = $conn->prepare("UPDATE comments SET deleted = 0 WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            $stmt->close();
            $message = "<p style='color:green;'>Comment restored.</p>";
        }
    } else {
        $message = "<p style='color:red;'>Invalid request.</p>";
    }
}

$show = isset($_GET['show']) && $_GET['show'] === 'deleted' ? 'deleted' : 'active';
$deleted_condition = ($show === 'deleted') ? 'c.deleted = 1' : 'c.deleted = 0';

$comments = [];
$sql = "SELECT c.id, c.content, c.created_at, c.target_type, c.target_id, c.deleted, u.username
        FROM comments c JOIN users u ON c.user_id = u.id
        WHERE $deleted_condition
        ORDER BY c.created_at DESC LIMIT 100";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
}

$csrf_token = generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<section id="admin-comments">
    <div class="container">
        <h2>Manage Comments</h2>
        <?php echo $message; ?>
        <p>
            <a href="<?php echo BASE_URL; ?>admin/comments.php" class="btn <?php echo $show === 'active' ? 'btn-secondary' : ''; ?>">Active Comments</a>
            <a href="<?php echo BASE_URL; ?>admin/comments.php?show=deleted" class="btn <?php echo $show === 'deleted' ? 'btn-secondary' : ''; ?>">Deleted Comments</a>
        </p>
        <?php if (empty($comments)): ?>
            <p>No comments found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Author</th>
                        <th>Content</th>
                        <th>On</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $c): ?>
                        <tr>
                            <td data-label="Author"><?php echo htmlspecialchars($c['username']); ?></td>
                            <td data-label="Content"><?php echo htmlspecialchars(mb_substr($c['content'], 0, 100)) . (mb_strlen($c['content']) > 100 ? '...' : ''); ?></td>
                            <td data-label="On"><?php echo htmlspecialchars(ucfirst($c['target_type'])); ?> #<?php echo $c['target_id']; ?></td>
                            <td data-label="Date"><?php echo date('M j, Y', strtotime($c['created_at'])); ?></td>
                            <td data-label="Action">
                                <?php if ($c['deleted']): ?>
                                    <a href="<?php echo BASE_URL; ?>admin/comments.php?restore=<?php echo $c['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" class="btn-action btn-approve">Restore</a>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>admin/comments.php?delete=<?php echo $c['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" class="btn-action btn-reject" onclick="return confirm('Delete this comment?');">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p><a href="<?php echo BASE_URL; ?>admin/dashboard.php">&laquo; Back to Dashboard</a></p>
    </div>
</section>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
