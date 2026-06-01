<?php
$page_title = 'User Profile';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_rep.php';
require_once __DIR__ . '/../includes/functions_interactive.php';

$profile_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$profile_id) {
    echo '<section><div class="container"><p class="msg-error">Invalid user.</p></div></section>';
    require_once __DIR__ . '/../includes/footer.php'; exit();
}

$user_data = null;
$stmt = $conn->prepare("SELECT id, username, role, reputation_points, bio, avatar, cover_image, favorite_character, favorite_arc, profile_theme, spoiler_tolerance, created_at FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
}
if (!$user_data) {
    echo '<section><div class="container"><p class="msg-error">User not found.</p></div></section>';
    require_once __DIR__ . '/../includes/footer.php'; exit();
}

$page_title = htmlspecialchars($user_data['username']) . ' | User Profile';
$rep_title = get_reputation_title($user_data['reputation_points']);
$rep_class = get_rep_class($user_data['reputation_points']);

$theme = $user_data['profile_theme'] ?? 'pirate';
$theme_class = 'profile-theme-' . $theme;
$is_admin = ($user_data['role'] === 'admin');
$is_mod = ($user_data['role'] === 'moderator');

$crews = [
    0 => ['name' => 'Crewless', 'jolly' => '☠️'],
    50 => ['name' => 'East Blue Pirates', 'jolly' => '⛵'],
    200 => ['name' => 'Grand Line Alliance', 'jolly' => '🌊'],
    500 => ['name' => 'Straw Hat Fleet', 'jolly' => '🍖'],
    1000 => ['name' => 'Straw Hat Pirates', 'jolly' => '🏴‍☠️'],
    2500 => ['name' => 'Worst Generation', 'jolly' => '💥'],
    5000 => ['name' => 'Yonko Crew', 'jolly' => '👑'],
    10000 => ['name' => "Pirate King's Crew", 'jolly' => '👑'],
];
$crew_name = 'Crewless';
$crew_jolly = '☠️';
foreach ($crews as $pts => $c) {
    if ($user_data['reputation_points'] >= $pts) { $crew_name = $c['name']; $crew_jolly = $c['jolly']; }
}

$bounty = number_format($user_data['reputation_points'] * 1000000);

$articles = [];
$stmt_art = $conn->prepare("SELECT id, title, slug, category, updated_at FROM wiki_articles WHERE user_id = ? AND status = 'approved' ORDER BY updated_at DESC LIMIT 20");
if ($stmt_art) {
    $stmt_art->bind_param("i", $profile_id);
    $stmt_art->execute();
    $result_art = $stmt_art->get_result();
    while ($row = $result_art->fetch_assoc()) { $articles[] = $row; }
    $stmt_art->close();
}

$theories = [];
$stmt_th = $conn->prepare("SELECT id, title, slug, created_at FROM theories WHERE user_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 20");
if ($stmt_th) {
    $stmt_th->bind_param("i", $profile_id);
    $stmt_th->execute();
    $result_th = $stmt_th->get_result();
    while ($row = $result_th->fetch_assoc()) { $theories[] = $row; }
    $stmt_th->close();
}

$article_likes = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM article_likes al JOIN wiki_articles wa ON al.article_id = wa.id WHERE wa.user_id = $profile_id");
if ($r) { $article_likes = (int)$r->fetch_assoc()['c']; }
$theory_likes = 0;
$r2 = $conn->query("SELECT COUNT(*) as c FROM theory_likes tl JOIN theories t ON tl.theory_id = t.id WHERE t.user_id = $profile_id");
if ($r2) { $theory_likes = (int)$r2->fetch_assoc()['c']; }

$total_likes = $article_likes + $theory_likes;

$article_count = $conn->query("SELECT COUNT(*) as c FROM wiki_articles WHERE user_id = $profile_id AND status = 'approved'");
$article_count = $article_count ? (int)$article_count->fetch_assoc()['c'] : 0;
$theory_count = $conn->query("SELECT COUNT(*) as c FROM theories WHERE user_id = $profile_id AND status = 'approved'");
$theory_count = $theory_count ? (int)$theory_count->fetch_assoc()['c'] : 0;
$comment_count = $conn->query("SELECT COUNT(*) as c FROM comments WHERE user_id = $profile_id");
$comment_count = $comment_count ? (int)$comment_count->fetch_assoc()['c'] : 0;
$follower_count = get_follower_count($profile_id, $conn);
$following_count = get_following_count($profile_id, $conn);

