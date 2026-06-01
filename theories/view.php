<?php
$page_title = 'View Theory';

require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_rep.php';
require_once __DIR__ . '/../includes/functions_interactive.php';

$theory_slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_STRING);
$theory_data = null;
$display_message = '';

$sql = "SELECT t.id, t.title, t.content, t.status, t.created_at, t.updated_at, t.user_id, t.spoiler_level, u.username
        FROM theories t
        JOIN users u ON t.user_id = u.id
        WHERE t.slug = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log("theories/view.php: Error preparing statement: " . $conn->error);
    $display_message = "<p style='color:red;'>An internal error occurred while loading the theory. Please try again later.</p>";
} else {
    $stmt->bind_param("s", $theory_slug);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        error_log("theories/view.php: Error getting result: " . $conn->error);
        $display_message = "<p style='color:red;'>An internal error occurred while loading the theory. Please try again later.</p>";
    } elseif ($result->num_rows === 1) {
        $theory_data = $result->fetch_assoc();
        $page_title = htmlspecialchars($theory_data['title']) . ' | Theory';
        $meta_description = substr(strip_tags($theory_data['content']), 0, 160);
    } else {
        $display_message = "<p>The requested theory could not be found.</p>";
    }
    $stmt->close();
}

$is_author = ($theory_data && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $theory_data['user_id']);
$user_role = $_SESSION['user_role'] ?? null;
$is_admin_viewing = $is_author ? false : ($user_role === 'admin');

if ($theory_data && $theory_data['status'] !== 'approved' && !$is_author && !$is_admin_viewing) {
    $display_message = "<p style='color:red;'>This theory is not yet approved and cannot be viewed. Please check back later or contact an administrator.</p>";
    $theory_data = null;
}

function render_lore_callouts($content) {
    $content = preg_replace_callback('/\[callout\s+type=["\'](trivia|theory|spoiler)["\']\](.*?)\[\/callout\]/is', function($m) {
        $type = strtolower($m[1]);
        $text = trim($m[2]);
        $icons = ['trivia' => '📝', 'theory' => '💭', 'spoiler' => '⚠️'];
        $labels = ['trivia' => 'Trivia', 'theory' => 'Theory', 'spoiler' => 'Spoiler'];
        return '<div class="lore-callout lore-callout-' . $type . '"><div class="lore-callout-title">' . ($icons[$type]??'📌') . ' ' . ($labels[$type]??'Note') . '</div>' . $text . '</div>';
    }, $content);
    return $content;
}

function render_theory_blocks($content) {
    $content = preg_replace_callback('/\[evidence\](.*?)\[\/evidence\]/is', function($m) {
        return '<div class="theory-block theory-block-evidence"><div class="theory-block-title">📜 Evidence</div><div class="theory-block-content">' . trim($m[1]) . '</div></div>';
    }, $content);
    $content = preg_replace_callback('/\[foreshadowing\](.*?)\[\/foreshadowing\]/is', function($m) {
        return '<div class="theory-block theory-block-foreshadowing"><div class="theory-block-title">🔮 Foreshadowing</div><div class="theory-block-content">' . trim($m[1]) . '</div></div>';
    }, $content);
    $content = preg_replace_callback('/\[counter\](.*?)\[\/counter\]/is', function($m) {
        return '<div class="theory-block theory-block-counter"><div class="theory-block-title">⚠️ Counter Argument</div><div class="theory-block-content">' . trim($m[1]) . '</div></div>';
    }, $content);
    $content = preg_replace_callback('/\[contradiction\](.*?)\[\/contradiction\]/is', function($m) {
        return '<div class="theory-block theory-block-counter"><div class="theory-block-title">🚩 Contradiction</div><div class="theory-block-content">' . trim($m[1]) . '</div></div>';
    }, $content);
    $content = preg_replace_callback('/\[timeline\](.*?)\[\/timeline\]/is', function($m) {
        return '<div class="theory-block theory-block-evidence" style="border-left-color:var(--ocean-surface);"><div class="theory-block-title">📅 Timeline Connection</div><div class="theory-block-content">' . trim($m[1]) . '</div></div>';
    }, $content);
    $content = preg_replace_callback('/\[poll\](.*?)\[\/poll\]/is', function($m) {
        return '<div class="theory-block theory-block-manga"><div class="theory-block-title">🗳️ Community Poll</div><div class="theory-block-content">' . trim($m[1]) . '</div></div>';
    }, $content);
    return $content;
}

