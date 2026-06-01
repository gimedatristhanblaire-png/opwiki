<?php
$page_title = 'Leaderboard';
$meta_description = 'Top theorists, contributors, and most liked content on OPWiki.';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_rep.php';

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'contributors';

$top_users = [];
if ($tab === 'contributors') {
    $result = $conn->query("SELECT id, username, reputation_points, avatar, bio FROM users ORDER BY reputation_points DESC LIMIT 50");
    if ($result) { while ($row = $result->fetch_assoc()) { $top_users[] = $row; } }
} elseif ($tab === 'theorists') {
    $result = $conn->query("SELECT u.id, u.username, u.reputation_points, u.avatar, u.bio, COUNT(t.id) as theory_count FROM users u JOIN theories t ON u.id = t.user_id WHERE t.status='approved' GROUP BY u.id ORDER BY theory_count DESC LIMIT 50");
    if ($result) { while ($row = $result->fetch_assoc()) { $top_users[] = $row; } }
} elseif ($tab === 'articles') {
    $result = $conn->query("SELECT wa.id, wa.title, wa.slug, COUNT(al.id) as likes FROM wiki_articles wa LEFT JOIN article_likes al ON wa.id = al.article_id WHERE wa.status='approved' GROUP BY wa.id ORDER BY likes DESC LIMIT 20");
    if ($result) { while ($row = $result->fetch_assoc()) { $top_users[] = $row; } }
} elseif ($tab === 'theories') {
    $result = $conn->query("SELECT t.id, t.title, t.slug, COUNT(tl.id) as likes FROM theories t LEFT JOIN theory_likes tl ON t.id = tl.theory_id WHERE t.status='approved' GROUP BY t.id ORDER BY likes DESC LIMIT 20");
    if ($result) { while ($row = $result->fetch_assoc()) { $top_users[] = $row; } }
}
?>
<section id="leaderboard">
    <div class="container">
        <h2>🏆 Leaderboard</h2>
        <p class="tab-nav">
            <a href="<?php echo BASE_URL; ?>leaderboard/?tab=contributors" class="btn <?php echo $tab==='contributors'?'btn-secondary':'';?>">Top Contributors</a>
            <a href="<?php echo BASE_URL; ?>leaderboard/?tab=theorists" class="btn <?php echo $tab==='theorists'?'btn-secondary':'';?>">Top Theorists</a>
            <a href="<?php echo BASE_URL; ?>leaderboard/?tab=articles" class="btn <?php echo $tab==='articles'?'btn-secondary':'';?>">Most Liked Articles</a>
            <a href="<?php echo BASE_URL; ?>leaderboard/?tab=theories" class="btn <?php echo $tab==='theories'?'btn-secondary':'';?>">Most Liked Theories</a>
        </p>
        <?php if (empty($top_users)): ?><p>No data yet.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>#</th><th>User / Title</th><th><?php echo in_array($tab,['articles','theories']) ? 'Likes' : 'Reputation / Count'; ?></th><th>Title</th></tr></thead>
                <tbody>
                <?php $rank = 1; foreach ($top_users as $u): ?>
                    <tr>
                        <td data-label="#"><?php echo $rank++; ?></td>
                        <td data-label="User">
                            <?php if (in_array($tab,['articles','theories'])): ?>
                                <a href="<?php echo BASE_URL; ?><?php echo $tab; ?>/view.php?slug=<?php echo urlencode($u['slug']); ?>"><?php echo htmlspecialchars($u['title']); ?></a>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></a>
                            <?php endif; ?>
                        </td>
                        <td data-label="Score"><?php echo $tab==='theorists' ? $u['theory_count'] : $u['likes'] ?? $u['reputation_points']; ?></td>
                        <td data-label="Title">
                            <?php if (!in_array($tab,['articles','theories'])): ?>
                                <span class="<?php echo get_rep_class($u['reputation_points']); ?>"><?php echo get_reputation_title($u['reputation_points']); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <div class="leaderboard-tiers-card">
            <h3 class="leaderboard-tiers-title">Reputation Tiers</h3>
            <div class="leaderboard-tiers-grid">
                <?php
                $tiers = [
                    [50, 'Cabin Boy', 'rep-cabin', '⚓'],
                    [200, 'Pirate', 'rep-pirate', '🏴‍☠️'],
                    [500, 'Supernova', 'rep-supernova', '⭐'],
                    [1000, 'Warlord', 'rep-warlord', '⚔️'],
                    [2500, 'Yonko Cmdr', 'rep-commander', '🛡️'],
                    [5000, 'Yonko', 'rep-yonko', '👑'],
                    [10000, 'Pirate King', 'rep-pk', '🏆'],
                ];
                foreach ($tiers as $t):
                ?>
                    <div class="leaderboard-tier-item">
                        <span class="leaderboard-tier-icon"><?php echo $t[3]; ?></span>
                        <div class="<?php echo $t[2]; ?> leaderboard-tier-name"><?php echo $t[1]; ?></div>
                        <small class="leaderboard-tier-rep"><?php echo number_format($t[0]); ?> rep</small>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="leaderboard-tier-info">Earn rep: +20 per approved article, +30 per theory, +1/+2 per like received.</p>
        </div>
        <p><a href="<?php echo BASE_URL; ?>index.php">&laquo; Back to Home</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
