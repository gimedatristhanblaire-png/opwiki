<?php
$page_title = 'Crew Archives — Your Dossier';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_rep.php';
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    $_SESSION['redirect_to'] = BASE_URL . 'user/profile.php';
    header('Location: ' . BASE_URL . 'auth/login.php'); exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

$sql_profile = "SELECT id, username, email, role, created_at, reputation_points, bio, avatar, favorite_character, favorite_arc, spoiler_tolerance FROM users WHERE id = ?";
$stmt_profile = $conn->prepare($sql_profile);
$profile_data = null;
if ($stmt_profile) {
    $stmt_profile->bind_param("i", $user_id);
    $stmt_profile->execute();
    $result_profile = $stmt_profile->get_result();
    if ($result_profile && $result_profile->num_rows === 1) {
        $profile_data = $result_profile->fetch_assoc();
    }
    $stmt_profile->close();
}

if (!$profile_data) {
    $_SESSION = array();
    session_destroy();
    header('Location: ' . BASE_URL . 'auth/login.php'); exit();
}

$rep_title = get_reputation_title($profile_data['reputation_points']);
$rep_class = get_rep_class($profile_data['reputation_points']);
$bounty = number_format($profile_data['reputation_points'] * 1000000);

$pending_theories = 0;
$stmt_pt = $conn->prepare("SELECT COUNT(*) as c FROM theories WHERE user_id = ? AND status = 'pending'");
if ($stmt_pt) { $stmt_pt->bind_param("i", $user_id); $stmt_pt->execute(); $pending_theories = (int)$stmt_pt->get_result()->fetch_assoc()['c']; $stmt_pt->close(); }
$pending_articles = 0;
$stmt_pa = $conn->prepare("SELECT COUNT(*) as c FROM wiki_articles WHERE user_id = ? AND status = 'pending'");
if ($stmt_pa) { $stmt_pa->bind_param("i", $user_id); $stmt_pa->execute(); $pending_articles = (int)$stmt_pa->get_result()->fetch_assoc()['c']; $stmt_pa->close(); }

$article_count = (int)$conn->query("SELECT COUNT(*) as c FROM wiki_articles WHERE user_id = $user_id AND status = 'approved'")->fetch_assoc()['c'];
$theory_count = (int)$conn->query("SELECT COUNT(*) as c FROM theories WHERE user_id = $user_id AND status = 'approved'")->fetch_assoc()['c'];
$total_likes = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM article_likes al JOIN wiki_articles wa ON al.article_id = wa.id WHERE wa.user_id = $user_id");
if ($r) $total_likes += (int)$r->fetch_assoc()['c'];
$r2 = $conn->query("SELECT COUNT(*) as c FROM theory_likes tl JOIN theories t ON tl.theory_id = t.id WHERE t.user_id = $user_id");
if ($r2) $total_likes += (int)$r2->fetch_assoc()['c'];
$comment_count = (int)$conn->query("SELECT COUNT(*) as c FROM comments WHERE user_id = $user_id")->fetch_assoc()['c'];

$is_admin = ($profile_data['role'] === 'admin');
$is_mod = ($profile_data['role'] === 'moderator');
$role_label = $is_admin ? 'Fleet Admiral' : ($is_mod ? 'Marine Captain' : 'Pirate');
?>

