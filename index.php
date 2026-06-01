<?php
$page_title = 'Home';
$meta_description = 'OPWiki - A community wiki exploring One Piece topics, theories, and knowledge.';
require_once __DIR__ . '/includes/db_connection.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/functions_rep.php';

$stats = [];
$r = $conn->query("SELECT (SELECT COUNT(*) FROM wiki_articles WHERE status='approved') as articles, (SELECT COUNT(*) FROM theories WHERE status='approved') as theories, (SELECT COUNT(*) FROM users) as users, (SELECT COUNT(*) FROM article_likes)+(SELECT COUNT(*) FROM theory_likes) as total_likes");
if ($r) $stats = $r->fetch_assoc();

$recent_articles = [];
$stmt = $conn->prepare("SELECT wa.id, wa.title, wa.slug, wa.category, wa.updated_at, u.username, u.id as uid, (SELECT COUNT(*) FROM article_likes WHERE article_id=wa.id) as likes FROM wiki_articles wa JOIN users u ON wa.user_id=u.id WHERE wa.status='approved' ORDER BY wa.updated_at DESC LIMIT 6");
if ($stmt) { $stmt->execute(); $recent_articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close(); }

$recent_theories = [];
$stmt = $conn->prepare("SELECT t.id, t.title, t.slug, t.created_at, u.username, u.id as uid, (SELECT COUNT(*) FROM theory_likes WHERE theory_id=t.id) as likes FROM theories t JOIN users u ON t.user_id=u.id WHERE t.status='approved' ORDER BY t.created_at DESC LIMIT 6");
if ($stmt) { $stmt->execute(); $recent_theories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close(); }

$top_users = [];
$r = $conn->query("SELECT id, username, reputation_points FROM users ORDER BY reputation_points DESC LIMIT 5");
if ($r) $top_users = $r->fetch_all(MYSQLI_ASSOC);

$featured_article = null;
$r = $conn->query("SELECT wa.id, wa.title, wa.slug, wa.content, u.username FROM wiki_articles wa JOIN users u ON wa.user_id=u.id WHERE wa.status='approved' ORDER BY (SELECT COUNT(*) FROM article_likes WHERE article_id=wa.id) DESC LIMIT 1");
if ($r) $featured_article = $r->fetch_assoc();

$tags = [];
$r = $conn->query("SELECT t.name, t.slug, (SELECT COUNT(*) FROM article_tags at2 JOIN wiki_articles wa2 ON at2.article_id=wa2.id WHERE at2.tag_id=t.id AND wa2.status='approved') + (SELECT COUNT(*) FROM theory_tags tt2 JOIN theories th2 ON tt2.theory_id=th2.id WHERE tt2.tag_id=t.id AND th2.status='approved') as count FROM tags t HAVING count > 0 ORDER BY count DESC LIMIT 15");
if ($r) { while ($row = $r->fetch_assoc()) $tags[] = $row; }

// Character of the day
$char_day = null;
$r = $conn->query("SELECT id, name, affiliation, bounty, image FROM characters ORDER BY RAND() LIMIT 1");
if ($r) $char_day = $r->fetch_assoc();

// Random devil fruit
$rand_df = null;
$r = $conn->query("SELECT d.name, d.type, d.current_holder, c.name AS holder_name FROM devil_fruits d LEFT JOIN characters c ON d.current_holder_id = c.id ORDER BY RAND() LIMIT 1");
if ($r) $rand_df = $r->fetch_assoc();

// Arc count for timeline
$arc_stats = [];
$r = $conn->query("SELECT saga, COUNT(*) as cnt FROM arcs GROUP BY saga ORDER BY MIN(arc_number)");
if ($r) { while ($row = $r->fetch_assoc()) $arc_stats[] = $row; }

// Most discussed (most comments)
$most_discussed = [];
$r = $conn->query("SELECT wa.id, wa.title, wa.slug, (SELECT COUNT(*) FROM comments WHERE target_type='article' AND target_id=wa.id) as c FROM wiki_articles wa WHERE wa.status='approved' ORDER BY c DESC LIMIT 5");
if ($r) $most_discussed = $r->fetch_all(MYSQLI_ASSOC);

