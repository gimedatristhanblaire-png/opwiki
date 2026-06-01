<?php
$page_title = 'Fleet Admiral Control Panel';
$meta_description = 'Marine Headquarters — Administration and moderation command center.';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_rep.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

require_once __DIR__ . '/../includes/header.php';

$admin_name = htmlspecialchars($_SESSION['username'] ?? 'Fleet Admiral');
$user_id = (int)$_SESSION['user_id'];

$admin_data = $conn->query("SELECT id, username, reputation_points, bio, avatar, created_at FROM users WHERE id = $user_id")->fetch_assoc();

$staff_ranks = ['admin' => 'Fleet Admiral', 'moderator' => 'Admiral'];
$staff_rank = $staff_ranks[$_SESSION['user_role']] ?? 'Marine Officer';
$rep_title = get_reputation_title($admin_data['reputation_points'] ?? 0);

$pending_articles = 0;
$pa = $conn->query("SELECT COUNT(*) as c FROM wiki_articles WHERE status='pending'");
if ($pa) $pending_articles = (int)$pa->fetch_assoc()['c'];

$pending_theories = 0;
$pt = $conn->query("SELECT COUNT(*) as c FROM theories WHERE status='pending'");
if ($pt) $pending_theories = (int)$pt->fetch_assoc()['c'];

$total_articles = 0;
$ta = $conn->query("SELECT COUNT(*) as c FROM wiki_articles WHERE status='approved'");
if ($ta) $total_articles = (int)$ta->fetch_assoc()['c'];

$total_theories = 0;
$tt = $conn->query("SELECT COUNT(*) as c FROM theories WHERE status='approved'");
if ($tt) $total_theories = (int)$tt->fetch_assoc()['c'];

$total_users = 0;
$tu = $conn->query("SELECT COUNT(*) as c FROM users");
if ($tu) $total_users = (int)$tu->fetch_assoc()['c'];

$reports_handled = 0;
$rh = $conn->query("SELECT COUNT(*) as c FROM mod_log");
if ($rh) $reports_handled = (int)$rh->fetch_assoc()['c'];

$mod_actions = [];
$mq = $conn->query("SELECT action, created_at FROM mod_log ORDER BY created_at DESC LIMIT 10");
if ($mq) { while ($row = $mq->fetch_assoc()) $mod_actions[] = $row; }

