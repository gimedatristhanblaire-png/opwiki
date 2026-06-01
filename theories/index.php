<?php
$page_title = 'CLASSIFIED THEORY DATABASE';
$meta_description = 'Investigate the hidden truths of the Grand Line — community theories, evidence files, and classified speculation.';

require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$submission_message = '';
if (isset($_SESSION['submission_message'])) {
    $submission_message = $_SESSION['submission_message'];
    unset($_SESSION['submission_message']);
}

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;
$is_current_user_admin = ($user_role === 'admin');
if (!$is_current_user_admin && $user_role === null && $user_id !== null) {
    $is_current_user_admin = is_admin($user_id, $conn);
}

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
if (!$page || $page < 1) $page = 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Theories don't have a native category column; categories map to theory tags.
// If a category is selected, filter theories having a tag matching the category name.
$theories = [];
$display_message = '';

// --- Count query ---
$count_joins = "FROM theories t JOIN users u ON t.user_id = u.id";
$count_where = "WHERE 1=1";

if (!$is_current_user_admin) {
    $count_where .= " AND t.status = 'approved'";
}

$count_params = [];
$count_types = '';

if (!empty($selected_category)) {
    $count_joins .= " JOIN theory_tags tt_filter ON t.id = tt_filter.theory_id JOIN tags tg_filter ON tt_filter.tag_id = tg_filter.id";
    $count_where .= " AND tg_filter.slug = ?";
    $count_params[] = $selected_category;
    $count_types .= 's';
}

$sql_count = "SELECT COUNT(DISTINCT t.id) AS total $count_joins $count_where";
$stmt_count = $conn->prepare($sql_count);
if ($stmt_count) {
    if (!empty($count_params)) {
        $stmt_count->bind_param($count_types, ...$count_params);
    }
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row_total = $result_count ? $result_count->fetch_assoc() : null;
    $total_theories = isset($row_total['total']) ? (int)$row_total['total'] : 0;
    $stmt_count->close();
} else {
    $total_theories = 0;
}

$total_pages = max(1, ceil($total_theories / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// --- Data query ---
$select_cols = "t.id, t.title, t.slug, t.content, t.created_at, t.updated_at, t.user_id, t.spoiler_level, u.username";
if ($is_current_user_admin) {
    $select_cols .= ", t.status";
}
$select_cols .= ",
    (SELECT COUNT(*) FROM theory_likes WHERE theory_id = t.id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE target_type = 'theory' AND target_id = t.id) as comment_count,
    (SELECT COALESCE(SUM(CASE WHEN vote = 'up' THEN 1 WHEN vote = 'down' THEN -1 ELSE 0 END), 0) FROM theory_votes WHERE theory_id = t.id) as vote_score";

$data_joins = "FROM theories t JOIN users u ON t.user_id = u.id";
$data_where = "WHERE 1=1";

if (!$is_current_user_admin) {
    $data_where .= " AND t.status = 'approved'";
}

$data_params = [];
$data_types = '';

if (!empty($selected_category)) {
    $data_joins .= " JOIN theory_tags tt_filter ON t.id = tt_filter.theory_id JOIN tags tg_filter ON tt_filter.tag_id = tg_filter.id";
    $data_where .= " AND tg_filter.slug = ?";
    $data_params[] = $selected_category;
    $data_types .= 's';
}

$order_by = $is_current_user_admin ? "ORDER BY t.status ASC, t.created_at DESC" : "ORDER BY t.created_at DESC";

$sql = "SELECT DISTINCT $select_cols $data_joins $data_where $order_by LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log("theories/index.php: Error preparing statement: " . $conn->error);
    $display_message = "<p style='color:red;'>Could not load theories. Please try again later.</p>";
} else {
    $bind_params = array_merge($data_params, [$per_page, $offset]);
    $bind_types = $data_types . 'ii';
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        error_log("theories/index.php: Error fetching theories: " . $conn->error);
        $display_message = "<p style='color:red;'>Could not load theories. Please try again later.</p>";
    } elseif ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $theories[] = $row;
        }
    } else {
        $display_message = "<p>No investigation files match your query.</p>";
    }
    $stmt->close();
}

