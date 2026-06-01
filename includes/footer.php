    </main>

    <!-- Reading Progress Bar -->
    <div id="reading-progress"></div>

    <!-- Mobile Bottom Nav -->
    <nav id="mobile-bottom-nav">
        <a href="<?php echo BASE_URL; ?>"><span class="nav-icon">🏠</span>Home</a>
        <a href="<?php echo BASE_URL; ?>wiki/"><span class="nav-icon">📖</span>Wiki</a>
        <a href="<?php echo BASE_URL; ?>theories/"><span class="nav-icon">💭</span>Theories</a>
        <a href="<?php echo BASE_URL; ?>lore/"><span class="nav-icon">📚</span>Lore</a>
        <a href="<?php echo BASE_URL; ?>lore/"><span class="nav-icon">📜</span>History</a>
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

<script>
// --- Loading Screen ---
(function() {
    var loader = document.getElementById('opwiki-loader');
    if (loader) {
        loader.classList.add('active');
        window.addEventListener('load', function() {
            setTimeout(function() {
                loader.classList.remove('active');
                loader.style.display = 'none';
            }, 500);
        });
    }
})();

// --- Nav Toggle (Mobile) ---
(function() {
    var toggle = document.getElementById('nav-toggle');
    var nav = document.querySelector('nav');
    if (toggle && nav) {
        toggle.addEventListener('click', function() {
            nav.classList.toggle('open');
        });
    }
})();

// --- Dark Mode Toggle ---
(function() {
    var checkbox = document.getElementById('dark-mode-checkbox');
    var body = document.body;
    if (!checkbox) return;
    var saved = localStorage.getItem('opwiki-dark-mode');
    if (saved === 'light') {
        body.classList.add('light-mode');
        checkbox.checked = false;
    } else {
        body.classList.remove('light-mode');
        checkbox.checked = true;
    }
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            body.classList.remove('light-mode');
            localStorage.setItem('opwiki-dark-mode', 'dark');
        } else {
            body.classList.add('light-mode');
            localStorage.setItem('opwiki-dark-mode', 'light');
        }
    });
})();

// --- Quote Rotation ---
(function() {
    var el = document.getElementById('hero-quote');
    if (!el) return;
    var quotes = [
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
    var idx = 0;
    setInterval(function() {
        idx = (idx + 1) % quotes.length;
        el.style.opacity = 0;
        setTimeout(function() {
            el.textContent = quotes[idx];
            el.style.opacity = 1;
        }, 500);
    }, 8000);
})();

// --- Reading Progress Bar ---
(function() {
    var bar = document.getElementById('reading-progress');
    if (!bar) return;
    window.addEventListener('scroll', function() {
        var scrollTop = window.scrollY;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        if (docHeight <= 0) { bar.style.width = '0'; return; }
        var progress = (scrollTop / docHeight) * 100;
        bar.style.width = progress + '%';
    });
})();

// --- Toast Notifications ---
function showToast(msg) {
    var container = document.getElementById('toast-container');
    if (!container) return;
    var toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = msg;
    container.appendChild(toast);
    setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 3500);
}

// --- Ambient Audio ---
(function() {
    var btn = document.getElementById('ambient-toggle');
    if (!btn) return;
    var audioCtx, source, gain, playing = false;
    btn.addEventListener('click', function() {
        if (!playing) {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                gain = audioCtx.createGain();
                gain.gain.value = 0.08;
                gain.connect(audioCtx.destination);
                source = audioCtx.createBufferSource();
                playNoise();
            }
            btn.classList.add('playing');
            btn.textContent = '🔇';
            playing = true;
        } else {
            btn.classList.remove('playing');
            btn.textContent = '🌊';
            playing = false;
            if (source) source.stop();
        }
    });
    function playNoise() {
        if (!audioCtx || !playing) return;
        var sr = audioCtx.sampleRate;
        var len = sr * 2;
        var buf = audioCtx.createBuffer(1, len, sr);
        var data = buf.getChannelData(0);
        for (var i = 0; i < len; i++) {
            data[i] = (Math.random() * 2 - 1) * Math.pow(Math.random(), 0.5);
        }
        source = audioCtx.createBufferSource();
        source.buffer = buf;
        source.loop = true;
        source.connect(gain);
        source.start();
    }
})();