$badges = [];
$bq = $conn->query("SELECT badge_type, created_at FROM user_badges WHERE user_id = " . (int)$profile_id . " ORDER BY created_at DESC");
if ($bq) { while ($b = $bq->fetch_assoc()) $badges[] = $b; }
$badge_defs = [
    'scholar' => ['icon' => '📚', 'name' => 'Scholar', 'desc' => 'Contributed multiple wiki articles'],
    'haki_master' => ['icon' => '⚡', 'name' => 'Haki Master', 'desc' => 'Highly engaged community member'],
    'theory_king' => ['icon' => '👑', 'name' => 'Theory King', 'desc' => 'Top theory crafter'],
    'lore_historian' => ['icon' => '📜', 'name' => 'Lore Historian', 'desc' => 'Deep lore knowledge'],
    'cipher_pol' => ['icon' => '🕵️', 'name' => 'Cipher Pol', 'desc' => 'Secret intelligence contributor'],
    'yokai_hunter' => ['icon' => '👹', 'name' => 'Yokai Hunter', 'desc' => 'Mythical Zoan expert'],
];

$featured_theory = null;
$top_articles = [];
$ftq = $conn->prepare("SELECT t.id, t.title, t.slug, COALESCE(SUM(CASE WHEN v.vote = 'up' THEN 1 WHEN v.vote = 'down' THEN -1 ELSE 0 END), 0) as score FROM theories t LEFT JOIN theory_votes v ON t.id = v.theory_id WHERE t.user_id = ? GROUP BY t.id HAVING score > 0 ORDER BY score DESC LIMIT 1");
$ftq->bind_param('i', $profile_id);
$ftq->execute();
$ftr = $ftq->get_result();
if ($ftr && $ftr->num_rows) $featured_theory = $ftr->fetch_assoc();
$ftq->close();
$taq = $conn->prepare("SELECT id, title, slug, category, updated_at FROM wiki_articles WHERE user_id = ? AND status = 'approved' ORDER BY updated_at DESC LIMIT 3");
$taq->bind_param('i', $profile_id);
$taq->execute();
$tar = $taq->get_result();
if ($tar) while ($ta = $tar->fetch_assoc()) $top_articles[] = $ta;
$taq->close();