$quotes = [
  '"Inherited Will."',
  '"The dreams of pirates never end."',
  '"The One Piece is real!"',
  '"People\'s dreams never end!"',
  '"I don\'t want to conquer anything — the freest person on the sea is the Pirate King."',
  '"A life without a dream is meaningless."',
  '"Power is not determined by your size, but by the size of your will!"',
  '"Nothing happened."',
  '"If you don\'t take risks, you can\'t create a future!"',
  '"When you give up, the game ends."',
];
?>
<!-- Hero Section -->
<section id="home-hero">
  <div class="hero-ocean">
    <div class="hero-wave wave-1"></div>
    <div class="hero-wave wave-2"></div>
    <div class="hero-wave wave-3"></div>
  </div>
  <div class="hero-clouds">
    <div class="cloud cloud-1">☁️</div>
    <div class="cloud cloud-2">☁️</div>
    <div class="cloud cloud-3">☁️</div>
    <div class="cloud cloud-4">☁️</div>
  </div>
  <div class="hero-ship">⛵</div>
  <div class="hero-compass">🧭</div>
  <div class="hero-content">
    <div class="hero-badge">🏴‍☠️ The Grand Line Encyclopedia</div>
    <h2 class="hero-title"><span class="hero-op">OP</span>Wiki</h2>
    <p class="hero-quote" id="hero-quote"><?php echo $quotes[array_rand($quotes)]; ?></p>
    <div class="hero-stats">
      <div class="hero-stat"><span class="hero-stat-num"><?php echo (int)($stats['articles']??0); ?></span><span class="hero-stat-label">Articles</span></div>
      <div class="hero-stat"><span class="hero-stat-num"><?php echo (int)($stats['theories']??0); ?></span><span class="hero-stat-label">Theories</span></div>
      <div class="hero-stat"><span class="hero-stat-num"><?php echo (int)($stats['users']??0); ?></span><span class="hero-stat-label">Crew</span></div>
      <div class="hero-stat"><span class="hero-stat-num"><?php echo (int)($stats['total_likes']??0); ?></span><span class="hero-stat-label">Likes</span></div>
    </div>
    <div class="hero-ctas">
      <a href="<?php echo BASE_URL; ?>lore/" class="btn-hero btn-hero-primary">Explore Lore</a>
      <a href="<?php echo BASE_URL; ?>theories/" class="btn-hero btn-hero-secondary">Read Theories</a>
      <a href="<?php echo BASE_URL; ?>random.php" class="btn-hero btn-hero-accent">Random Mystery</a>
      <a href="<?php echo BASE_URL; ?>lore/timeline.php" class="btn-hero btn-hero-ghost">Release Archive</a>
    </div>
  </div>
</section>

