    </main>

    <!-- Reading Progress Bar -->
    <div id="reading-progress"></div>

    <!-- Mobile Bottom Nav -->
    <nav id="mobile-bottom-nav">
        <a href="<?php echo BASE_URL; ?>"><span class="nav-icon">🏠</span>Home</a>
        <a href="<?php echo BASE_URL; ?>wiki/"><span class="nav-icon">📖</span>Wiki</a>
        <a href="<?php echo BASE_URL; ?>theories/"><span class="nav-icon">💭</span>Theories</a>
        <a href="<?php echo BASE_URL; ?>lore/"><span class="nav-icon">📚</span>Lore</a>
        <a href="<?php echo BASE_URL; ?>lore/browse.php?type=timeline"><span class="nav-icon">📜</span>History</a>
    </nav>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- Ambient Audio Toggle -->
    <div class="footer-ambient">
        <button id="ambient-toggle" class="ambient-btn" title="Toggle ocean ambiance">🌊</button>
    </div>

    <footer>
        <div class="footer-wave"></div>
        <div class="container footer-inner">
            <div class="footer-brand">
                <a href="https://www.bilibili.tv/en/play/37976/10344255" target="_blank" class="footer-skull-link" title="The One Piece is real.">
                    <div class="footer-skull">☠</div>
                </a>
                <h2 class="footer-title">THE ONE PIECE ENCYCLOPEDIA</h2>
                <p class="footer-subtitle">"The One Piece is real."</p>
            </div>
            <div class="footer-columns">
                <div class="footer-col">
                    <h4 class="footer-section-title">🗺️ Lore Archive</h4>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>lore/browse.php?type=characters">Characters</a></li>
                        <li><a href="<?php echo BASE_URL; ?>lore/browse.php?type=devil_fruits">Devil Fruits</a></li>
                        <li><a href="<?php echo BASE_URL; ?>lore/browse.php?type=arcs">Story Arcs</a></li>
                        <li><a href="<?php echo BASE_URL; ?>chapters/">Chapters &amp; Episodes</a></li>
                        <li><a href="<?php echo BASE_URL; ?>lore/timeline.php">World Timeline</a></li>
                        <li><a href="<?php echo BASE_URL; ?>lore/browse.php?type=timeline">World History</a></li>
                        <li><a href="<?php echo BASE_URL; ?>wiki/">Wiki Archives</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4 class="footer-section-title">💭 Community</h4>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>theories/">Pirate Theories</a></li>
                        <li><a href="<?php echo BASE_URL; ?>leaderboard/">Grand Line Rankings</a></li>
                        <li><a href="<?php echo BASE_URL; ?>random.php">Random Discovery</a></li>
                        <li><a href="<?php echo BASE_URL; ?>changes.php">Recent Changes</a></li>
                        <li><a href="<?php echo BASE_URL; ?>leaderboard/">Top Contributors</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4 class="footer-section-title">⚓ System</h4>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>auth/login.php">Login / Register</a></li>
                        <li><a href="<?php echo BASE_URL; ?>user/profile.php">My Profile</a></li>
                        <li><a href="<?php echo BASE_URL; ?>user/bookmarks.php">Bookmarks</a></li>
                        <li><a href="<?php echo BASE_URL; ?>user/notifications.php">Notifications</a></li>
                        <li><a href="<?php echo BASE_URL; ?>wiki/search.php">Search Archives</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4 class="footer-section-title">🏛️ Admin Control</h4>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php">Marine HQ</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/pending_articles.php">Pending Approvals</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/users.php">User Management</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/theories.php">Moderate Theories</a></li>
                        <li><a href="<?php echo BASE_URL; ?>media/">Media Manager</a></li>
                        <li><a href="<?php echo BASE_URL; ?>admin/backup.php">Database Backup</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> OPWiki — Grand Line Archive System</p>
                <p class="footer-credits">One Piece &copy; Eiichiro Oda, Shueisha, Toei Animation. This is a fan project.</p>
            </div>
        </div>
        <div class="footer-particles"></div>
    </footer>

<script>window.BASE_URL='<?php echo BASE_URL; ?>';</script>
<script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>



<style>
@media (max-width: 768px) {
    #mobile-bottom-nav { display: flex; }
    body { padding-bottom: 60px; }
    .footer-ambient { bottom: 70px; }
    #toast-container { bottom: 80px; }
}
</style>
</body>
</html>
