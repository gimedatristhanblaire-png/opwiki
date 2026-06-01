<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    $_SESSION['redirect_to'] = BASE_URL . 'admin/dashboard.php';
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? null;
$is_current_user_admin = ($user_role === 'admin');
$is_current_user_staff = $is_current_user_admin || ($user_role === 'moderator');

if (!$is_current_user_staff && $user_role === null && $user_id !== null) {
    $role = get_user_role($user_id, $conn);
    $is_current_user_admin = ($role === 'admin');
    $is_current_user_staff = in_array($role, ['moderator', 'admin']);
}

if (!$is_current_user_staff) {
    header('Location: ' . BASE_URL);
    exit();
}

$page_title = 'Marine HQ — Admin Command Center';

$total_users = 0;
$total_articles = 0;
$pending_articles = 0;
$total_theories = 0;

$result_users = $conn->query("SELECT COUNT(*) as total FROM users");
if ($result_users && $result_users->num_rows > 0) {
    $total_users = $result_users->fetch_assoc()['total'];
}

$result_articles = $conn->query("SELECT COUNT(*) as total FROM wiki_articles");
if ($result_articles && $result_articles->num_rows > 0) {
    $total_articles = $result_articles->fetch_assoc()['total'];
}

$result_pending = $conn->query("SELECT COUNT(*) as total FROM wiki_articles WHERE status = 'pending'");
if ($result_pending && $result_pending->num_rows > 0) {
    $pending_articles = $result_pending->fetch_assoc()['total'];
}

$result_theories = @$conn->query("SELECT COUNT(*) as total FROM theories");
if ($result_theories && $result_theories->num_rows > 0) {
    $total_theories = $result_theories->fetch_assoc()['total'];
}

$pending_theories = 0;
$result_pending_theories = @$conn->query("SELECT COUNT(*) as total FROM theories WHERE status = 'pending'");
if ($result_pending_theories && $result_pending_theories->num_rows > 0) {
    $pending_theories = $result_pending_theories->fetch_assoc()['total'];
}

$recent_users = $conn->query("SELECT id, username, created_at FROM users ORDER BY created_at DESC LIMIT 5");

require_once __DIR__ . '/../includes/header.php';
?>