// --- Pending authored theories ---
$authored_pending = [];
if ($user_id !== null) {
    $stmt_ap = $conn->prepare("SELECT id, title, slug, status FROM theories WHERE user_id = ? AND status != 'approved' ORDER BY created_at DESC");
    if ($stmt_ap) {
        $stmt_ap->bind_param("i", $user_id);
        $stmt_ap->execute();
        $result_ap = $stmt_ap->get_result();
        while ($row_ap = $result_ap->fetch_assoc()) {
            $authored_pending[] = $row_ap;
        }
        $stmt_ap->close();
    }
}

// --- Available categories (from tags used on theories) ---
$theory_categories = [];
$cat_sql = "SELECT DISTINCT tg.slug, tg.name FROM tags tg JOIN theory_tags tt ON tg.id = tt.tag_id ORDER BY tg.name";
$cat_result = $conn->query($cat_sql);
if ($cat_result && $cat_result->num_rows > 0) {
    while ($cat_row = $cat_result->fetch_assoc()) {
        $theory_categories[] = $cat_row;
    }
}

function get_excerpt($content, $length = 200) {
    $text = strip_tags($content);
    if (mb_strlen($text) <= $length) return $text;
    $excerpt = mb_substr($text, 0, $length);
    $last_space = mb_strrpos($excerpt, ' ');
    if ($last_space !== false) {
        $excerpt = mb_substr($excerpt, 0, $last_space);
    }
    return $excerpt . '...';
}

function get_controversy_pct($vote_score, $comment_count) {
    $base = abs($vote_score) * 3 + $comment_count * 5;
    return min(99, max(0, $base));
}
?>
<section class="theory-hero">
    <div class="container">
        <h1 class="theory-hero-title">CLASSIFIED THEORY DATABASE</h1>
        <p class="theory-hero-sub">Uncover the hidden truths of the Grand Line.</p>
        <?php if ($submission_message): ?>
            <div class="theory-hero-message"><?php echo $submission_message; ?></div>
        <?php endif; ?>
    </div>
</section>

