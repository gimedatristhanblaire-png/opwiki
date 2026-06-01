<?php
$page_title = 'World Government Archive';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_rep.php';
require_once __DIR__ . '/../includes/functions_interactive.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    $_SESSION['redirect_to'] = BASE_URL . 'wiki/view.php?slug=' . urlencode($_GET['slug'] ?? '');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

require_once __DIR__ . '/../includes/header.php';

$is_current_user_admin = false;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? null;
if ($user_role === 'admin') {
    $is_current_user_admin = true;
} elseif ($user_role === null && $user_id !== null) {
    $is_current_user_admin = is_admin($user_id, $conn);
}

$article_slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_STRING);
$article_data = null;
$display_message = '';

$sql_view = "SELECT wa.id, wa.title, wa.content, wa.category, wa.updated_at, wa.status, wa.user_id, wa.last_edited_by, wa.spoiler_level, wa.views, u.username, editor.username as editor_name
             FROM wiki_articles wa
             JOIN users u ON wa.user_id = u.id
             LEFT JOIN users editor ON wa.last_edited_by = editor.id
             WHERE wa.slug = ?";

$stmt_view = $conn->prepare($sql_view);
if ($stmt_view === false) {
    error_log("wiki/view.php: Error preparing view statement: " . $conn->error);
    $display_message = "<p style='color:red;'>An internal error occurred while loading the article. Please try again later.</p>";
} else {
    $stmt_view->bind_param("s", $article_slug);
    $stmt_view->execute();
    $result_view = $stmt_view->get_result();
    if ($result_view === false) {
        error_log("wiki/view.php: Error getting result for view statement: " . $conn->error);
        $display_message = "<p style='color:red;'>An internal error occurred while loading the article. Please try again later.</p>";
    } elseif ($result_view->num_rows === 1) {
        $article_data = $result_view->fetch_assoc();
        if ($article_data['status'] !== 'approved' && !$is_current_user_admin) {
            $display_message = "<p style='color:red;'>This article is not yet approved and cannot be viewed. Please check back later or contact an administrator.</p>";
            $article_data = null;
        } else {
            $page_title = htmlspecialchars($article_data['title']) . ' | Wiki Article';
            $meta_description = substr(strip_tags($article_data['content']), 0, 160);
            $conn->query("UPDATE wiki_articles SET views = views + 1 WHERE id = " . $article_data['id']);
        }
    } else {
        $display_message = "<p>The requested wiki article could not be found.</p>";
    }
    $stmt_view->close();
}

function render_lore_callouts($content) {
    $content = preg_replace_callback('/\[callout\s+type=["\'](trivia|theory|spoiler)["\']\](.*?)\[\/callout\]/is', function($m) {
        $type = strtolower($m[1]);
        $text = trim($m[2]);
        $icons = ['trivia' => '📝', 'theory' => '💭', 'spoiler' => '⚠️'];
        $labels = ['trivia' => 'Trivia', 'theory' => 'Theory', 'spoiler' => 'Spoiler'];
        $icon = $icons[$type] ?? '📌';
        $label = $labels[$type] ?? 'Note';
        return '<div class="lore-callout lore-callout-' . $type . '"><div class="lore-callout-title">' . $icon . ' ' . $label . '</div>' . $text . '</div>';
    }, $content);
    $content = preg_replace_callback('/\[wg\](.*?)\[\/wg\]/is', function($m) {
        return '<div class="wg-record"><div class="wg-record-title">📜 WORLD GOVERNMENT RECORD</div>' . trim($m[1]) . '</div>';
    }, $content);
    $content = preg_replace_callback('/\[void\](.*?)\[\/void\]/is', function($m) {
        return '<div class="ancient-archive"><div class="ancient-archive-title">🏛️ ANCIENT ARCHIVE</div>' . trim($m[1]) . '</div>';
    }, $content);
    $content = preg_replace_callback('/\[oda\](.*?)\[\/oda\]/is', function($m) {
        return '<div class="oda-foreshadow"><div class="oda-foreshadow-title">🔮 ODA FORESHADOWING</div>' . trim($m[1]) . '</div>';
    }, $content);
    $content = preg_replace_callback('/\[manga\](.*?)\[\/manga\]/is', function($m) {
        return '<div class="manga-evidence"><div class="manga-evidence-title">📖 MANGA EVIDENCE</div>' . trim($m[1]) . '</div>';
    }, $content);
    $content = preg_replace_callback('/\[timeline\](.*?)\[\/timeline\]/is', function($m) {
        return '<div class="timeline-ref"><div class="timeline-ref-title">📅 TIMELINE REFERENCE</div>' . trim($m[1]) . '</div>';
    }, $content);
    $content = preg_replace_callback('/\[quote\](.*?)\[\/quote\]/is', function($m) {
        return '<blockquote class="lore-quote-block">' . trim($m[1]) . '</blockquote>';
    }, $content);
    return $content;
}