<section id="home-content">
  <div class="container">

    <!-- Current Arc Tracker -->
    <div class="home-section">
      <div class="section-header"><span class="section-icon">🌊</span> Current Arc Tracker</div>
      <?php
      $current_arc = $conn->query("SELECT name, saga, chapters, episodes FROM arcs ORDER BY arc_number DESC LIMIT 1")->fetch_assoc();
      $total_arcs = $conn->query("SELECT COUNT(*) as c FROM arcs")->fetch_assoc()['c'];
      $saga_icons = ['🏴‍☠️','🔥','☁️','🌊','💀','⚔️','🐟','🌹','👑','⭐'];
      ?>
      <div class="journey-route" style="flex-wrap: nowrap; overflow-x: auto; padding: 8px 0;">
        <?php $si = 0; foreach ($arc_stats as $s):
          $icon = $saga_icons[$si] ?? '🏝️';
          $name = explode(' ', $s['saga'])[0];
        ?>
        <a href="<?php echo BASE_URL; ?>lore/browse.php?type=arcs" class="journey-saga-card" style="min-width:100px; padding:12px 10px; flex-shrink:0;">
          <div class="saga-card-icon" style="font-size:1.6rem;"><?php echo $icon; ?></div>
          <div class="saga-card-name" style="font-size:0.78rem;"><?php echo htmlspecialchars($name); ?></div>
          <div class="saga-card-arcs" style="font-size:0.65rem;"><?php echo $s['cnt']; ?> arcs</div>
        </a>
        <?php if ($si < count($arc_stats) - 1): ?>
        <div class="journey-connector" style="flex-shrink:0;">
          <div class="connect-line" style="height:2px;width:20px;"></div>
        </div>
        <?php endif; ?>
        <?php $si++; endforeach; ?>
      </div>
      <?php if ($current_arc): ?>
      <div style="text-align:center;margin-top:8px;font-size:0.82rem;color:var(--text-dim);">
        📍 Currently: <strong style="color:var(--gold);"><?php echo htmlspecialchars($current_arc['name']); ?></strong>
        <span style="margin-left:12px;">🏴 <?php echo htmlspecialchars($current_arc['saga']); ?> Saga</span>
      </div>
      <?php endif; ?>
    </div>

    <!-- Phase 2: Featured Character -->
    <?php if ($char_day): ?>
    <div class="home-section">
      <div class="section-header"><span class="section-icon">🏴‍☠️</span> Character of the Day</div>
      <div class="char-day-card">
        <div class="char-day-image">
          <?php
          $cd_img = $char_day['image'] ?? '';
          $cd_img_src = !empty($cd_img) ? ((strpos($cd_img, 'http') === 0 || strpos($cd_img, '/') === 0) ? $cd_img : BASE_URL . $cd_img) : BASE_URL . 'lore/avatar.php?name=' . urlencode($char_day['name']) . '&bg=C62828&color=fff&size=150';
          ?>
          <img src="<?php echo htmlspecialchars($cd_img_src); ?>" alt="<?php echo htmlspecialchars($char_day['name']); ?>">
        </div>
        <div class="char-day-info">
          <h3><?php echo htmlspecialchars($char_day['name']); ?></h3>
          <?php if ($char_day['affiliation']): ?><span class="char-day-affil"><?php echo htmlspecialchars($char_day['affiliation']); ?></span><?php endif; ?>
          <?php if ($char_day['bounty']): ?><div class="char-day-bounty">Bounty: <?php echo number_format($char_day['bounty']); ?> Berries</div><?php endif; ?>
        </div>
        <div class="char-day-footer">
          <a href="<?php echo BASE_URL; ?>lore/browse.php?type=characters" class="btn-sm">View All Characters</a>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- This Day in One Piece Widget -->
    <?php
    $today_md = date('m-d');
    $today_events = $conn->query("SELECT t.id, t.title, t.category, t.release_date, t.event_date
                                  FROM timeline t
                                  WHERE (DATE_FORMAT(t.release_date, '%m-%d') = '$today_md' OR DATE_FORMAT(STR_TO_DATE(t.event_date, '%Y-%m-%d'), '%m-%d') = '$today_md')
                                  AND t.release_date IS NOT NULL
                                  LIMIT 5");
    ?>
    <div class="home-section">
      <div class="this-day-widget">
        <div class="this-day-header">
          <span class="this-day-icon">📅</span>
          <span class="this-day-title">This Day in One Piece</span>
          <span class="home-list-meta ml-auto"><?php echo date('F j'); ?></span>
        </div>
        <div class="this-day-entries">
          <?php if ($today_events && $today_events->num_rows > 0): ?>
            <?php while ($td = $today_events->fetch_assoc()): ?>
              <div class="this-day-entry">
                <a href="<?php echo BASE_URL; ?>lore/view.php?type=timeline&id=<?php echo $td['id']; ?>">
                  <?php echo htmlspecialchars($td['title']); ?>
                </a>
                <span class="home-list-meta">— <?php echo date('F j, Y', strtotime($td['release_date'] ?? $td['event_date'])); ?></span>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="this-day-empty">No major events recorded on this day. The archives are silent...</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Featured Article + Latest Articles/Theories Grid -->
    <div class="home-featured-grid">
      <?php if ($featured_article): ?>
      <div class="featured-card parchment-card">
        <div class="parchment-seal">👑</div>
        <h3 class="parchment-title">Featured Article</h3>
        <h4><a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($featured_article['slug']); ?>"><?php echo htmlspecialchars($featured_article['title']); ?></a></h4>
        <p class="parchment-author">by <?php echo htmlspecialchars($featured_article['username']); ?></p>
        <p class="parchment-excerpt"><?php echo substr(strip_tags($featured_article['content']), 0, 200); ?>...</p>
        <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($featured_article['slug']); ?>" class="parchment-read">Continue Reading →</a>
      </div>
      <?php endif; ?>

      <div class="home-col parchment-card">
        <div class="parchment-seal">📖</div>
        <h3 class="parchment-title">Latest Articles</h3>
        <?php if (empty($recent_articles)): ?><p class="home-empty">No articles yet.</p>
        <?php else: ?>
          <?php foreach ($recent_articles as $a): ?>
          <div class="home-list-item">
            <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($a['slug']); ?>" class="home-list-title"><?php echo htmlspecialchars($a['title']); ?></a>
            <span class="home-list-meta"><?php echo htmlspecialchars($a['category']); ?> · <a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $a['uid']; ?>"><?php echo htmlspecialchars($a['username']); ?></a> · <?php echo time_ago($a['updated_at']); ?> · ♥ <?php echo $a['likes']; ?></span>
          </div>
          <?php endforeach; ?>
          <a href="<?php echo BASE_URL; ?>wiki/" class="parchment-read">All Articles →</a>
        <?php endif; ?>
      </div>

      <div class="home-col parchment-card">
        <div class="parchment-seal">💭</div>
        <h3 class="parchment-title">Latest Theories</h3>
        <?php if (empty($recent_theories)): ?><p class="home-empty">No theories yet.</p>
        <?php else: ?>
          <?php foreach ($recent_theories as $t): ?>
          <div class="home-list-item">
            <a href="<?php echo BASE_URL; ?>theories/view.php?slug=<?php echo urlencode($t['slug']); ?>" class="home-list-title"><?php echo htmlspecialchars($t['title']); ?></a>
            <span class="home-list-meta">by <a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $t['uid']; ?>"><?php echo htmlspecialchars($t['username']); ?></a> · <?php echo time_ago($t['created_at']); ?> · ♥ <?php echo $t['likes']; ?></span>
          </div>
          <?php endforeach; ?>
          <a href="<?php echo BASE_URL; ?>theories/" class="parchment-read">All Theories →</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Bottom Grid: Top Contributors, Tags, Random DF, Most Discussed -->
    <div class="home-bottom-grid">
      <div class="home-col parchment-card">
        <div class="parchment-seal">🏆</div>
        <h3 class="parchment-title">Top Contributors</h3>
        <table class="home-rank-table">
          <?php $rank=1; foreach ($top_users as $u): ?>
          <tr><td class="rank-num">#<?php echo $rank++; ?></td><td><a href="<?php echo BASE_URL; ?>user/view.php?id=<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></a></td><td class="rank-rep"><?php echo htmlspecialchars(get_reputation_title($u['reputation_points'])); ?></td></tr>
          <?php endforeach; ?>
        </table>
        <a href="<?php echo BASE_URL; ?>leaderboard/" class="parchment-read">Full Leaderboard →</a>
      </div>

      <div class="home-col parchment-card">
        <div class="parchment-seal">🏷️</div>
        <h3 class="parchment-title">Popular Tags</h3>
        <div class="home-tags">
          <?php foreach ($tags as $tag): ?>
            <a href="<?php echo BASE_URL; ?>wiki/tag.php?tag=<?php echo urlencode($tag['slug']); ?>" class="home-tag"><?php echo htmlspecialchars($tag['name']); ?><span class="home-tag-count"><?php echo $tag['count']; ?></span></a>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if ($rand_df): ?>
      <div class="home-col parchment-card">
        <div class="parchment-seal">🍎</div>
        <h3 class="parchment-title">Random Devil Fruit</h3>
        <div class="rand-df-card">
          <div class="rand-df-name"><?php echo htmlspecialchars($rand_df['name']); ?></div>
          <div class="rand-df-type"><?php echo htmlspecialchars($rand_df['type']); ?></div>
          <?php $holder = $rand_df['holder_name'] ?? $rand_df['current_holder'] ?? ''; if ($holder): ?><div class="rand-df-holder">Current: <?php echo htmlspecialchars($holder); ?></div><?php endif; ?>
        </div>
        <a href="<?php echo BASE_URL; ?>lore/browse.php?type=devil_fruits" class="parchment-read">All Devil Fruits →</a>
      </div>
      <?php endif; ?>

      <div class="home-col parchment-card">
        <div class="parchment-seal">💬</div>
        <h3 class="parchment-title">Most Discussed</h3>
        <?php if (empty($most_discussed)): ?><p class="home-empty">No discussions yet.</p>
        <?php else: ?>
          <?php foreach ($most_discussed as $d): ?>
          <div class="home-list-item">
            <a href="<?php echo BASE_URL; ?>wiki/view.php?slug=<?php echo urlencode($d['slug']); ?>" class="home-list-title"><?php echo htmlspecialchars($d['title']); ?></a>
            <span class="home-list-meta"><?php echo $d['c']; ?> comments</span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Phase 9: Community Poll -->
    <div class="home-section">
      <div class="section-header"><span class="section-icon">📊</span> Community Poll</div>
      <div class="poll-card parchment-card">
        <p class="poll-question">Which saga is the best in One Piece?</p>
        <div class="poll-options">
          <?php 
          $sagas = ['East Blue','Alabasta','Sky Island','Water 7','Summit War','Fish-Man Island','Dressrosa','Four Emperors','Final Saga'];
          foreach ($sagas as $s): ?>
          <label class="poll-option">
            <input type="radio" name="poll" value="<?php echo $s; ?>">
            <span class="poll-label"><?php echo $s; ?></span>
          </label>
          <?php endforeach; ?>
        </div>
        <button class="btn-sm poll-vote" onclick="showToast('Vote recorded! Check back for results.')">Vote</button>
      </div>
    </div>

    <!-- Links -->
    <div class="home-links">
      <a href="<?php echo BASE_URL; ?>wiki/submit.php" class="btn">⚓ Write Article</a>
      <a href="<?php echo BASE_URL; ?>theories/submit.php" class="btn">💡 Share Theory</a>
      <a href="<?php echo BASE_URL; ?>wiki/" class="btn">📚 Browse Wiki</a>
      <a href="<?php echo BASE_URL; ?>lore/" class="btn">🏴‍☠️ Lore DB</a>
      <a href="<?php echo BASE_URL; ?>leaderboard/" class="btn">🏆 Leaderboard</a>
      <a href="<?php echo BASE_URL; ?>lore/timeline.php" class="btn">📰 Release Archive</a>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
