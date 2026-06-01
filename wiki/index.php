<?php
$page_title = 'World Archive Database';
$meta_description = 'Explore the classified archives of the Grand Line — marine records, Devil Fruit encyclopedias, and ancient world history.';

require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_rep.php';

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
$per_page = 10;
$offset = ($page - 1) * $per_page;

$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$wiki_articles = [];
$display_message = '';

$where_clauses = [];
$params = [];
$types = '';

if (!empty($selected_category)) {
    $where_clauses[] = 'wa.category = ?';
    $params[] = $selected_category;
    $types .= 's';
}

if (!empty($search_query)) {
    $where_clauses[] = '(wa.title LIKE ? OR wa.content LIKE ?)';
    $search_term = '%' . $search_query . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

if (!$is_current_user_admin) {
    $where_clauses[] = "wa.status = 'approved'";
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$sql_count = "SELECT COUNT(*) as total FROM wiki_articles wa JOIN users u ON wa.user_id = u.id $where_sql";
$sql_wiki = "SELECT wa.id, wa.title, wa.slug, wa.content, wa.category, wa.updated_at, wa.user_id, u.username" .
    ($is_current_user_admin ? ", wa.status" : "") .
    " FROM wiki_articles wa
     JOIN users u ON wa.user_id = u.id
     $where_sql
     ORDER BY wa.updated_at DESC
     LIMIT ? OFFSET ?";

$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_articles = $result_count ? (int)$result_count->fetch_assoc()['total'] : 0;
$stmt_count->close();

$total_pages = ceil($total_articles / $per_page);
if ($total_pages > 0 && $page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

$stmt_wiki = $conn->prepare($sql_wiki);
if ($stmt_wiki === false) {
    error_log("wiki/index.php: Error preparing wiki articles statement: " . $conn->error);
    $display_message = "<p style='color:red;'>Could not load wiki articles. Please try again later.</p>";
} else {
    $bind_params = array_merge($params, [$per_page, $offset]);
    $bind_types = $types . 'ii';
    $stmt_wiki->bind_param($bind_types, ...$bind_params);
    $stmt_wiki->execute();
    $result_wiki = $stmt_wiki->get_result();
    if ($result_wiki === false) {
        error_log("wiki/index.php: Error fetching wiki articles: " . $conn->error);
        $display_message = "<p style='color:red;'>Could not load wiki articles. Please try again later.</p>";
    } elseif ($result_wiki->num_rows > 0) {
        while ($row = $result_wiki->fetch_assoc()) {
            $wiki_articles[] = $row;
        }
    } else {
        $display_message = "<p>No wiki articles are available yet.</p>";
    }
    $stmt_wiki->close();
}

$authored_articles_for_edit = [];
if ($user_id !== null) {
    $sql_authored = "SELECT id, title, slug, status
                     FROM wiki_articles
                     WHERE user_id = ? AND status != 'approved'
                     ORDER BY created_at DESC";
    $stmt_authored = $conn->prepare($sql_authored);
    if ($stmt_authored === false) {
        error_log("wiki/index.php: Error preparing authored articles statement: " . $conn->error);
    } else {
        $stmt_authored->bind_param("i", $user_id);
        $stmt_authored->execute();
        $result_authored = $stmt_authored->get_result();
        if ($result_authored !== false) {
            while ($row = $result_authored->fetch_assoc()) {
                $authored_articles_for_edit[] = $row;
            }
        }
        $stmt_authored->close();
    }
}

$featured_article = null;
$stmt_featured = $conn->prepare("SELECT wa.id, wa.title, wa.slug, wa.content, wa.category, wa.updated_at, u.username, u.id as uid,
    (SELECT COUNT(*) FROM article_likes WHERE article_id = wa.id) as likes
    FROM wiki_articles wa
    JOIN users u ON wa.user_id = u.id
    WHERE wa.status = 'approved'
    ORDER BY likes DESC
    LIMIT 1");
if ($stmt_featured) {
    $stmt_featured->execute();
    $result_featured = $stmt_featured->get_result();
    if ($result_featured && $result_featured->num_rows > 0) {
        $featured_article = $result_featured->fetch_assoc();
    }
    $stmt_featured->close();
}

function get_read_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $minutes = max(1, ceil($word_count / 200));
    return $minutes . ' min read';
}

function get_excerpt($content, $length = 150) {
    $text = strip_tags($content);
    if (mb_strlen($text) <= $length) return $text;
    $excerpt = mb_substr($text, 0, $length);
    $last_space = mb_strrpos($excerpt, ' ');
    if ($last_space !== false) {
        $excerpt = mb_substr($excerpt, 0, $last_space);
    }
    return $excerpt . '...';
}

$category_labels = [
    'general' => 'HISTORY',
    'characters' => 'CHARACTERS',
    'world_government' => 'WORLD GOVERNMENT',
    'devil_fruits' => 'DEVIL FRUITS',
    'locations' => 'LOCATIONS',
    'void_century' => 'VOID CENTURY',
];

function get_classification($category) {
    $classifications = [
        'general' => 'PUBLIC RECORD',
        'characters' => 'CONFIDENTIAL',
        'world_government' => 'CLASSIFIED',
        'devil_fruits' => 'CLASSIFIED',
        'locations' => 'CONFIDENTIAL',
        'void_century' => 'CLASSIFIED',
    ];
    return $classifications[$category] ?? 'PUBLIC RECORD';
}
?>
<section class="archive-hero">
    <div class="container">
        <h1 class="archive-hero-title">WORLD ARCHIVE DATABASE</h1>
        <p class="archive-hero-sub">Explore the hidden history of the Grand Line.</p>
        <div class="archive-search">
            <form method="GET" action="<?php echo BASE_URL; ?>wiki/index.php">
                <input type="text" name="search" placeholder="Search archives..." value="<?php echo htmlspecialchars($search_query); ?>">
                <?php if (!empty($selected_category)): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
                <?php endif; ?>
            </form>
        </div>
    </div>
</section>

<section id="wiki-list">
    <div class="container">

        <?php echo $submission_message; ?>

        <div class="archive-categories">
            <a href="<?php echo BASE_URL; ?>wiki/index.php<?php echo !empty($search_query) ? '?search=' . urlencode($search_query) : ''; ?>" class="archive-cat-tab <?php echo empty($selected_category) ? 'active' : ''; ?>">ALL RECORDS</a>
            <?php
            $all_categories = array_keys($category_labels);
            foreach ($all_categories as $cat):
                $label = $category_labels[$cat];
                $active = ($selected_category === $cat) ? 'active' : '';
                $url = BASE_URL . 'wiki/index.php?category=' . urlencode($cat);
                if (!empty($search_query)) $url .= '&search=' . urlencode($search_query);
            ?>
                <a href="<?php echo $url; ?>" class="archive-cat-tab <?php echo $active; ?>"><?php echo htmlspecialchars($label); ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($featured_article && empty($selected_category) && empty($search_query)): ?>
            <div class="featured-archive">
                <div class="featured-archive-label">Featured Archive of the Day</div>
                <h2 class="archive-card-title"><?php echo htmlspecialchars($featured_article['title']); ?></h2>
                <p class="archive-card-excerpt"><?php echo htmlspecialchars(get_excerpt($featured_article['content'], 300)); ?></p>
                <div class="archive-card-meta">
                    <span><?php echo get_read_time($featured_article['content']); ?></span>
                    <span>Updated <?php echo time_ago($featured_article['updated_at']); ?></span>
                    <span>by <a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $featured_article['uid']; ?>"><?php echo htmlspecialchars($featured_article['username']); ?></a></span>
                    <span>♥ <?php echo $featured_article['likes']; ?></span>
                </div>
                <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($featured_article['slug']); ?>" class="archive-card-footer-link">Read Archive</a>
            </div>
        <?php endif; ?>

        <?php
        $can_submit_new = $user_id !== null;
        $can_manage_pending = $is_current_user_admin;
        if ($can_manage_pending):
        ?>
            <div class="wiki-admin-actions">
                <a href="<?php echo BASE_URL; ?>wiki/submit.php" class="btn">Submit New Article</a>
                <a href="<?php echo BASE_URL; ?>admin/pending_articles.php" class="btn btn-secondary">Manage Pending Content</a>
            </div>
        <?php elseif ($can_submit_new): ?>
            <div class="wiki-admin-actions">
                <a href="<?php echo BASE_URL; ?>wiki/submit.php" class="btn">Submit New Article</a>
            </div>
        <?php else: ?>
            <div class="wiki-admin-actions">
                <p>Please <a href="<?php echo BASE_URL; ?>auth/login.php">login</a> to submit articles.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($authored_articles_for_edit)): ?>
            <div class="user-authored-articles">
                <h3>Your Articles Awaiting Review</h3>
                <ul>
                    <?php foreach ($authored_articles_for_edit as $article): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($article['slug']); ?>">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </a>
                            <span style="font-weight: bold; color: <?php echo ($article['status'] === 'pending' ? 'orange' : 'red'); ?>;">
                                [<?php echo ucfirst($article['status']); ?>]
                            </span>
                            <a href="<?php echo BASE_URL; ?>wiki/submit.php?id=<?php echo $article['id']; ?>" class="btn-edit-small">Edit</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($wiki_articles)): ?>
            <div class="archive-card-grid">
                <?php foreach ($wiki_articles as $article): ?>
                    <?php
                    $classification = get_classification($article['category']);
                    $cat_label = isset($category_labels[$article['category']]) ? $category_labels[$article['category']] : strtoupper($article['category']);
                    $excerpt = get_excerpt($article['content'] ?? '');
                    $read_time = get_read_time($article['content'] ?? '');
                    $status_badge = '';
                    $status_color = '';
                    if ($is_current_user_admin && isset($article['status'])) {
                        switch ($article['status']) {
                            case 'pending': $status_badge = 'PENDING'; $status_color = 'orange'; break;
                            case 'approved': $status_badge = 'APPROVED'; $status_color = 'green'; break;
                            case 'rejected': $status_badge = 'REJECTED'; $status_color = 'red'; break;
                        }
                    }
                    ?>
                    <div class="archive-card">
                        <div class="archive-card-top">
                            <span class="archive-card-id">WORLD ARCHIVE #<?php echo str_pad($article['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            <span class="archive-card-classification"><?php echo htmlspecialchars($classification); ?></span>
                            <?php if ($status_badge): ?>
                                <span class="archive-card-status" style="color: <?php echo $status_color; ?>; font-weight: bold; font-size: 0.75rem; margin-left: auto;">[<?php echo $status_badge; ?>]</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="archive-card-title">
                            <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($article['slug']); ?>">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </a>
                        </h3>
                        <p class="archive-card-excerpt"><?php echo htmlspecialchars($excerpt); ?></p>
                        <div class="archive-card-meta">
                            <span><?php echo $read_time; ?></span>
                            <span>Updated <?php echo time_ago($article['updated_at']); ?></span>
                            <span class="archive-card-category"><?php echo htmlspecialchars($cat_label); ?></span>
                            <span>by <a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $article['user_id']; ?>"><?php echo htmlspecialchars($article['username']); ?></a></span>
                        </div>
                        <div class="archive-card-footer">
                            <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($article['slug']); ?>" class="archive-card-footer-link">Read Archive</a>
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
                if (!empty($search_query)) $query_params['search'] = $search_query;
                $base_query = http_build_query($query_params);
                $base_url = BASE_URL . 'wiki/index.php';
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
