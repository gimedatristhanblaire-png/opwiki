<?php
$page_title = 'Search';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$search_query = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING) ?: '';
$type = $_GET['type'] ?? 'all';
$category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_STRING) ?: '';
$sort = $_GET['sort'] ?? 'relevance';
$spoiler_filter = filter_input(INPUT_GET, 'spoiler', FILTER_VALIDATE_INT) ?: -1;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
if ($page < 1) $page = 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;
$is_admin = ($user_role === 'admin') || ($user_id && !$user_role && is_admin($user_id, $conn));

$results = [];
$total = 0;

$categories = [];
$rc = $conn->query("SELECT DISTINCT category FROM wiki_articles WHERE status='approved' ORDER BY category");
if ($rc) { while ($row = $rc->fetch_assoc()) $categories[] = $row['category']; }

if (!empty($search_query)) {
    $terms = '+' . implode(' +', explode(' ', $search_query));
    $status_sql = $is_admin ? '' : " AND status = 'approved'";
    $spoiler_sql = ($spoiler_filter >= 0) ? " AND spoiler_level <= $spoiler_filter" : '';
    $cat_sql = (!empty($category)) ? " AND category = '" . $conn->real_escape_string($category) . "'" : '';

    $order = 'wa.title ASC';
    if ($sort === 'date') $order = 'wa.updated_at DESC';
    elseif ($sort === 'likes') $order = 'like_count DESC';
    elseif ($sort === 'relevance') $order = 'relevance DESC';

    $unions = [];
    if ($type === 'all' || $type === 'articles') {
        $unions[] = "(SELECT 'article' as content_type, wa.id, wa.title, wa.slug, wa.content, wa.category as subcategory, wa.updated_at as date, wa.spoiler_level, u.username, wa.status, MATCH(wa.title,wa.content) AGAINST('$terms' IN BOOLEAN MODE) as relevance, (SELECT COUNT(*) FROM article_likes WHERE article_id=wa.id) as like_count FROM wiki_articles wa JOIN users u ON wa.user_id=u.id WHERE MATCH(wa.title,wa.content) AGAINST('$terms' IN BOOLEAN MODE)$status_sql$spoiler_sql)";
    }
    if ($type === 'all' || $type === 'theories') {
        $unions[] = "(SELECT 'theory' as content_type, t.id, t.title, t.slug, t.content, '' as subcategory, t.created_at as date, t.spoiler_level, u.username, t.status, MATCH(t.title,t.content) AGAINST('$terms' IN BOOLEAN MODE) as relevance, (SELECT COUNT(*) FROM theory_likes WHERE theory_id=t.id) as like_count FROM theories t JOIN users u ON t.user_id=u.id WHERE MATCH(t.title,t.content) AGAINST('$terms' IN BOOLEAN MODE)" . ($is_admin ? '' : " AND t.status='approved'") . "$spoiler_sql)";
    }

    if (!empty($unions)) {
        $union_sql = implode(' UNION ALL ', $unions);
        $count_sql = "SELECT COUNT(*) as c FROM ($union_sql) combined";
        $rc = $conn->query($count_sql);
        if ($rc) $total = $rc->fetch_assoc()['c'];

        $total_pages = ceil($total / $per_page);
        if ($page > $total_pages) $page = $total_pages;

        $sql = "$union_sql $cat_sql ORDER BY $order LIMIT $per_page OFFSET $offset";
        $rr = $conn->query($sql);
        if ($rr) { while ($row = $rr->fetch_assoc()) $results[] = $row; }
    }
}

