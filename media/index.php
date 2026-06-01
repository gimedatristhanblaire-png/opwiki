<?php
$page_title = 'Media Manager';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'auth/login.php'); exit();
}

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$per_page = 30;
$offset = ($page - 1) * $per_page;

$total = 0;
$tr = $conn->query("SELECT COUNT(*) as c FROM media");
if ($tr) { $total = $tr->fetch_assoc()['c']; }
$total_pages = ceil($total / $per_page);

$media = [];
$stmt = $conn->prepare("SELECT id, filename, original_name, type, mime, width, height, size, created_at FROM media ORDER BY created_at DESC LIMIT ? OFFSET ?");
if ($stmt) {
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) { $media[] = $row; }
    $stmt->close();
}
?>
<section id="media-manager">
    <div class="container">
        <h2>Media Manager</h2>
        <p>Uploaded images across the site.</p>
        <?php if (empty($media)): ?>
            <p>No media uploaded yet.</p>
        <?php else: ?>
            <div class="media-grid">
                <?php foreach ($media as $m): ?>
                    <div class="media-card">
                        <a href="<?php echo BASE_URL; ?>uploads/<?php echo htmlspecialchars($m['filename']); ?>" target="_blank">
                            <img src="<?php echo BASE_URL; ?>uploads/thumbs/thumb_<?php echo htmlspecialchars(pathinfo($m['filename'], PATHINFO_FILENAME)); ?>.webp" alt="" class="media-thumb" onerror="this.src='<?php echo BASE_URL; ?>uploads/<?php echo htmlspecialchars($m['filename']); ?>'">
                        </a>
                        <div class="media-info">
                            <div class="media-name"><?php echo htmlspecialchars($m['original_name']); ?></div>
                            <div class="media-meta"><?php echo $m['width']; ?>x<?php echo $m['height']; ?> &bull; <?php echo round($m['size']/1024); ?>KB</div>
                            <div class="media-meta"><?php echo date('M j, Y', strtotime($m['created_at'])); ?></div>
                            <div class="media-code-wrap">
                                <input type="text" readonly value='<img src="<?php echo BASE_URL; ?>uploads/<?php echo htmlspecialchars($m['filename']); ?>" alt="">' class="media-code-input" onclick="this.select()">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($total_pages > 1): ?>
                <div class="pagination media-pagination">
                    <?php for ($i=1; $i<=$total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo ($i==$page)?'active':''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <p><a href="<?php echo BASE_URL; ?>admin/dashboard.php">&laquo; Back to Admin</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