$role_label = 'Pirate';
if ($is_admin) $role_label = 'Fleet Admiral';
elseif ($is_mod) $role_label = 'Marine Captain';
?>
<section id="public-profile" class="profile-section <?php echo $theme_class; ?>">
    <div class="container">
        <?php if ($is_admin): ?>
        <div class="profile-admin-stamp">CONFIDENTIAL</div>
        <?php endif; ?>
        <!-- Wanted Poster Hero -->
        <div class="profile-wanted-hero <?php echo $is_admin ? 'profile-hero-admin' : 'profile-hero-pirate'; ?>">
            <?php if ($is_admin): ?>
            <div class="profile-hero-overlay profile-hero-overlay-admin"></div>
            <?php else: ?>
            <div class="profile-hero-overlay profile-hero-overlay-pirate"></div>
            <?php endif; ?>
            <div class="wanted-border-frame">
                <div class="wanted-stamp-row">
                    <?php if ($is_admin): ?>
                    <span class="wanted-stamp-line wanted-stamp-admin">AUTHORIZED</span>
                    <span class="wanted-stamp-sub">● WORLD GOVERNMENT ●</span>
                    <?php else: ?>
                    <span class="wanted-stamp-line">WANTED</span>
                    <span class="wanted-stamp-sub">● DEAD OR ALIVE ●</span>
                    <?php endif; ?>
                </div>
                <div class="wanted-photo-frame">
                    <?php if ($user_data['avatar']): ?>
                        <img src="<?php echo BASE_URL . htmlspecialchars($user_data['avatar']); ?>" alt="Avatar" class="wanted-photo">
                    <?php else: ?>
                        <img src="<?php echo BASE_URL; ?>lore/avatar.php?name=<?php echo urlencode($user_data['username']); ?>&bg=<?php echo $is_admin ? 'C62828' : '2E7D32'; ?>&color=fff&size=180" alt="Avatar" class="wanted-photo">
                    <?php endif; ?>
                    <div class="wanted-photo-border"></div>
                </div>
                <div class="wanted-info-area">
                    <div class="wanted-bounty-amount">฿ <?php echo $bounty; ?></div>
                    <div class="wanted-bounty-footer">BOUNTY</div>
                </div>
                <div class="wanted-name-tag">
                    <h2 class="wanted-poster-name"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                    <div class="wanted-crew"><?php echo $crew_jolly; ?> <?php echo $crew_name; ?></div>
                    <div class="wanted-title <?php echo $rep_class; ?>"><?php echo htmlspecialchars($rep_title); ?></div>
                    <?php if ($is_admin): ?>
                    <div class="wanted-role-badge wanted-role-admin">⚓ Fleet Admiral</div>
                    <?php elseif ($is_mod): ?>
                    <div class="wanted-role-badge wanted-role-mod">⚔️ Marine Captain</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bio -->
        <?php if ($user_data['bio']): ?>
        <div class="profile-bio-box">
            <span class="profile-bio-icon">💬</span>
            <p>"<?php echo htmlspecialchars($user_data['bio']); ?>"</p>
        </div>
        <?php endif; ?>

        <!-- Animated Stats Row -->
        <div class="profile-stats-row">
            <div class="profile-stat-card">
                <span class="profile-stat-number"><?php echo $article_count; ?></span>
                <span class="profile-stat-label">Articles</span>
            </div>
            <div class="profile-stat-card">
                <span class="profile-stat-number"><?php echo $theory_count; ?></span>
                <span class="profile-stat-label">Theories</span>
            </div>
            <div class="profile-stat-card">
                <span class="profile-stat-number"><?php echo $total_likes; ?></span>
                <span class="profile-stat-label">Likes</span>
            </div>
            <div class="profile-stat-card">
                <span class="profile-stat-number"><?php echo $comment_count; ?></span>
                <span class="profile-stat-label">Comments</span>
            </div>
            <div class="profile-stat-card">
                <span class="profile-stat-number"><?php echo $follower_count; ?></span>
                <span class="profile-stat-label">Followers</span>
            </div>
            <div class="profile-stat-card">
                <span class="profile-stat-number"><?php echo $following_count; ?></span>
                <span class="profile-stat-label">Following</span>
            </div>
        </div>

        <!-- Three Column Layout -->
        <div class="profile-columns">
            <!-- Left: User Information Card -->
            <div class="profile-left">
                <div class="parchment-card profile-info-card">
                    <div class="profile-info-header">
                        <span class="profile-info-icon">📋</span>
                        <h3>Bounty Record</h3>
                    </div>
                    <div class="profile-info-body">
                        <div class="profile-info-row">
                            <span class="profile-info-label">Member Since</span>
                            <span class="profile-info-value"><?php echo date('M j, Y', strtotime($user_data['created_at'])); ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span class="profile-info-label">Reputation</span>
                            <span class="profile-info-value rep-<?php echo $rep_class; ?>"><?php echo number_format($user_data['reputation_points']); ?> pts</span>
                        </div>
                        <div class="profile-info-row">
                            <span class="profile-info-label">Crew</span>
                            <span class="profile-info-value"><?php echo $crew_jolly; ?> <?php echo $crew_name; ?></span>
                        </div>
                        <?php if ($user_data['favorite_character']): ?>
                        <div class="profile-info-row">
                            <span class="profile-info-label">Favorite Character</span>
                            <span class="profile-info-value"><?php echo htmlspecialchars($user_data['favorite_character']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($user_data['favorite_arc']): ?>
                        <div class="profile-info-row">
                            <span class="profile-info-label">Favorite Arc</span>
                            <span class="profile-info-value"><?php echo htmlspecialchars($user_data['favorite_arc']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="profile-info-row">
                            <span class="profile-info-label">Spoiler Tolerance</span>
                            <span class="profile-info-value"><?php echo ['None','Mild','Moderate','Ultimate'][min(3, $user_data['spoiler_tolerance'])]; ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span class="profile-info-label">Crew Emblem</span>
                            <span class="profile-info-value profile-crew-emblem"><?php echo $crew_jolly; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Center: Featured Contributions -->
            <div class="profile-center">
                <!-- Featured Theory -->
                <?php if ($featured_theory): ?>
                <div class="parchment-card profile-feat-card user-stat-gold">
                    <div class="profile-feat-header">
                        <span class="profile-feat-icon">🏆</span>
                        <h3>Featured Investigation</h3>
                    </div>
                    <a href="<?php echo BASE_URL; ?>theories/view.php?slug=<?php echo urlencode($featured_theory['slug']); ?>" class="profile-feat-title"><?php echo htmlspecialchars($featured_theory['title']); ?></a>
                    <div class="profile-feat-score">Score: <strong>+<?php echo (int)$featured_theory['score']; ?></strong></div>
                </div>
                <?php endif; ?>

                <!-- Articles -->
                <div class="parchment-card">
                    <div class="profile-feat-header">
                        <span class="profile-feat-icon">📖</span>
                        <h3>Archives (<?php echo $article_count; ?>)</h3>
                    </div>
                    <?php if (empty($articles)): ?>
                        <p class="profile-empty">No articles yet.</p>
                    <?php else: ?>
                        <?php foreach ($articles as $a): ?>
                        <div class="profile-entry">
                            <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($a['slug']); ?>"><?php echo htmlspecialchars($a['title']); ?></a>
                            <span class="profile-entry-meta"><?php echo htmlspecialchars($a['category']); ?> · <?php echo date('M j, Y', strtotime($a['updated_at'])); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Theories -->
                <div class="parchment-card">
                    <div class="profile-feat-header">
                        <span class="profile-feat-icon">💭</span>
                        <h3>Investigations (<?php echo $theory_count; ?>)</h3>
                    </div>
                    <?php if (empty($theories)): ?>
                        <p class="profile-empty">No theories yet.</p>
                    <?php else: ?>
                        <?php foreach ($theories as $t): ?>
                        <div class="profile-entry">
                            <a href="<?php echo BASE_URL; ?>theories/view.php?slug=<?php echo urlencode($t['slug']); ?>"><?php echo htmlspecialchars($t['title']); ?></a>
                            <span class="profile-entry-meta"><?php echo date('M j, Y', strtotime($t['created_at'])); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Achievements / Badges -->
            <div class="profile-right">
                <div class="parchment-card profile-badge-card">
                    <div class="profile-info-header">
                        <span class="profile-info-icon">🎖️</span>
                        <h3>Achievements</h3>
                    </div>
                    <?php if (empty($badges)): ?>
                        <p class="profile-empty">No badges earned yet. Contribute articles and theories to earn reputation badges!</p>
                    <?php else: ?>
                        <div class="profile-badges-list">
                            <?php foreach ($badges as $b):
                                $def = $badge_defs[$b['badge_type']] ?? ['icon'=>'🎖️','name'=>ucfirst($b['badge_type']),'desc'=>''];
                            ?>
                            <div class="profile-badge-item">
                                <span class="profile-badge-icon"><?php echo $def['icon']; ?></span>
                                <div class="profile-badge-info">
                                    <span class="profile-badge-name"><?php echo htmlspecialchars($def['name']); ?></span>
                                    <span class="profile-badge-desc"><?php echo htmlspecialchars($def['desc']); ?></span>
                                    <span class="profile-badge-date">Earned <?php echo date('M j, Y', strtotime($b['created_at'])); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Follow Button -->
        <div class="profile-follow-area">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $profile_id):
                $am_following = is_following($_SESSION['user_id'], $profile_id, $conn);
            ?>
                <button class="btn <?php echo $am_following ? 'btn-following' : ''; ?>" data-target-user="<?php echo $profile_id; ?>" onclick="toggleFollow(this)" style="<?php echo $am_following ? 'background:var(--danger);color:#fff;border-color:var(--danger);' : ''; ?>">
                    <?php echo $am_following ? '✕ Unfollow' : '➕ Follow Pirate'; ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