<section id="marine-hq">
    <div class="container">
        <!-- Marine HQ Header -->
        <div class="marine-command-header">
            <div class="marine-command-emblem">⚓</div>
            <h1 class="marine-command-title">MARINE HEADQUARTERS</h1>
            <p class="marine-command-sub">Grand Line Administration — <?php echo htmlspecialchars($_SESSION['username'] ?? 'Marine Officer'); ?></p>
        </div>

        <!-- Dossier Stat Cards -->
        <div class="marine-dossier-grid">
            <div class="marine-dossier-card dossier-users">
                <div class="dossier-icon">👥</div>
                <div class="dossier-info">
                    <span class="dossier-label">Crews Registered</span>
                    <span class="dossier-value"><?php echo $total_users; ?></span>
                </div>
            </div>
            <div class="marine-dossier-card dossier-articles">
                <div class="dossier-icon">📜</div>
                <div class="dossier-info">
                    <span class="dossier-label">Archived Reports</span>
                    <span class="dossier-value"><?php echo $total_articles; ?></span>
                </div>
            </div>
            <div class="marine-dossier-card dossier-pending">
                <div class="dossier-icon">⏳</div>
                <div class="dossier-info">
                    <span class="dossier-label">Pending Review</span>
                    <span class="dossier-value"><?php echo $pending_articles + $pending_theories; ?></span>
                </div>
            </div>
            <div class="marine-dossier-card dossier-theories">
                <div class="dossier-icon">💭</div>
                <div class="dossier-info">
                    <span class="dossier-label">Intelligence Reports</span>
                    <span class="dossier-value"><?php echo $total_theories; ?></span>
                </div>
            </div>
        </div>

        <!-- Command Sections -->
        <div class="marine-command-grid">
            <!-- Content Management -->
            <div class="marine-command-section">
                <h3 class="marine-command-heading">📋 Content Management</h3>
                <div class="marine-command-actions">
                    <a href="<?php echo BASE_URL; ?>admin/pending_articles.php" class="marine-btn">
                        <span class="marine-btn-icon">📄</span>
                        <span class="marine-btn-label">Pending Articles</span>
                        <?php if ($pending_articles > 0): ?><span class="marine-badge"><?php echo $pending_articles; ?></span><?php endif; ?>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/theories.php?filter=pending" class="marine-btn">
                        <span class="marine-btn-icon">💭</span>
                        <span class="marine-btn-label">Pending Theories</span>
                        <?php if ($pending_theories > 0): ?><span class="marine-badge"><?php echo $pending_theories; ?></span><?php endif; ?>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/comments.php" class="marine-btn">
                        <span class="marine-btn-icon">💬</span>
                        <span class="marine-btn-label">Manage Comments</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>wiki/submit.php" class="marine-btn marine-btn-action">
                        <span class="marine-btn-icon">✍️</span>
                        <span class="marine-btn-label">New Report</span>
                    </a>
                </div>
            </div>

            <!-- Lore Database -->
            <div class="marine-command-section">
                <h3 class="marine-command-heading">🗺️ Lore Database</h3>
                <div class="marine-command-actions">
                    <a href="<?php echo BASE_URL; ?>lore/manage.php?type=characters" class="marine-btn">
                        <span class="marine-btn-icon">🏴</span>
                        <span class="marine-btn-label">Characters</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>lore/manage.php?type=devil_fruits" class="marine-btn">
                        <span class="marine-btn-icon">🍎</span>
                        <span class="marine-btn-label">Devil Fruits</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>lore/manage.php?type=arcs" class="marine-btn">
                        <span class="marine-btn-icon">🌊</span>
                        <span class="marine-btn-label">Story Arcs</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>lore/manage.php?type=timeline" class="marine-btn">
                        <span class="marine-btn-icon">⏳</span>
                        <span class="marine-btn-label">Timeline</span>
                    </a>
                </div>
            </div>

            <?php if ($is_current_user_admin): ?>
            <!-- Administration -->
            <div class="marine-command-section">
                <h3 class="marine-command-heading">⚙️ Fleet Admiral Powers</h3>
                <div class="marine-command-actions">
                    <a href="<?php echo BASE_URL; ?>admin/categories.php" class="marine-btn">
                        <span class="marine-btn-icon">🏷️</span>
                        <span class="marine-btn-label">Categories</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/tags.php" class="marine-btn">
                        <span class="marine-btn-icon">🔖</span>
                        <span class="marine-btn-label">Tags</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/users.php" class="marine-btn">
                        <span class="marine-btn-icon">👥</span>
                        <span class="marine-btn-label">Users</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/badges.php" class="marine-btn">
                        <span class="marine-btn-icon">🎖️</span>
                        <span class="marine-btn-label">Badges</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/backup.php" class="marine-btn">
                        <span class="marine-btn-icon">💾</span>
                        <span class="marine-btn-label">Backup</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>media/" class="marine-btn">
                        <span class="marine-btn-icon">🖼️</span>
                        <span class="marine-btn-label">Media</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <?php if ($recent_users && $recent_users->num_rows > 0): ?>
        <div class="marine-activity">
            <h3 class="marine-command-heading">🆕 Recent Recruits</h3>
            <div class="marine-activity-list">
                <?php while ($u = $recent_users->fetch_assoc()): ?>
                <div class="marine-activity-item">
                    <span class="marine-activity-avatar"><?php echo htmlspecialchars(strtoupper($u['username'][0] ?? '?')); ?></span>
                    <span class="marine-activity-name"><?php echo htmlspecialchars($u['username']); ?></span>
                    <span class="marine-activity-time"><?php echo time_ago($u['created_at']); ?></span>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