$staff_badges = [
    'fleet_admiral' => ['icon' => '⚓', 'name' => 'Fleet Admiral', 'desc' => 'Highest command authority'],
    'cipher_pol' => ['icon' => '🕵️', 'name' => 'Cipher Pol Elite', 'desc' => 'Intelligence operative status'],
    'archive_guardian' => ['icon' => '📜', 'name' => 'Archive Guardian', 'desc' => 'Protected lore integrity'],
    'lore_overseer' => ['icon' => '👁️', 'name' => 'Lore Overseer', 'desc' => 'Oversees all world records'],
    'justice' => ['icon' => '⚖️', 'name' => 'Absolute Justice', 'desc' => 'Enforced community standards'],
];
$staff_badge = $staff_badges['fleet_admiral'];
$has_mod_actions = count($mod_actions) > 0;
if ($has_mod_actions) $staff_badge = $staff_badges['lore_overseer'];
if ($pending_articles > 0 || $pending_theories > 0) $staff_badge = $staff_badges['cipher_pol'];
?>
<section id="admin-command-center" class="admin-profile-page">
    <div class="container">
        <!-- Marine Command Header -->
        <div class="marine-command-header">
            <div class="marine-emblem">⚓</div>
            <h1 class="marine-command-title">FLEET ADMIRAL<br>CONTROL CENTER</h1>
            <p class="marine-command-sub">Marine Headquarters — Absolute Justice Division</p>
            <div class="marine-rank-strip">
                <span class="marine-rank-badge"><?php echo $staff_rank; ?></span>
                <span class="marine-rank-divider">|</span>
                <span class="marine-rank-name"><?php echo $admin_name; ?></span>
            </div>
        </div>

        <!-- Three Column Layout -->
        <div class="marine-columns">
            <!-- Left: Staff Information -->
            <div class="marine-left">
                <div class="marine-card">
                    <div class="marine-card-header">
                        <span class="marine-card-icon">📋</span>
                        <h3>Staff File</h3>
                    </div>
                    <div class="marine-avatar-area">
                        <div class="marine-avatar-frame">
                            <?php if (!empty($admin_data['avatar'])): ?>
                                <img src="<?php echo BASE_URL . htmlspecialchars($admin_data['avatar']); ?>" alt="Admin Avatar" class="marine-avatar">
                            <?php else: ?>
                                <div class="marine-avatar marine-avatar-placeholder">⚓</div>
                            <?php endif; ?>
                        </div>
                        <div class="marine-avatar-name"><?php echo $admin_name; ?></div>
                        <div class="marine-avatar-rank"><?php echo $staff_rank; ?></div>
                    </div>
                    <div class="marine-info-body">
                        <div class="marine-info-row">
                            <span class="marine-info-label">Rank</span>
                            <span class="marine-info-value"><?php echo $staff_rank; ?></span>
                        </div>
                        <div class="marine-info-row">
                            <span class="marine-info-label">Joined</span>
                            <span class="marine-info-value"><?php echo date('M j, Y', strtotime($admin_data['created_at'] ?? 'now')); ?></span>
                        </div>
                        <div class="marine-info-row">
                            <span class="marine-info-label">Reputation</span>
                            <span class="marine-info-value"><?php echo number_format($admin_data['reputation_points'] ?? 0); ?> pts</span>
                        </div>
                        <div class="marine-info-row">
                            <span class="marine-info-label">Title</span>
                            <span class="marine-info-value"><?php echo $rep_title; ?></span>
                        </div>
                        <div class="marine-info-row">
                            <span class="marine-info-label">Approvals</span>
                            <span class="marine-info-value"><?php echo $total_articles + $total_theories; ?></span>
                        </div>
                        <div class="marine-info-row">
                            <span class="marine-info-label">Mod Actions</span>
                            <span class="marine-info-value"><?php echo $reports_handled; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Center: Moderation Activity -->
            <div class="marine-center">
                <!-- Metrics Row -->
                <div class="marine-metrics">
                    <div class="marine-metric marine-metric-danger">
                        <span class="marine-metric-val"><?php echo $pending_articles; ?></span>
                        <span class="marine-metric-lbl">Pending Articles</span>
                    </div>
                    <div class="marine-metric marine-metric-danger">
                        <span class="marine-metric-val"><?php echo $pending_theories; ?></span>
                        <span class="marine-metric-lbl">Pending Theories</span>
                    </div>
                    <div class="marine-metric marine-metric-gold">
                        <span class="marine-metric-val"><?php echo $total_articles; ?></span>
                        <span class="marine-metric-lbl">Approved Articles</span>
                    </div>
                    <div class="marine-metric marine-metric-gold">
                        <span class="marine-metric-val"><?php echo $total_theories; ?></span>
                        <span class="marine-metric-lbl">Approved Theories</span>
                    </div>
                    <div class="marine-metric">
                        <span class="marine-metric-val"><?php echo $total_users; ?></span>
                        <span class="marine-metric-lbl">Total Users</span>
                    </div>
                    <div class="marine-metric marine-metric-navy">
                        <span class="marine-metric-val"><?php echo $reports_handled; ?></span>
                        <span class="marine-metric-lbl">Reports Handled</span>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="marine-card">
                    <div class="marine-card-header">
                        <span class="marine-card-icon">⚡</span>
                        <h3>Command Actions</h3>
                    </div>
                    <div class="marine-actions">
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="btn">📊 Full Dashboard</a>
                        <a href="<?php echo BASE_URL; ?>lore/manage.php" class="btn">🗂️ Manage Lore</a>
                        <a href="<?php echo BASE_URL; ?>admin/manage_article.php" class="btn">📝 New Article</a>
                        <a href="<?php echo BASE_URL; ?>admin/theories.php" class="btn">🔮 Review Theories</a>
                        <a href="<?php echo BASE_URL; ?>admin/users.php" class="btn">👥 Manage Users</a>
                        <a href="<?php echo BASE_URL; ?>admin/badges.php" class="btn">🏆 Award Badges</a>
                    </div>
                </div>

                <!-- Moderation Log -->
                <div class="marine-card">
                    <div class="marine-card-header">
                        <span class="marine-card-icon">📋</span>
                        <h3>Recent Moderation</h3>
                    </div>
                    <?php if (empty($mod_actions)): ?>
                        <p class="marine-empty">No recent moderation actions.</p>
                    <?php else: ?>
                        <div class="marine-mod-list">
                            <?php foreach ($mod_actions as $m): ?>
                            <div class="marine-mod-item">
                                <span class="marine-mod-action"><?php echo htmlspecialchars($m['action']); ?></span>
                                <span class="marine-mod-date"><?php echo date('M j, Y H:i', strtotime($m['created_at'])); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Staff Achievements -->
            <div class="marine-right">
                <div class="marine-card">
                    <div class="marine-card-header">
                        <span class="marine-card-icon">🎖️</span>
                        <h3>Staff Achievements</h3>
                    </div>
                    <div class="marine-badges">
                        <div class="marine-badge-item marine-badge-active">
                            <span class="marine-badge-icon"><?php echo $staff_badge['icon']; ?></span>
                            <div class="marine-badge-info">
                                <span class="marine-badge-name"><?php echo $staff_badge['name']; ?></span>
                                <span class="marine-badge-desc"><?php echo $staff_badge['desc']; ?></span>
                            </div>
                        </div>
                        <?php foreach ($staff_badges as $key => $badge):
                            if ($badge['name'] === $staff_badge['name']) continue;
                        ?>
                        <div class="marine-badge-item">
                            <span class="marine-badge-icon muted"><?php echo $badge['icon']; ?></span>
                            <div class="marine-badge-info">
                                <span class="marine-badge-name muted"><?php echo $badge['name']; ?></span>
                                <span class="marine-badge-desc"><?php echo $badge['desc']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
