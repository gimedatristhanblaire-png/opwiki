<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : 'OPWiki - A community wiki exploring One Piece topics, theories, and knowledge.'; ?>">
    <meta name="theme-color" content="#0B1A2A">
    <meta property="og:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | ' : ''; ?>OPWiki">
    <meta property="og:description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : 'OPWiki - A community wiki exploring One Piece topics, theories, and knowledge.'; ?>">
    <meta property="og:type" content="<?php echo isset($og_type) ? $og_type : 'website'; ?>">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>">
    <meta property="og:image" content="<?php echo BASE_URL; ?>images/og-image.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | ' : ''; ?>OPWiki">
    <meta name="twitter:description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : 'OPWiki - A community wiki exploring One Piece topics, theories, and knowledge.'; ?>">
    <link rel="canonical" href="<?php echo (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>">
    <link rel="alternate" type="application/rss+xml" title="OPWiki RSS Feed" href="<?php echo BASE_URL; ?>rss.php">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | ' : ''; ?>OPWiki</title>
    <link rel="preconnect" href="https://cdn.tiny.cloud">
    <link rel="dns-prefetch" href="https://cdn.tiny.cloud">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Inter:wght@300;400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Pirata+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <script defer src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    tinymce.init({
        selector: 'textarea.wysiwyg',
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code',
        branding: false,
        menubar: false,
        height: 400,
        images_upload_url: '<?php echo BASE_URL; ?>upload.php',
        images_upload_handler: function (blobInfo, progress) {
            return new Promise(function (resolve, reject) {
                var xhr, formData;
                xhr = new XMLHttpRequest();
                xhr.withCredentials = false;
                formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                xhr.onerror = function () { reject('Image upload failed due to a network error.'); };
                xhr.onload = function () {
                    if (xhr.status !== 200) { reject('HTTP Error: ' + xhr.status); return; }
                    var json = JSON.parse(xhr.responseText);
                    if (!json || typeof json.location != 'string') { reject('Invalid JSON: ' + xhr.responseText); return; }
                    resolve(json.location);
                };
                xhr.open('POST', '<?php echo BASE_URL; ?>upload.php');
                xhr.send(formData);
            });
        }
    });
    </script>
</head>
<body>
    <header>
        <div class="container header-inner">
            <div id="branding">
                <a href="https://www.bilibili.tv/en/play/37976/10344255" target="_blank" class="logo-link" title="The One Piece is real.">
                    <div class="pirate-logo">☠</div>
                </a>
                <h1><a href="<?php echo BASE_URL; ?>" title="OPWiki - One Piece Encyclopedia"><span class="highlight">OP</span>Wiki</a></h1>
            </div>
            <button id="nav-toggle" aria-label="Toggle navigation">&#9776;</button>
            <nav>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>wiki/">Articles</a></li>
                    <li><a href="<?php echo BASE_URL; ?>theories/">Theories</a></li>
                    <li class="nav-dropdown">
                        <a href="<?php echo BASE_URL; ?>lore/">Lore ▾</a>
                        <ul class="nav-dropdown-menu">
                            <li><a href="<?php echo BASE_URL; ?>lore/browse.php?type=characters">🏴 Characters</a></li>
                            <li><a href="<?php echo BASE_URL; ?>lore/browse.php?type=devil_fruits">🍎 Devil Fruits</a></li>
                            <li><a href="<?php echo BASE_URL; ?>lore/browse.php?type=arcs">🌊 Arcs</a></li>
                            <li><a href="<?php echo BASE_URL; ?>chapters/">📚 Chapters</a></li>
                            <li><a href="<?php echo BASE_URL; ?>lore/timeline.php">⏳ Timeline</a></li>
                            <li><a href="<?php echo BASE_URL; ?>lore/browse.php?type=timeline">📜 World History</a></li>
                            <li class="nav-divider"></li>
                            <li><a href="<?php echo BASE_URL; ?>random.php">🏴‍☠️ Random Discovery</a></li>
                            <li><a href="<?php echo BASE_URL; ?>leaderboard/">👑 Grand Line Rankings</a></li>
                        </ul>
                    </li>
                    <?php
                    if (isset($_SESSION['user_id'])) {
                        echo '<li><a href="' . BASE_URL . 'user/profile.php">Profile</a></li>';
                        if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'moderator')) {
                            $pcount = 0;
                            $tpcount = 0;
                            if (isset($conn) && $conn) {
                                $pr = $conn->query("SELECT COUNT(*) as c FROM wiki_articles WHERE status='pending'");
                                if ($pr) { $prow = $pr->fetch_assoc(); $pcount = $prow['c']; }
                                $tpr = $conn->query("SELECT COUNT(*) as c FROM theories WHERE status='pending'");
                                if ($tpr) { $tprow = $tpr->fetch_assoc(); $tpcount = $tprow['c']; }
                            }
                            $total_pending = $pcount + $tpcount;
                            $badge = $total_pending > 0 ? ' <span class="admin-badge">' . $total_pending . '</span>' : '';
                            echo '<li><a href="' . BASE_URL . 'admin/dashboard.php">Admin' . $badge . '</a></li>';
                        }
                        echo '<li><a href="' . BASE_URL . 'auth/logout.php">Logout</a></li>';
                    } else {
                        echo '<li><a href="' . BASE_URL . 'auth/login.php">Login</a></li>';
                        echo '<li><a href="' . BASE_URL . 'auth/register.php">Register</a></li>';
                    }
                    ?>
                </ul>
            </nav>
            <div id="header-right">
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="notif-bell-wrap">
                    <a href="<?php echo BASE_URL; ?>user/notifications.php" id="notif-bell" class="notif-bell" title="Notifications"><span class="denden">🐌</span><span id="notif-count" class="notif-count">0</span></a>
                    <div id="notif-dropdown" class="notif-dropdown-content">
                        <div id="notif-list"></div>
                        <a href="<?php echo BASE_URL; ?>user/notifications.php" class="notif-view-all">View All Notifications</a>
                    </div>
                </div>
                <?php endif; ?>
                <div id="search-bar">
                    <form action="<?php echo BASE_URL; ?>wiki/search.php" method="GET">
                        <input type="text" name="q" placeholder="Search..." required>
                        <button type="submit" class="btn-search">Search</button>
                    </form>
                </div>
                <label class="dark-switch" id="dark-mode-toggle" title="Toggle dark mode">
                    <input type="checkbox" id="dark-mode-checkbox">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </header>

    <!-- Loading Screen for all pages -->
    <div id="opwiki-loader">
      <div class="loader-straw-hat">🏴‍☠️</div>
      <div class="loader-text">Charting the Grand Line...</div>
    </div>

    <main class="container">