<section id="theories-list">
    <div class="container">

        <div class="theory-filters">
            <a href="<?php echo BASE_URL; ?>theories/" class="theory-filter-btn <?php echo empty($selected_category) ? 'active' : ''; ?>">ALL INVESTIGATIONS</a>
            <?php foreach ($theory_categories as $cat): ?>
                <?php
                $cat_active = ($selected_category === $cat['slug']) ? 'active' : '';
                $cat_label = strtoupper($cat['name']);
                ?>
                <a href="<?php echo BASE_URL; ?>theories/?category=<?php echo urlencode($cat['slug']); ?>" class="theory-filter-btn <?php echo $cat_active; ?>"><?php echo htmlspecialchars($cat_label); ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($user_id !== null): ?>
            <div class="theory-submit-row">
                <a href="<?php echo BASE_URL; ?>theories/submit.php" class="btn">Submit New Investigation</a>
            </div>
        <?php else: ?>
            <div class="theory-submit-row">
                <p>Please <a href="<?php echo BASE_URL; ?>auth/login.php">login</a> to submit an investigation.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($authored_pending)): ?>
            <div class="user-authored-articles">
                <h3>Your Investigations Awaiting Review</h3>
                <ul>
                    <?php foreach ($authored_pending as $t): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>theories/view.php?slug=<?php echo urlencode($t['slug']); ?>"><?php echo htmlspecialchars($t['title']); ?></a>
                            <span style="font-weight:bold;color:<?php echo ($t['status']==='pending'?'orange':'red');?>;">[<?php echo ucfirst($t['status']); ?>]</span>
                            <a href="<?php echo BASE_URL; ?>theories/submit.php?id=<?php echo $t['id']; ?>" class="btn-edit-small">Edit</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($theories)): ?>
            <div class="theory-grid">
                <?php foreach ($theories as $theory):
                    $evidence_score = (int)($theory['vote_score'] ?? 0);
                    $comment_count = (int)($theory['comment_count'] ?? 0);
                    $like_count = (int)($theory['like_count'] ?? 0);
                    $controversy_pct = get_controversy_pct($evidence_score, $comment_count);
                    $spoiler_level = (int)($theory['spoiler_level'] ?? 0);
                    $spoiler_labels = ['', 'mild', 'major', 'ultimate'];
                    $spoiler_class = $spoiler_labels[$spoiler_level] ?? '';
                    $spoiler_text = ['', 'Mild Spoiler', 'Major Spoiler', 'Ultimate Spoiler'][$spoiler_level] ?? '';
                    $excerpt = get_excerpt($theory['content'] ?? '');
                    $status_badge = '';
                    $status_color = '';
                    if ($is_current_user_admin && isset($theory['status'])) {
                        switch ($theory['status']) {
                            case 'pending': $status_badge = 'PENDING'; $status_color = '#FF9800'; break;
                            case 'approved': $status_badge = 'APPROVED'; $status_color = '#4CAF50'; break;
                            case 'rejected': $status_badge = 'REJECTED'; $status_color = '#F44336'; break;
                        }
                    }
                ?>
                    <div class="theory-card">
                        <div class="theory-card-top">
                            <span class="theory-card-id">CLASSIFIED THEORY FILE #<?php echo str_pad($theory['id'], 3, '0', STR_PAD_LEFT); ?></span>
                            <?php if ($status_badge): ?>
                                <span class="theory-card-status" style="color:<?php echo $status_color; ?>;font-weight:bold;font-size:0.7rem;margin-left:auto;">[<?php echo $status_badge; ?>]</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="theory-card-title">
                            <a href="<?php echo BASE_URL; ?>theories/view.php?slug=<?php echo urlencode($theory['slug']); ?>">
                                <?php echo htmlspecialchars($theory['title']); ?>
                            </a>
                        </h3>
                        <p class="theory-card-excerpt"><?php echo htmlspecialchars($excerpt); ?></p>
                        <div class="theory-card-meta">
                            <span class="theory-meta-evidence">📜 Evidence: <?php echo $evidence_score; ?></span>
                            <span class="theory-meta-controversy">🔥 Controversy: <?php echo $controversy_pct; ?>%</span>
                            <span class="theory-meta-likes">★ <?php echo $like_count; ?></span>
                            <span class="theory-meta-comments">💬 <?php echo $comment_count; ?></span>
                            <?php if ($spoiler_level > 0): ?>
                                <span class="theory-spoiler-badge <?php echo $spoiler_class; ?>"><?php echo $spoiler_text; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="theory-card-author">
                            <span>by <a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $theory['user_id']; ?>"><?php echo htmlspecialchars($theory['username']); ?></a></span>
                            <span><?php echo time_ago($theory['created_at']); ?></span>
                        </div>
                        <div class="theory-card-footer">
                            <a href="<?php echo BASE_URL; ?>theories/view.php?slug=<?php echo urlencode($theory['slug']); ?>" class="theory-card-footer-link">Read Investigation</a>
                            <a href="<?php echo BASE_URL; ?>theories/view.php?slug=<?php echo urlencode($theory['slug']); ?>#comments" class="theory-card-footer-link">View Evidence</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php echo $display_message; ?>
        <?php endif; ?>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php
                $query_params = [];
                if (!empty($selected_category)) $query_params['category'] = $selected_category;
                $base_query = http_build_query($query_params);
                $base_url = BASE_URL . 'theories/index.php';
                if (!empty($base_query)) $base_url .= '?' . $base_query . '&';
                else $base_url .= '?';
                ?>
                <?php if ($page > 1): ?>
                    <a href="<?php echo $base_url; ?>page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="<?php echo $base_url; ?>page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="<?php echo $base_url; ?>page=<?php echo $page + 1; ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
