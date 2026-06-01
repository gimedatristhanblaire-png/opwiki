<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    $_SESSION['redirect_to'] = BASE_URL . 'admin/tags.php';
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

$page_title = 'Manage Tags';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tag'])) {
    $tag_id = filter_input(INPUT_POST, 'tag_id', FILTER_VALIDATE_INT);
    $csrf_token = $_POST['csrf_token'] ?? '';
    if ($tag_id && verify_csrf_token($csrf_token)) {
        $stmt = $conn->prepare("DELETE FROM tags WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $tag_id);
            if ($stmt->execute()) {
                $message = "<p style='color:green;'>Tag deleted successfully.</p>";
            } else {
                $message = "<p style='color:red;'>Error deleting tag.</p>";
            }
            $stmt->close();
        }
    } else {
        $message = "<p style='color:red;'>Invalid request.</p>";
    }
}

$tags = [];
$result = $conn->query("SELECT t.id, t.name, t.slug, COUNT(at.article_id) as article_count FROM tags t LEFT JOIN article_tags at ON t.id = at.tag_id GROUP BY t.id ORDER BY article_count DESC, t.name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
}

$csrf_token = generate_csrf_token();

require_once __DIR__ . '/../includes/header.php';
?>
<section id="admin-tags">
    <div class="container">
        <h2>Manage Tags</h2>
        <?php echo $message; ?>
        <?php if (!empty($tags)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Slug</th>
                        <th>Articles</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tags as $tag): ?>
                        <tr>
                            <td data-label="Tag"><?php echo htmlspecialchars($tag['name']); ?></td>
                            <td data-label="Slug"><?php echo htmlspecialchars($tag['slug']); ?></td>
                            <td data-label="Articles"><?php echo $tag['article_count']; ?></td>
                            <td data-label="Action">
                                <form method="POST" class="form-inline" onsubmit="return confirm('Delete tag &quot;<?php echo htmlspecialchars($tag['name'], ENT_QUOTES); ?>&quot;?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                                    <button type="submit" name="delete_tag" class="btn-action btn-reject">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No tags yet. Tags are created automatically when users add them to articles.</p>
        <?php endif; ?>
        <p><a href="<?php echo BASE_URL; ?>admin/dashboard.php">&laquo; Back to Dashboard</a></p>
    </div>
</section>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