<section id="crew-dossier">
    <div class="container">
        <!-- Dossier Header -->
        <div class="dossier-header">
            <div class="dossier-avatar-wrap">
                <?php if ($profile_data['avatar']): ?>
                    <img src="<?php echo BASE_URL . htmlspecialchars($profile_data['avatar']); ?>" alt="" class="dossier-avatar">
                <?php else: ?>
                    <img src="<?php echo BASE_URL; ?>lore/avatar.php?name=<?php echo urlencode($profile_data['username']); ?>&bg=<?php echo $is_admin ? 'C62828' : 'D4A843'; ?>&color=fff&size=180" alt="" class="dossier-avatar">
                <?php endif; ?>
            </div>
            <div class="dossier-id">
                <h1 class="dossier-username"><?php echo htmlspecialchars($profile_data['username']); ?></h1>
                <div class="dossier-title <?php echo $rep_class; ?>"><?php echo htmlspecialchars($rep_title); ?></div>
                <div class="dossier-role"><?php echo $role_label; ?></div>
                <div class="dossier-bounty">฿ <?php echo $bounty; ?></div>
            </div>
            <div class="dossier-stats">
                <div class="dossier-stat"><span class="dossier-stat-num"><?php echo $article_count; ?></span><span class="dossier-stat-lbl">Archives</span></div>
                <div class="dossier-stat"><span class="dossier-stat-num"><?php echo $theory_count; ?></span><span class="dossier-stat-lbl">Theories</span></div>
                <div class="dossier-stat"><span class="dossier-stat-num"><?php echo $total_likes; ?></span><span class="dossier-stat-lbl">Likes</span></div>
                <div class="dossier-stat"><span class="dossier-stat-num"><?php echo $comment_count; ?></span><span class="dossier-stat-lbl">Records</span></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dossier-actions">
            <a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $user_id; ?>" class="dossier-action-btn">👁️ View Public Profile</a>
            <a href="<?php echo BASE_URL; ?>user/edit_profile.php" class="dossier-action-btn dossier-action-primary">✏️ Edit Dossier</a>
            <a href="<?php echo BASE_URL; ?>user/bookmarks.php" class="dossier-action-btn">🔖 My Bookmark</a>
        </div>

        <!-- Pending Content Alert -->
        <?php if ($pending_articles > 0 || $pending_theories > 0): ?>
        <div class="dossier-alert">
            <span class="dossier-alert-icon">⏳</span>
            <span>You have <strong><?php echo $pending_articles + $pending_theories; ?></strong> submission(s) awaiting review.</span>
            <?php if ($pending_articles > 0): ?><a href="<?php echo BASE_URL; ?>wiki/" class="dossier-alert-link"><?php echo $pending_articles; ?> article(s)</a><?php endif; ?>
            <?php if ($pending_theories > 0): ?><?php if ($pending_articles > 0) echo ' | '; ?><a href="<?php echo BASE_URL; ?>theories/" class="dossier-alert-link"><?php echo $pending_theories; ?> theory(ies)</a><?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Info Card -->
        <div class="dossier-card">
            <h3 class="dossier-card-title">📋 Bounty Record</h3>
            <div class="dossier-card-body">
                <div class="dossier-row"><span class="dossier-label">Member Since</span><span class="dossier-value"><?php echo date('F j, Y', strtotime($profile_data['created_at'])); ?></span></div>
                <div class="dossier-row"><span class="dossier-label">Reputation</span><span class="dossier-value"><?php echo number_format($profile_data['reputation_points']); ?> pts</span></div>
                <div class="dossier-row"><span class="dossier-label">Email</span><span class="dossier-value"><?php echo htmlspecialchars($profile_data['email']); ?></span></div>
                <div class="dossier-row"><span class="dossier-label">Role</span><span class="dossier-value"><?php echo $role_label; ?></span></div>
                <div class="dossier-row"><span class="dossier-label">Spoiler Tolerance</span><span class="dossier-value"><?php echo ['None','Mild','Moderate','Ultimate'][min(3, $profile_data['spoiler_tolerance'])]; ?></span></div>
                <?php if ($profile_data['favorite_character']): ?>
                <div class="dossier-row"><span class="dossier-label">Favorite Character</span><span class="dossier-value"><?php echo htmlspecialchars($profile_data['favorite_character']); ?></span></div>
                <?php endif; ?>
                <?php if ($profile_data['favorite_arc']): ?>
                <div class="dossier-row"><span class="dossier-label">Favorite Arc</span><span class="dossier-value"><?php echo htmlspecialchars($profile_data['favorite_arc']); ?></span></div>
                <?php endif; ?>
                <?php if ($profile_data['bio']): ?>
                <div class="dossier-row dossier-row-full"><span class="dossier-label">Bio</span><span class="dossier-value">"<?php echo htmlspecialchars($profile_data['bio']); ?>"</span></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_admin || $is_mod): ?>
        <div class="dossier-card dossier-card-admin">
            <h3 class="dossier-card-title">⚓ Marine Authority Panel</h3>
            <div class="dossier-card-body">
                <div class="dossier-actions" style="margin-top:0;">
                    <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="dossier-action-btn">Marine HQ</a>
                    <a href="<?php echo BASE_URL; ?>admin/pending_articles.php" class="dossier-action-btn">Pending Approvals</a>
                    <a href="<?php echo BASE_URL; ?>admin/theories.php" class="dossier-action-btn">Moderate Theories</a>
                    <a href="<?php echo BASE_URL; ?>admin/users.php" class="dossier-action-btn">Manage Crews</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
