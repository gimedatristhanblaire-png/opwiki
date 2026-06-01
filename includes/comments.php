<?php
$comment_target_type = $comment_target_type ?? null;
$comment_target_id = $comment_target_id ?? null;

if ($comment_target_type && $comment_target_id) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== null) {
            $csrf_token = $_POST['csrf_token'] ?? '';
            $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING);
            if (verify_csrf_token($csrf_token) && !empty(trim($content ?? ''))) {
                $sql = "INSERT INTO comments (target_type, target_id, user_id, content, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("siis", $comment_target_type, $comment_target_id, $_SESSION['user_id'], $content);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    $comments = [];
    $sql = "SELECT c.id, c.content, c.created_at, u.username, u.id as user_id, u.reputation_points FROM comments c JOIN users u ON c.user_id = u.id WHERE c.target_type = ? AND c.target_id = ? AND c.deleted = 0 ORDER BY c.created_at ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $comment_target_type, $comment_target_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $comments[] = $row;
            }
        }
        $stmt->close();
    }

    $comment_csrf = generate_csrf_token();
    $comment_count = count($comments);
?>
<div class="gl-discussions">
    <div class="gl-discussions-header">
        <h3 class="gl-discussions-title">📜 Grand Line Discussions</h3>
        <span class="gl-discussions-count"><?php echo $comment_count; ?> <?php echo $comment_count === 1 ? 'Record' : 'Records'; ?></span>
    </div>

    <?php if (!empty($comments)): ?>
        <?php $ci = 0; foreach ($comments as $c): $ci++; ?>
        <div class="gl-comment<?php echo $ci === 1 ? ' gl-comment-top' : ''; ?>">
            <?php if ($ci === 1 && $comment_count > 1): ?>
            <div class="gl-comment-top-badge">🏆 Top Theory</div>
            <?php endif; ?>
            <div class="gl-comment-inner">
                <div class="gl-comment-avatar-col">
                    <div class="gl-comment-avatar"><?php echo htmlspecialchars(strtoupper($c['username'][0] ?? '?')); ?></div>
                    <div class="gl-comment-rep">฿<?php echo number_format($c['reputation_points'] ?? 0); ?></div>
                </div>
                <div class="gl-comment-main">
                    <div class="gl-comment-meta">
                        <span class="gl-comment-author"><?php echo htmlspecialchars($c['username']); ?></span>
                        <span class="gl-comment-time"><?php echo time_ago($c['created_at']); ?></span>
                    </div>
                    <div class="gl-comment-content">
                        <?php echo nl2br(htmlspecialchars($c['content'])); ?>
                    </div>
                    <div class="gl-comment-actions">
                        <button class="gl-comment-btn" onclick="showToast('⚓ Like recorded in the Grand Line logs!')">🏴‍☠️ Like</button>
                        <button class="gl-comment-btn" onclick="showToast('💬 Reply feature coming soon!')">↩️ Reply</button>
                        <button class="gl-comment-btn" onclick="showToast('📌 Bookmarked to your personal logs.')">🔖 Bookmark</button>
                        <button class="gl-comment-btn gl-comment-btn-report" onclick="showToast('🚩 Reported to Marine HQ.')">⚔️ Report</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="gl-comment-empty">
            <div class="gl-comment-empty-icon">📭</div>
            <p>No records yet in the Grand Line archives. Be the first to document your discovery!</p>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== null): ?>
        <form method="POST" action="" class="gl-comment-form">
            <input type="hidden" name="csrf_token" value="<?php echo $comment_csrf; ?>">
            <div class="gl-comment-form-inner">
                <textarea name="content" rows="4" class="gl-comment-textarea" placeholder="Share your theory with the Grand Line..." required></textarea>
                <button type="submit" name="submit_comment" class="gl-comment-submit">📯 Send to the Archives</button>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php
}
?>
