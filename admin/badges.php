<?php
$page_title = 'Award Badges';
$meta_description = 'Marine Headquarters — Award reputation badges to pirates.';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    $_SESSION['redirect_to'] = BASE_URL . 'admin/badges.php';
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? null;
if ($user_role !== 'admin') {
    $chk = $conn->query("SELECT role FROM users WHERE id = $user_id");
    if ($chk) { $r = $chk->fetch_assoc(); $user_role = $r['role'] ?? $user_role; }
    if ($user_role !== 'admin') {
        echo '<section><div class="container"><p class="msg-error" style="text-align:center;padding:40px 0;">Access denied. Admiral privileges required.</p></div></section>';
        require_once __DIR__ . '/../includes/footer.php';
        exit();
    }
}

$message = '';
$badge_types = [
    'scholar' => '📚 Scholar',
    'haki_master' => '⚡ Haki Master',
    'theory_king' => '👑 Theory King',
    'lore_historian' => '📜 Lore Historian',
    'cipher_pol' => '🕵️ Cipher Pol',
    'yokai_hunter' => '👹 Yokai Hunter',
];

// Handle actions
$csrf_token = generate_csrf_token();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = '<div class="parchment-card card-error"><p class="msg-error">Invalid security token.</p></div>';
    } else {
        $target_user = filter_input(INPUT_POST, 'target_user', FILTER_VALIDATE_INT);
        if ($_POST['action'] === 'award' && $target_user) {
            $type = preg_replace('/[^a-z_]/', '', $_POST['badge_type'] ?? '');
            if ($type && in_array($type, array_keys($badge_types))) {
                $iq = $conn->prepare("INSERT IGNORE INTO user_badges (user_id, badge_type, awarded_by) VALUES (?, ?, ?)");
                $iq->bind_param('isi', $target_user, $type, $user_id);
                if ($iq->execute() && $iq->affected_rows > 0) {
                    $message = '<div class="parchment-card card-success"><p class="msg-success" style="text-align:center;">✅ Badge awarded!</p></div>';
                } else {
                    $message = '<div class="parchment-card card-warning"><p class="msg-warning" style="text-align:center;">⚠️ Badge already exists or failed.</p></div>';
                }
                $iq->close();
            }
        } elseif ($_POST['action'] === 'remove' && $target_user) {
            $type = preg_replace('/[^a-z_]/', '', $_POST['badge_type'] ?? '');
            if ($type) {
                $dq = $conn->prepare("DELETE FROM user_badges WHERE user_id = ? AND badge_type = ?");
                $dq->bind_param('is', $target_user, $type);
                $dq->execute();
                if ($dq->affected_rows > 0) {
                    $message = '<div class="parchment-card card-warning"><p class="msg-warning" style="text-align:center;">🗑️ Badge removed.</p></div>';
                }
                $dq->close();
            }
        }
    }
}

// Get all users with their badges
$users = [];
$uq = $conn->query("SELECT id, username, role, created_at, reputation_points FROM users ORDER BY username ASC");
if ($uq) while ($u = $uq->fetch_assoc()) {
    $u['badges'] = [];
    $users[$u['id']] = $u;
}
$uq?->close();

$bq = $conn->query("SELECT ub.user_id, ub.badge_type, ub.created_at, a.username as awarded_by_name FROM user_badges ub LEFT JOIN users a ON ub.awarded_by = a.id ORDER BY ub.user_id, ub.badge_type");
if ($bq) while ($b = $bq->fetch_assoc()) {
    if (isset($users[$b['user_id']])) $users[$b['user_id']]['badges'][] = $b;
}
$bq?->close();

require_once __DIR__ . '/../includes/header.php';
?>
<section id="admin-badges" class="admin-profile-page">
    <div class="container">
        <div class="admin-header marine-header">
            <div class="admin-header-badge">⚓ MARINE HEADQUARTERS</div>
            <h1 class="admin-header-title">Award Reputation Badges</h1>
            <p class="admin-header-sub">Bestow honors upon worthy pirates</p>
        </div>

        <?php echo $message; ?>

        <div class="admin-actions-box">
            <h3 class="admin-section-title">🏆 Badge Types</h3>
            <div class="badge-types-list">
                <?php foreach ($badge_types as $k => $v): ?>
                <span class="badge-type-tag"><?php echo $v; ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-card admin-card-scroll">
            <h3 class="admin-section-title">👥 Crew Members</h3>
            <?php if (empty($users)): ?>
                <p class="admin-empty">No users found.</p>
            <?php else: ?>
                <table class="admin-rank-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Rep</th>
                            <th>Current Badges</th>
                            <th>Award / Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></a></td>
                            <td><?php echo htmlspecialchars($u['role']); ?></td>
                            <td><?php echo number_format($u['reputation_points']); ?></td>
                            <td>
                                <?php if (empty($u['badges'])): ?>
                                    <em class="badge-none-text">none</em>
                                <?php else: ?>
                                    <?php foreach ($u['badges'] as $b): ?>
                                    <span class="badge-pill" title="Awarded by <?php echo htmlspecialchars($b['awarded_by_name'] ?? 'unknown'); ?> on <?php echo date('M j, Y', strtotime($b['created_at'])); ?>"><?php echo $badge_types[$b['badge_type']] ?? $b['badge_type']; ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="badge-action-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="target_user" value="<?php echo $u['id']; ?>">
                                    <select name="badge_type" required class="badge-action-select">
                                        <option value="">—</option>
                                        <?php foreach ($badge_types as $k => $v): ?>
                                        <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="action" value="award" class="badge-btn badge-btn-award">+</button>
                                    <button type="submit" name="action" value="remove" class="badge-btn badge-btn-remove" onclick="return confirm('Remove this badge?');">−</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="admin-badge-actions">
            <a href="<?php echo BASE_URL; ?>admin/profile.php" class="btn admin-badge-form">&laquo; Back to Command Center</a>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