// --- Notifications (AJAX Poll) ---
(function() {
    var bell = document.getElementById('notif-bell');
    var countEl = document.getElementById('notif-count');
    var listEl = document.getElementById('notif-list');
    if (!bell || !countEl || !listEl) return;
    function fetchNotifs() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '<?php echo BASE_URL; ?>ajax/notif_count.php');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var d = JSON.parse(xhr.responseText);
                    countEl.textContent = d.count;
                    countEl.style.display = d.count > 0 ? 'inline' : 'none';
                } catch(e) {}
            }
        };
        xhr.send();
        var xhr2 = new XMLHttpRequest();
        xhr2.open('GET', '<?php echo BASE_URL; ?>ajax/notif_list.php');
        xhr2.onload = function() {
            if (xhr2.status === 200) {
                listEl.innerHTML = xhr2.responseText;
            }
        };
        xhr2.send();
    }
    fetchNotifs();
    setInterval(fetchNotifs, 30000);
})();

// --- Share Article ---
function shareArticle() {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(window.location.href).then(function() {
            showToast('🔗 Link copied to clipboard!');
        });
    } else {
        var ta = document.createElement('textarea');
        ta.value = window.location.href;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('🔗 Link copied to clipboard!');
    }
}

// --- Saga Accordion Toggle ---
(function() {
    document.addEventListener('click', function(e) {
        var header = e.target.closest('.saga-header');
        if (header) {
            var group = header.closest('.saga-group');
            if (group) {
                group.classList.toggle('open');
            }
        }
    });
})();

// --- Lore Card Toggle ---
(function() {
    document.addEventListener('click', function(e) {
        var card = e.target.closest('.lore-card');
        if (card) {
            card.classList.toggle('expanded');
        }
    });
})();

// --- TOC Scroll Tracking ---
(function() {
    function setupTOC(root) {
        var links = root.querySelectorAll('a');
        if (!links.length) return null;
        var headings = [];
        links.forEach(function(a) {
            var id = a.getAttribute('href').replace('#', '');
            var el = document.getElementById(id);
            if (el) headings.push({ el: el, link: a });
        });
        if (!headings.length) return null;
        return { links: links, headings: headings };
    }
    var sidebar = document.getElementById('article-sidebar');
    var tocFloat = document.getElementById('floating-toc');
    var tocData = null;
    if (sidebar && (tocData = setupTOC(sidebar))) {
        // article sidebar TOC
    } else if (tocFloat && (tocData = setupTOC(tocFloat))) {
        // theory floating TOC
    }
    if (!tocData) return;
    var links = tocData.links;
    var headings = tocData.headings;
    function updateActive() {
        var scrollY = window.scrollY + 120;
        var active = headings[0];
        for (var i = 0; i < headings.length; i++) {
            if (headings[i].el.offsetTop <= scrollY) {
                active = headings[i];
            }
        }
        links.forEach(function(a) { a.classList.remove('active'); });
        if (active) active.link.classList.add('active');
    }
    window.addEventListener('scroll', updateActive);
    updateActive();
})();

// --- Nav Dropdown Toggle (mobile) ---
(function() {
    var dropdowns = document.querySelectorAll('.nav-dropdown > a');
    dropdowns.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                this.parentElement.classList.toggle('open');
            }
        });
    });
})();

// --- Mobile Bottom Nav Highlight ---
(function() {
    var nav = document.getElementById('mobile-bottom-nav');
    if (!nav) return;
    var path = window.location.pathname;
    var links = nav.querySelectorAll('a');
    links.forEach(function(a) {
        var href = a.getAttribute('href');
        if (path === href || (href !== '/' && path.indexOf(href) === 0)) {
            a.classList.add('active');
        }
    });
})();

// --- Animate Paper (Morgans Newspaper Particles) ---
(function() {
    var page = document.getElementById('morgans-timeline');
    if (!page) return;
    var container = document.createElement('div');
    container.className = 'paper-particles';
    page.appendChild(container);
    function spawnPaper() {
        var el = document.createElement('span');
        el.className = 'paper-particle';
        el.textContent = '📰';
        el.style.left = Math.random() * 100 + '%';
        el.style.animationDuration = (6 + Math.random() * 8) + 's';
        el.style.fontSize = (0.8 + Math.random() * 1.2) + 'rem';
        el.style.opacity = 0.2 + Math.random() * 0.3;
        container.appendChild(el);
        setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 14000);
    }
    setInterval(spawnPaper, 4000);
    spawnPaper();
})();
</script>

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