// Word count / reading time
$word_count = str_word_count(strip_tags($article_data['content'] ?? ''), 0, 'àáâãäçèéêëìíîïñòóôõöùúûüýÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ');
$reading_time = max(1, ceil($word_count / 200));

// Category → gradient mapping for hero banner
$cat_hero_colors = [
    'Character' => ['bg' => '#1A2744', 'accent' => '#C62828'],
    'Organization' => ['bg' => '#1A1A2E', 'accent' => '#D4A843'],
    'Location' => ['bg' => '#0B1A2A', 'accent' => '#2A6B8A'],
    'History' => ['bg' => '#2A1F0E', 'accent' => '#B8922E'],
    'Devil Fruit' => ['bg' => '#1A0A2E', 'accent' => '#7B1FA2'],
    'Ship' => ['bg' => '#0A1A2A', 'accent' => '#00BCD4'],
    'Arc' => ['bg' => '#1A1A2E', 'accent' => '#C62828'],
];
$cat_color = $cat_hero_colors[$article_data['category'] ?? ''] ?? ['bg' => '#0B1A2A', 'accent' => '#D4A843'];

// Prev/Next article
$prev_article = null;
$next_article = null;
if ($article_data) {
    $pst = $conn->prepare("SELECT id, title, slug FROM wiki_articles WHERE category = ? AND id < ? AND status = 'approved' ORDER BY id DESC LIMIT 1");
    if ($pst) { $pst->bind_param("si", $article_data['category'], $article_data['id']); $pst->execute(); $pr = $pst->get_result(); if ($pr->num_rows) $prev_article = $pr->fetch_assoc(); $pst->close(); }
    $nst = $conn->prepare("SELECT id, title, slug FROM wiki_articles WHERE category = ? AND id > ? AND status = 'approved' ORDER BY id ASC LIMIT 1");
    if ($nst) { $nst->bind_param("si", $article_data['category'], $article_data['id']); $nst->execute(); $nr = $nst->get_result(); if ($nr->num_rows) $next_article = $nr->fetch_assoc(); $nst->close(); }
}
?>
<section id="wiki-view-article">
    <?php echo $display_message; ?>
    <?php if ($article_data): ?>
        <!-- Cinematic Hero -->
        <div class="article-hero">
            <div class="article-hero-bg" style="background: linear-gradient(135deg, <?php echo $cat_color['bg']; ?>, <?php echo $cat_color['bg']; ?>88, <?php echo $cat_color['accent']; ?>44);"></div>
            <div class="article-hero-pattern"></div>
            <div class="article-hero-overlay"></div>
            <div class="article-hero-content">
                <div class="article-hero-badges">
                    <span class="article-hero-badge article-hero-badge-id">WORLD ARCHIVE #<?php echo str_pad($article_data['id'], 3, '0', STR_PAD_LEFT); ?></span>
                    <span class="article-hero-badge"><?php echo htmlspecialchars($article_data['category']); ?></span>
                    <?php if ($article_data['spoiler_level'] > 0): ?>
                    <span class="article-hero-badge <?php echo 'article-hero-badge-spoiler-' . ['', 'mild', 'major', 'ultimate'][$article_data['spoiler_level']]; ?>">
                        <?php echo ['', 'Mild Spoiler', 'Major Spoiler', 'Ultimate Spoiler'][$article_data['spoiler_level']]; ?>
                    </span>
                    <?php endif; ?>
                </div>
                <h1 class="article-hero-title"><?php echo htmlspecialchars($article_data['title']); ?></h1>
                <div class="article-hero-meta">
                    <span class="article-hero-avatar"><img src="<?php echo BASE_URL; ?>lore/avatar.php?name=<?php echo urlencode($article_data['username'][0] ?? 'U'); ?>&bg=<?php echo urlencode($cat_color['accent']); ?>&color=fff&size=32" alt=""></span>
                    <a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $article_data['user_id']; ?>"><?php echo htmlspecialchars($article_data['username']); ?></a>
                    <span class="article-hero-meta-dot">•</span>
                    <span><?php echo $reading_time; ?> min read</span>
                    <span class="article-hero-meta-dot">•</span>
                    <span><?php echo (int)$article_data['views']; ?> views</span>
                    <span class="article-hero-meta-dot">•</span>
                    <span>Updated <?php echo date('M j, Y', strtotime($article_data['updated_at'])); ?></span>
                    <?php
                    $edit_ago = time_ago($article_data['updated_at']);
                    if ($edit_ago !== 'just now'): ?>
                    <span class="article-hero-meta-dot">•</span>
                    <span class="edit-inline-badge">✏️ Last edited <?php echo $edit_ago; if ($article_data['editor_name'] && $article_data['last_edited_by'] != $article_data['user_id']) echo ' by ' . htmlspecialchars($article_data['editor_name']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="container">
            <?php
            $tag_list = [];
            $stmt_tags = $conn->prepare("SELECT t.name, t.slug FROM tags t JOIN article_tags at ON t.id = at.tag_id WHERE at.article_id = ?");
            if ($stmt_tags) {
                $stmt_tags->bind_param("i", $article_data['id']);
                $stmt_tags->execute();
                $result_tags = $stmt_tags->get_result();
                while ($tag_row = $result_tags->fetch_assoc()) {
                    $tag_list[] = '<a href="' . BASE_URL . 'wiki/tag.php?tag=' . urlencode($tag_row['slug']) . '" class="home-tag">' . htmlspecialchars($tag_row['name']) . '</a>';
                }
                $stmt_tags->close();
            }
            ?>
            <?php if (!empty($tag_list)): ?>
                <div class="article-tags"><?php echo implode(' ', $tag_list); ?></div>
            <?php endif; ?>

            <!-- Archive Classification Badges -->
            <div class="archive-classification-row">
                <span class="archive-number-badge">WORLD GOVERNMENT ARCHIVE #<?php echo str_pad($article_data['id'], 3, '0', STR_PAD_LEFT); ?></span>
                <?php
                $class_badges = [
                    'Character' => ['label' => 'COMBAT DOSSIER', 'class' => 'class-combat'],
                    'Organization' => ['label' => 'CLASSIFIED', 'class' => 'class-classified'],
                    'Location' => ['label' => 'TERRITORIAL RECORD', 'class' => 'class-territory'],
                    'History' => ['label' => 'VOID CENTURY RECORD', 'class' => 'class-void'],
                    'Devil Fruit' => ['label' => 'BOTANICAL REPORT', 'class' => 'class-botanical'],
                    'Ship' => ['label' => 'NAVAL ARCHIVE', 'class' => 'class-naval'],
                    'Arc' => ['label' => 'CAMPAIGN REPORT', 'class' => 'class-campaign'],
                ];
                $cat = $article_data['category'] ?? '';
                if (isset($class_badges[$cat])) {
                    $cb = $class_badges[$cat];
                    echo '<span class="archive-class-badge ' . $cb['class'] . '">' . $cb['label'] . '</span>';
                }
                if ($article_data['spoiler_level'] > 0) {
                    echo '<span class="archive-class-badge class-spoiler">⚠️ SPOILER LEVEL ' . $article_data['spoiler_level'] . '</span>';
                }
                ?>
            </div>

            <?php
            $like_count_art = get_like_count('article', $article_data['id'], $conn);
            $user_has_liked_art = isset($_SESSION['user_id']) ? user_has_liked('article', $article_data['id'], $_SESSION['user_id'], $conn) : false;
            $is_bookmarked_art = isset($_SESSION['user_id']) ? is_bookmarked($_SESSION['user_id'], 'article', $article_data['id'], $conn) : false;
            ?>
            <div class="article-interaction-bar">
                <button class="ai-btn <?php echo $user_has_liked_art ? 'liked' : ''; ?>" data-type="article" data-id="<?php echo $article_data['id']; ?>" onclick="toggleLike(this)">
                    <?php echo $user_has_liked_art ? '★' : '☆'; ?> <span class="like-count"><?php echo $like_count_art; ?></span>
                </button>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="ai-btn <?php echo $is_bookmarked_art ? 'bookmarked' : ''; ?>" data-type="article" data-id="<?php echo $article_data['id']; ?>" onclick="toggleBookmark(this)">
                        🔖 <span><?php echo $is_bookmarked_art ? 'Bookmarked' : 'Bookmark'; ?></span>
                    </button>
                <?php endif; ?>
                <button class="ai-btn ai-btn-share" onclick="shareArticle()">📤 Share</button>
                <?php if ($is_current_user_admin || (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $article_data['user_id'])): ?>
                    <a href="<?php echo BASE_URL; ?>wiki/revisions.php?article_id=<?php echo $article_data['id']; ?>" class="ai-btn">📋 History</a>
                <?php endif; ?>
                <div class="ai-btn-stats">
                    <span>📄 <?php echo $word_count; ?> words</span>
                    <span>⏱ <?php echo $reading_time; ?> min</span>
                </div>
            </div>

            <!-- Article Body: Sidebar + Reading Area -->
            <?php
            require_once __DIR__ . '/../includes/Parsedown.php';
            $Parsedown = new Parsedown();
            $rendered = $Parsedown->text($article_data['content']);
            $rendered = render_lore_callouts($rendered);
            preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $rendered, $h2s);
            preg_match_all('/<h3[^>]*>(.*?)<\/h3>/i', $rendered, $h3s);

            $heading_count = 0;
            $headings = [];
            if (!empty($h2s[1]) || !empty($h3s[1])) {
                foreach ($h2s[1] as $h2) {
                    $heading_count++;
                    $slug = 'heading-' . $heading_count;
                    $headings[] = ['level' => 2, 'text' => strip_tags($h2), 'slug' => $slug];
                    $rendered = preg_replace('/<h2>(.*?)<\/h2>/', '<h2 id="' . $slug . '">$1</h2>', $rendered, 1);
                }
                foreach ($h3s[1] as $h3) {
                    $heading_count++;
                    $slug = 'heading-' . $heading_count;
                    $headings[] = ['level' => 3, 'text' => strip_tags($h3), 'slug' => $slug];
                    $rendered = preg_replace('/<h3>(.*?)<\/h3>/', '<h3 id="' . $slug . '">$1</h3>', $rendered, 1);
                }
            }

            $user_tolerance = 0;
            if (isset($_SESSION['user_id'])) {
                $tr = $conn->query("SELECT spoiler_tolerance FROM users WHERE id = " . (int)$_SESSION['user_id']);
                if ($tr) { $row_tol = $tr->fetch_assoc(); $user_tolerance = (int)$row_tol['spoiler_tolerance']; }
            }
            $show_spoiler = $article_data['spoiler_level'] > $user_tolerance;
            ?>

            <div class="article-layout">
                <!-- Left Sidebar TOC (desktop) -->
                <?php if (!empty($headings)): ?>
                <aside class="article-sidebar" id="article-sidebar">
                    <div class="sidebar-toc">
                        <h4>📖 Contents</h4>
                        <ul>
                            <?php foreach ($headings as $h): ?>
                            <li class="<?php echo $h['level'] === 3 ? 'level-3' : ''; ?>"><a href="#<?php echo $h['slug']; ?>"><?php echo htmlspecialchars($h['text']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </aside>
                <?php else: ?>
                <aside class="article-sidebar" id="article-sidebar"></aside>
                <?php endif; ?>

                <!-- Main Reading Area -->
                <div class="article-reading">
                    <div class="article-content <?php echo $show_spoiler ? 'spoiler-blurred' : ''; ?>" id="article-content">
                        <?php if ($show_spoiler): ?>
                            <div class="spoiler-overlay">
                                <span class="spoiler-label"><?php echo ['','Mild Spoiler','Major Spoiler','Ultimate Spoiler'][$article_data['spoiler_level']]; ?></span>
                                <p>This article contains <?php echo strtolower(['','Mild','Major','Ultimate'][$article_data['spoiler_level']]); ?> spoilers.</p>
                                <button class="btn spoiler-reveal" onclick="revealSpoiler(this)">Reveal Content</button>
                            </div>
                        <?php endif; ?>
                        <?php echo $rendered; ?>
                    </div>

                    <!-- Article Footer -->
                    <footer class="article-footer">
                        <!-- Prev/Next -->
                        <div class="article-footer-section">
                            <div class="prev-next-nav">
                                <div>
                                    <?php if ($prev_article): ?>
                                    <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($prev_article['slug']); ?>" class="prev-next-link prev">
                                        <div class="prev-next-label">← Previous Article</div>
                                        <div class="prev-next-title"><?php echo htmlspecialchars($prev_article['title']); ?></div>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($next_article): ?>
                                    <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($next_article['slug']); ?>" class="prev-next-link next">
                                        <div class="prev-next-label">Next Article →</div>
                                        <div class="prev-next-title"><?php echo htmlspecialchars($next_article['title']); ?></div>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Contributor Credits -->
                        <div class="article-footer-section">
                            <div class="article-footer-label">🏴‍☠️ Contributor</div>
                            <div class="contributor-credits">
                                <div class="contributor-avatar">
                                    <img src="<?php echo BASE_URL; ?>lore/avatar.php?name=<?php echo urlencode($article_data['username'][0] ?? 'U'); ?>&bg=<?php echo urlencode($cat_color['accent']); ?>&color=fff&size=40" alt="">
                                </div>
                                <div class="contributor-info">
                                    <div class="contributor-name"><a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $article_data['user_id']; ?>"><?php echo htmlspecialchars($article_data['username']); ?></a></div>
                                    <div class="contributor-role">Author • <?php echo date('M j, Y', strtotime($article_data['updated_at'])); ?></div>
                                </div>
                                <?php if ($article_data['last_edited_by'] && $article_data['editor_name'] && $article_data['last_edited_by'] != $article_data['user_id']): ?>
                                <div class="editor-block">
                                    <div class="contributor-avatar">
                                        <img src="<?php echo BASE_URL; ?>lore/avatar.php?name=<?php echo urlencode($article_data['editor_name'][0] ?? 'E'); ?>&bg=888&color=fff&size=40" alt="">
                                    </div>
                                    <div class="contributor-info">
                                        <div class="contributor-name"><a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $article_data['last_edited_by']; ?>"><?php echo htmlspecialchars($article_data['editor_name']); ?></a></div>
                                        <div class="contributor-role">Editor</div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Connected Characters -->
                        <?php
                        $connected_chars = [];
                        $cat_for_chars = $article_data['category'] ?? '';
                        if ($cat_for_chars === 'Character') {
                            $ccq = $conn->query("SELECT id, name FROM characters WHERE name LIKE '%" . $conn->real_escape_string(substr($article_data['title'], 0, 30)) . "%' OR affiliation LIKE '%" . $conn->real_escape_string($article_data['title']) . "%' LIMIT 6");
                            if ($ccq) while ($cc = $ccq->fetch_assoc()) $connected_chars[] = $cc;
                        } elseif (in_array($cat_for_chars, ['Organization', 'Location', 'Arc'])) {
                            $ccq = $conn->query("SELECT id, name FROM characters WHERE affiliation LIKE '%" . $conn->real_escape_string($article_data['title']) . "%' OR debut_arc LIKE '%" . $conn->real_escape_string($article_data['title']) . "%' LIMIT 6");
                            if ($ccq) while ($cc = $ccq->fetch_assoc()) $connected_chars[] = $cc;
                        }
                        ?>
                        <?php if (!empty($connected_chars)): ?>
                        <div class="article-footer-section">
                            <div class="article-footer-label">🏴‍☠️ Connected Characters</div>
                            <div class="connected-chars-grid">
                                <?php foreach ($connected_chars as $cc): ?>
                                <a href="<?php echo BASE_URL; ?>lore/view.php?type=characters&id=<?php echo $cc['id']; ?>" class="connected-char-link">
                                    <img src="<?php echo BASE_URL; ?>lore/avatar.php?name=<?php echo urlencode($cc['name']); ?>&bg=D4A843&color=fff&size=24" alt="">
                                    <span><?php echo htmlspecialchars($cc['name']); ?></span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Related Articles -->
                        <?php
                        $related = [];
                        $sql_rel = "SELECT wa.id, wa.title, wa.slug, COUNT(at.tag_id) as common_tags
                                    FROM wiki_articles wa
                                    JOIN article_tags at ON wa.id = at.article_id
                                    WHERE at.tag_id IN (SELECT tag_id FROM article_tags WHERE article_id = ?)
                                    AND wa.id != ? AND wa.status = 'approved'
                                    GROUP BY wa.id
                                    ORDER BY common_tags DESC, wa.title ASC
                                    LIMIT 5";
                        $stmt_rel = $conn->prepare($sql_rel);
                        if ($stmt_rel) {
                            $stmt_rel->bind_param("ii", $article_data['id'], $article_data['id']);
                            $stmt_rel->execute();
                            $result_rel = $stmt_rel->get_result();
                            while ($row_rel = $result_rel->fetch_assoc()) {
                                $related[] = $row_rel;
                            }
                            $stmt_rel->close();
                        }
                        ?>
                        <?php if (!empty($related)): ?>
                        <div class="article-footer-section">
                            <div class="article-footer-label">🔗 Related Articles</div>
                            <div class="related-articles-list">
                                <?php foreach ($related as $r): ?>
                                <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($r['slug']); ?>" class="related-article-link">
                                    <span class="related-article-title"><?php echo htmlspecialchars($r['title']); ?></span>
                                    <span class="related-article-arrow">→</span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Back Link -->
                        <p class="back-link"><a href="<?php echo BASE_URL; ?>wiki/" class="btn">&laquo; Back to Wiki Index</a></p>

                        <!-- Admin Actions -->
                        <?php if ($is_current_user_admin): ?>
                            <div class="article-admin-panel">
                                <h3>⚙️ Marine Intelligence</h3>
                                <div class="article-admin-actions">
                                    <a href="<?php echo BASE_URL; ?>admin/manage_article.php?id=<?php echo $article_data['id']; ?>&action=approve&csrf_token=<?php echo generate_csrf_token(); ?>" class="btn-sm">✅ Approve Archive</a>
                                    <a href="<?php echo BASE_URL; ?>admin/manage_article.php?id=<?php echo $article_data['id']; ?>&action=reject&csrf_token=<?php echo generate_csrf_token(); ?>" class="btn-sm btn-danger">❌ Classified Denied</a>
                                    <a href="<?php echo BASE_URL; ?>wiki/submit.php?id=<?php echo $article_data['id']; ?>" class="btn-sm">✏️ Revise Record</a>
                                    <a href="<?php echo BASE_URL; ?>admin/manage_article.php?id=<?php echo $article_data['id']; ?>&action=delete&csrf_token=<?php echo generate_csrf_token(); ?>" class="btn-sm btn-danger" onclick="return confirm('Erase this record from the archives?');">🗑️ Erase Record</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </footer>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>

<?php
if ($article_data) {
    $comment_target_type = 'article';
    $comment_target_id = $article_data['id'];
    require_once __DIR__ . '/../includes/comments.php';
}
require_once __DIR__ . '/../includes/footer.php';
?>