function render_manga_blocks($content) {
    $content = preg_replace_callback('/\[manga\](.*?)\[\/manga\]/is', function($m) {
        return '<div class="theory-block theory-block-manga"><div class="theory-block-title">📖 SOURCE MATERIAL</div><div class="theory-block-content">' . trim($m[1]) . '</div></div>';
    }, $content);
    return $content;
}

// --- Heat Meter ---
$heat_score = 0;
$heat_level = 'cold';
$heat_label = 'Ice Cold';
$theory_id = $theory_data['id'] ?? 0;
if ($theory_id) {
    $vote_score = get_vote_score($theory_id, $conn);
    $c_count = 0;
    $rc = $conn->query("SELECT COUNT(*) as c FROM comments WHERE target_type='theory' AND target_id=" . $theory_id);
    if ($rc) { $c_row = $rc->fetch_assoc(); $c_count = (int)$c_row['c']; }
    $recency = 0;
    $d = $conn->query("SELECT TIMESTAMPDIFF(DAY, created_at, NOW()) as days FROM theories WHERE id=" . $theory_id);
    if ($d) { $dd = $d->fetch_assoc(); $recency = max(0, 30 - (int)$dd['days']); }
    $heat_score = min(100, max(0, ($vote_score + 50) * 0.4 + $c_count * 5 + $recency * 1.5));
    if ($heat_score >= 70) { $heat_level = 'hot'; $heat_label = '🔥 Blazing Hot'; }
    elseif ($heat_score >= 45) { $heat_level = 'warm'; $heat_label = '⚡ Trending'; }
    elseif ($heat_score >= 20) { $heat_level = 'mild'; $heat_label = '🌤️ Warm'; }
    else { $heat_level = 'cold'; $heat_label = '❄️ Ice Cold'; }
}
?>
<section id="theory-view">
    <div class="container">
        <?php echo $display_message; ?>
        <?php if ($theory_data): ?>
            <div class="theory-file-header">
                <div class="theory-file-badge">CLASSIFIED THEORY FILE #<?php echo str_pad($theory_data['id'], 3, '0', STR_PAD_LEFT); ?></div>
                <div class="theory-file-title"><?php echo htmlspecialchars($theory_data['title']); ?></div>
                <div class="theory-file-meta">
                    <span class="theory-file-meta-item">✍️ <a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $theory_data['user_id']; ?>"><?php echo htmlspecialchars($theory_data['username']); ?></a></span>
                    <span class="theory-file-meta-item">📅 <?php echo date('M j, Y', strtotime($theory_data['created_at'])); ?></span>
                    <span class="theory-file-meta-item">🔥 <span class="heat-<?php echo $heat_level; ?>"><?php echo $heat_label; ?> (<?php echo round($heat_score); ?>%)</span></span>
                    <?php if ($theory_data['updated_at'] != $theory_data['created_at']): ?>
                    <span class="theory-file-meta-item">🔄 Updated <?php echo date('M j, Y', strtotime($theory_data['updated_at'])); ?></span>
                    <?php
                    require_once __DIR__ . '/../includes/functions.php';
                    $edit_ago = time_ago($theory_data['updated_at']);
                    if ($edit_ago !== 'just now'): ?>
                    <span class="theory-file-meta-item"><span class="edit-inline-badge">✏️ Last edited <?php echo $edit_ago; ?></span></span>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($theory_data['status'] !== 'approved'): ?>
                    <span class="theory-file-meta-item" style="color:<?php echo ($theory_data['status']==='pending'?'#FF9800':'#F44336'); ?>;">⚪ <?php echo ucfirst(htmlspecialchars($theory_data['status'])); ?></span>
                    <?php endif; ?>
                </div>
                <?php
                $vote_score = $theory_id ? get_vote_score($theory_id, $conn) : 0;
                $has_admin_edit = false;
                if ($theory_data) {
                    $ae2 = $conn->query("SELECT 1 FROM theories WHERE id=" . $theory_id . " AND status='approved' AND user_id IN (SELECT id FROM users WHERE role='admin') LIMIT 1");
                    if ($ae2 && $ae2->num_rows) $has_admin_edit = true;
                }
                require_once __DIR__ . '/../lore/card_renderer.php';
                ?>
                <div class="theory-meta-actions">
                    <?php echo render_controversy_meter($heat_score); ?>
                    <?php echo render_theory_badge($vote_score, $theory_data['status'] ?? 'pending', $has_admin_edit); ?>
                </div>
            </div>

            <?php
            $user_tolerance = 0;
            if (isset($_SESSION['user_id'])) {
                $tr_tol = $conn->query("SELECT spoiler_tolerance FROM users WHERE id = " . (int)$_SESSION['user_id']);
                if ($tr_tol) { $row_tol = $tr_tol->fetch_assoc(); $user_tolerance = (int)$row_tol['spoiler_tolerance']; }
            }
            $show_spoiler = $theory_data['spoiler_level'] > $user_tolerance;
            ?>
            <div class="article-content theory-content theory-content-v2 <?php echo $show_spoiler ? 'spoiler-blurred' : ''; ?>">
                <?php if ($show_spoiler): ?>
                    <div class="spoiler-overlay">
                        <span class="spoiler-label"><?php echo ['','Mild Spoiler','Major Spoiler','Ultimate Spoiler'][$theory_data['spoiler_level']]; ?></span>
                        <p>This theory contains <?php echo strtolower(['','Mild','Major','Ultimate'][$theory_data['spoiler_level']]); ?> spoilers.</p>
                        <button class="btn spoiler-reveal" onclick="revealSpoiler(this)">Reveal Content</button>
                    </div>
                <?php endif; ?>
                <?php
                require_once __DIR__ . '/../includes/Parsedown.php';
                $Parsedown = new Parsedown();
                $rendered = $Parsedown->text($theory_data['content']);
                $rendered = render_theory_blocks($rendered);
                $rendered = render_manga_blocks($rendered);
                $rendered = render_lore_callouts($rendered);
                preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $rendered, $h2s);
                preg_match_all('/<h3[^>]*>(.*?)<\/h3>/i', $rendered, $h3s);
                $heading_count = 0;
                $headings = [];
                if (!empty($h2s[1]) || !empty($h3s[1])) {
                    foreach ($h2s[1] as $h2) {
                        $heading_count++;
                        $slug = 'th-heading-' . $heading_count;
                        $headings[] = ['level' => 2, 'text' => strip_tags($h2), 'slug' => $slug];
                        $rendered = preg_replace('/<h2>(.*?)<\/h2>/', '<h2 id="' . $slug . '">$1</h2>', $rendered, 1);
                    }
                    foreach ($h3s[1] as $h3) {
                        $heading_count++;
                        $slug = 'th-heading-' . $heading_count;
                        $headings[] = ['level' => 3, 'text' => strip_tags($h3), 'slug' => $slug];
                        $rendered = preg_replace('/<h3>(.*?)<\/h3>/', '<h3 id="' . $slug . '">$1</h3>', $rendered, 1);
                    }
                }
                if (!empty($headings)): ?>
                <div class="toc-float visible" id="floating-toc">
                    <ul>
                        <?php foreach ($headings as $h): ?>
                        <li style="padding-left: <?php echo $h['level'] === 3 ? '12px' : '0'; ?>;"><a href="#<?php echo $h['slug']; ?>"><?php echo htmlspecialchars($h['text']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php echo $rendered; ?>
            </div>

            <?php
            $tag_list = [];
            $stmt_tags = $conn->prepare("SELECT t.name, t.slug FROM tags t JOIN theory_tags tt ON t.id = tt.tag_id WHERE tt.theory_id = ?");
            if ($stmt_tags) {
                $stmt_tags->bind_param("i", $theory_data['id']);
                $stmt_tags->execute();
                $result_tags = $stmt_tags->get_result();
                while ($tag_row = $result_tags->fetch_assoc()) {
                    $tag_list[] = '<a href="' . BASE_URL . 'wiki/tag.php?tag=' . urlencode($tag_row['slug']) . '" class="home-tag">' . htmlspecialchars($tag_row['name']) . '</a>';
                }
                $stmt_tags->close();
            }
            if (!empty($tag_list)): ?>
                <div class="theory-tags theory-tags-section"><?php echo implode(' ', $tag_list); ?></div>
            <?php endif; ?>

            <?php
            $like_count_th = get_like_count('theory', $theory_data['id'], $conn);
            $user_has_liked_th = isset($_SESSION['user_id']) ? user_has_liked('theory', $theory_data['id'], $_SESSION['user_id'], $conn) : false;
            $vote_score_th = get_vote_score($theory_data['id'], $conn);
            $user_vote = isset($_SESSION['user_id']) ? get_user_vote($theory_data['id'], $_SESSION['user_id'], $conn) : null;
            $is_bookmarked_th = isset($_SESSION['user_id']) ? is_bookmarked($_SESSION['user_id'], 'theory', $theory_data['id'], $conn) : false;
            ?>
            <div class="interaction-bar">
                <div class="vote-section">
                    <button class="vote-btn up <?php echo $user_vote === 'up' ? 'active' : ''; ?>" data-theory-id="<?php echo $theory_data['id']; ?>" data-vote="up" onclick="voteTheory(this)">▲</button>
                    <span class="vote-score" id="score-<?php echo $theory_data['id']; ?>"><?php echo $vote_score_th; ?></span>
                    <button class="vote-btn down <?php echo $user_vote === 'down' ? 'active' : ''; ?>" data-theory-id="<?php echo $theory_data['id']; ?>" data-vote="down" onclick="voteTheory(this)">▼</button>
                </div>
                <button class="btn-sm <?php echo $user_has_liked_th ? 'liked' : ''; ?>" data-type="theory" data-id="<?php echo $theory_data['id']; ?>" onclick="toggleLike(this)">
                    <?php echo $user_has_liked_th ? '★' : '☆'; ?> <span class="like-count"><?php echo $like_count_th; ?></span>
                </button>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="btn-sm <?php echo $is_bookmarked_th ? 'bookmarked' : ''; ?>" data-type="theory" data-id="<?php echo $theory_data['id']; ?>" onclick="toggleBookmark(this)">
                        🔖 <span><?php echo $is_bookmarked_th ? 'Bookmarked' : 'Bookmark'; ?></span>
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($is_author): ?>
                <p><a href="<?php echo BASE_URL; ?>theories/submit.php?id=<?php echo $theory_data['id']; ?>" class="btn">Edit Theory</a></p>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <div class="article-admin-panel">
                    <h3>⚙️ Marine Intelligence</h3>
                    <div class="article-admin-actions">
                        <a href="<?php echo BASE_URL; ?>admin/theories.php?approve=<?php echo $theory_data['id']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" class="btn-sm">✅ Approve Archive</a>
                        <a href="<?php echo BASE_URL; ?>admin/theories.php?reject=<?php echo $theory_data['id']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" class="btn-sm btn-danger">❌ Classified Denied</a>
                        <a href="<?php echo BASE_URL; ?>theories/submit.php?id=<?php echo $theory_data['id']; ?>" class="btn-sm">✏️ Revise Record</a>
                        <a href="<?php echo BASE_URL; ?>admin/theories.php?delete=<?php echo $theory_data['id']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" class="btn-sm btn-danger" onclick="return confirm('Erase this record from the archives?');">🗑️ Erase Record</a>
                    </div>
                </div>
            <?php endif; ?>

            <p class="back-link"><a href="<?php echo BASE_URL; ?>theories/" class="btn">&laquo; Back to Theories Index</a></p>

        <?php endif; ?>
    </div>
</section>

<?php
if ($theory_data) {
    $comment_target_type = 'theory';
    $comment_target_id = $theory_data['id'];
    require_once __DIR__ . '/../includes/comments.php';
}
require_once __DIR__ . '/../includes/footer.php';
?>
