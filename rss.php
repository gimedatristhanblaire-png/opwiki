<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db_connection.php';

header('Content-Type: application/rss+xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>OPWiki</title>
    <link><?php echo BASE_URL; ?></link>
    <description>Latest articles and theories from OPWiki</description>
    <language>en-us</language>
    <atom:link href="<?php echo BASE_URL; ?>rss.php" rel="self" type="application/rss+xml"/>
    <?php
    $items = [];

    $result = $conn->query("SELECT title, slug, content, updated_at, username, 'article' as type FROM wiki_articles JOIN users ON user_id = users.id WHERE status='approved' ORDER BY updated_at DESC LIMIT 20");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }

    $result2 = $conn->query("SELECT title, slug, content, updated_at, username, 'theory' as type FROM theories JOIN users ON user_id = users.id WHERE status='approved' ORDER BY updated_at DESC LIMIT 10");
    if ($result2) {
        while ($row = $result2->fetch_assoc()) {
            $items[] = $row;
        }
    }

    usort($items, function ($a, $b) {
        return strtotime($b['updated_at']) - strtotime($a['updated_at']);
    });

    $items = array_slice($items, 0, 20);

    foreach ($items as $item):
        $url = BASE_URL . ($item['type'] === 'article' ? 'wiki' : 'theories') . '/view.php?slug=' . urlencode($item['slug']);
        $description = substr(strip_tags($item['content']), 0, 500);
    ?>
    <item>
        <title><?php echo htmlspecialchars($item['title']); ?></title>
        <link><?php echo $url; ?></link>
        <guid isPermaLink="true"><?php echo $url; ?></guid>
        <pubDate><?php echo date('r', strtotime($item['updated_at'])); ?></pubDate>
        <description><?php echo htmlspecialchars($description); ?></description>
        <author><?php echo htmlspecialchars($item['username']); ?></author>
        <category><?php echo $item['type'] === 'article' ? 'Wiki Article' : 'Theory'; ?></category>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