$csrf = generate_csrf_token();
?>
<section id="advanced-search">
    <div class="container">
        <h2>🔍 Search</h2>
        <form method="GET" class="search-form">
            <div class="search-input-row">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search..." required class="search-input">
                <button type="submit" class="btn">Search</button>
            </div>
            <div class="search-filter-row">
                <select name="type" class="search-select">
                    <option value="all" <?php if($type=='all') echo 'selected'; ?>>All Content</option>
                    <option value="articles" <?php if($type=='articles') echo 'selected'; ?>>Articles Only</option>
                    <option value="theories" <?php if($type=='theories') echo 'selected'; ?>>Theories Only</option>
                </select>
                <select name="category" class="search-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>" <?php if($category===$c) echo 'selected'; ?>><?php echo htmlspecialchars($c); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="sort" class="search-select">
                    <option value="relevance" <?php if($sort==='relevance') echo 'selected'; ?>>Relevance</option>
                    <option value="date" <?php if($sort==='date') echo 'selected'; ?>>Newest</option>
                    <option value="likes" <?php if($sort==='likes') echo 'selected'; ?>>Most Liked</option>
                    <option value="title" <?php if($sort==='title') echo 'selected'; ?>>Title A-Z</option>
                </select>
                <select name="spoiler" class="search-select">
                    <option value="-1" <?php if($spoiler_filter===-1) echo 'selected'; ?>>All Spoiler Levels</option>
                    <option value="0" <?php if($spoiler_filter===0) echo 'selected'; ?>>No Spoilers</option>
                    <option value="1" <?php if($spoiler_filter===1) echo 'selected'; ?>>Mild Max</option>
                    <option value="2" <?php if($spoiler_filter===2) echo 'selected'; ?>>Major Max</option>
                </select>
            </div>
        </form>

        <?php if (empty($search_query)): ?>
            <p class="search-hint">Enter a search term above to find articles and theories.</p>
        <?php elseif (empty($results)): ?>
            <p>No results found for "<strong><?php echo htmlspecialchars($search_query); ?></strong>".</p>
        <?php else: ?>
            <p class="search-count">Found <strong><?php echo $total; ?></strong> result(s) for "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
            <?php foreach ($results as $r): ?>
                <?php
                $url = ($r['content_type'] === 'article') ? 'wiki/view.php?slug=' . urlencode($r['slug']) : 'theories/view.php?slug=' . urlencode($r['slug']);
                $excerpt = substr(strip_tags($r['content']), 0, 200);
                ?>
                <div class="search-result-item" style="border-left-color:<?php echo $r['spoiler_level']>0?'#C62828':'#F5C518'; ?>;">
                    <a href="<?php echo BASE_URL . $url; ?>" class="search-result-title"><?php echo htmlspecialchars($r['title']); ?></a>
                    <small class="search-result-meta">
                        [<?php echo ucfirst($r['content_type']); ?>]
                        <?php if ($r['subcategory']): ?>&bull; <?php echo htmlspecialchars($r['subcategory']); ?><?php endif; ?>
                        <?php if ($r['spoiler_level'] > 0): ?>
                            <span class="search-spoiler-badge"><?php echo ['','Mild','Major','Ultimate'][$r['spoiler_level']]; ?></span>
                        <?php endif; ?>
                    </small>
                    <p class="search-result-excerpt"><?php echo htmlspecialchars($excerpt); ?></p>
                    <small class="search-result-byline">
                        by <a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['username']); ?></a>
                        &bull; <?php echo date('M j, Y', strtotime($r['date'])); ?>
                        &bull; ♥ <?php echo $r['like_count']; ?>
                        <?php if (!$is_admin && $r['status'] !== 'approved'): ?>
                            &bull; <span class="search-pending">[Pending]</span>
                        <?php endif; ?>
                    </small>
                </div>
            <?php endforeach; ?>
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i=1; $i<=$total_pages; $i++): ?>
                        <a href="?q=<?php echo urlencode($search_query); ?>&type=<?php echo $type; ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo $sort; ?>&spoiler=<?php echo $spoiler_filter; ?>&page=<?php echo $i; ?>" class="<?php echo ($i==$page)?'active':''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <p><a href="<?php echo BASE_URL; ?>wiki/">&laquo; Back to Wiki</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
