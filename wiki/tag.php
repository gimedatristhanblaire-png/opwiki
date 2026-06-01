<?php
$page_title = 'Articles by Tag';

require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$tag_slug = filter_input(INPUT_GET, 'tag', FILTER_SANITIZE_STRING);

if (empty($tag_slug)) {
    echo '<section><div class="container"><p>No tag specified. <a href="' . BASE_URL . 'wiki/">Browse all articles</a>.</p></div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$tag_info = null;
$stmt_tag = $conn->prepare("SELECT id, name, slug FROM tags WHERE slug = ?");
if ($stmt_tag) {
    $stmt_tag->bind_param("s", $tag_slug);
    $stmt_tag->execute();
    $result_tag = $stmt_tag->get_result();
    $tag_info = $result_tag->fetch_assoc();
    $stmt_tag->close();
}

if (!$tag_info) {
    echo '<section><div class="container"><p>Tag not found. <a href="' . BASE_URL . 'wiki/">Browse all articles</a>.</p></div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$page_title = 'Tag: ' . htmlspecialchars($tag_info['name']);

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
if (!$page || $page < 1) $page = 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$sql_count = "SELECT COUNT(*) as total FROM article_tags at JOIN wiki_articles wa ON at.article_id = wa.id WHERE at.tag_id = ? AND wa.status = 'approved'";
$stmt_count = $conn->prepare($sql_count);
$total_articles = 0;
if ($stmt_count) {
    $stmt_count->bind_param("i", $tag_info['id']);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    if ($result_count) $total_articles = $result_count->fetch_assoc()['total'];
    $stmt_count->close();
}
$total_pages = ceil($total_articles / $per_page);

if ($total_pages > 0 && $page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

$articles = [];
$sql = "SELECT wa.id, wa.title, wa.slug, wa.category, wa.updated_at, u.username
        FROM wiki_articles wa
        JOIN users u ON wa.user_id = u.id
        JOIN article_tags at ON wa.id = at.article_id
        WHERE at.tag_id = ? AND wa.status = 'approved'
        ORDER BY wa.updated_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("iii", $tag_info['id'], $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    $stmt->close();
}
?>

<section id="tag-filter">
    <div class="container">
        <h2>Articles tagged: <em><?php echo htmlspecialchars($tag_info['name']); ?></em></h2>

        <?php if (empty($articles)): ?>
            <p>No articles found with this tag.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($articles as $article): ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($article['slug']); ?>">
                            <?php echo htmlspecialchars($article['title']); ?>
                        </a>
                        <small>(<?php echo htmlspecialchars($article['category']); ?> | <?php echo date('F j, Y', strtotime($article['updated_at'])); ?> by <?php echo htmlspecialchars($article['username']); ?>)</small>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo BASE_URL; ?>wiki/tag.php?tag=<?php echo urlencode($tag_slug); ?>&page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo BASE_URL; ?>wiki/tag.php?tag=<?php echo urlencode($tag_slug); ?>&page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo BASE_URL; ?>wiki/tag.php?tag=<?php echo urlencode($tag_slug); ?>&page=<?php echo $page + 1; ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <p><a href="<?php echo BASE_URL; ?>wiki/">&laquo; Back to Wiki Index</a></p>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
