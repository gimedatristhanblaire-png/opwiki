// ─── Browse Page JS ───
(function() {

    var tabs = document.querySelectorAll('.lore-filter-tab');
    var results = document.getElementById('lore-results');
    if (!tabs.length || !results) return;

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var type = this.getAttribute('data-type');
            tabs.forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');
            results.innerHTML = '<div class="lore-empty">\u23f3 Loading...</div>';
            var xhr = new XMLHttpRequest();
            xhr.open('GET', window.BASE_URL + 'lore/ajax_browse.php?type=' + type);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    results.innerHTML = xhr.responseText;
                    attachCardToggle();
                }
            };
            xhr.send();
            var url = new URL(window.location);
            url.searchParams.set('type', type);
            window.history.replaceState({}, '', url);
            document.querySelectorAll('.lore-type-stat').forEach(function(s) {
                s.classList.toggle('active', s.getAttribute('data-type') === type);
            });
        });
    });

    var searchInput = document.getElementById('lore-search-input');
    var searchClear = document.getElementById('lore-search-clear');
    var searchTimer = null;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            var q = this.value.trim();
            if (q.length === 0) {
                if (searchClear) searchClear.style.display = 'none';
                var activeTab = document.querySelector('.lore-filter-tab.active');
                if (activeTab) activeTab.click();
                return;
            }
            if (searchClear) searchClear.style.display = 'inline';
            searchTimer = setTimeout(function() {
                var type = document.querySelector('.lore-filter-tab.active').getAttribute('data-type');
                results.innerHTML = '<div class="lore-empty">\ud83d\udd0d Searching...</div>';
                var xhr = new XMLHttpRequest();
                xhr.open('GET', window.BASE_URL + 'lore/ajax_browse.php?type=' + type + '&q=' + encodeURIComponent(q));
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        results.innerHTML = xhr.responseText;
                        attachCardToggle();
                    }
                };
                xhr.send();
            }, 300);
        });
    }
    if (searchClear) {
        searchClear.addEventListener('click', function() {
            if (searchInput) { searchInput.value = ''; searchInput.dispatchEvent(new Event('input')); }
            this.style.display = 'none';
        });
    }

    function attachCardToggle() {
        document.querySelectorAll('.lore-card-expand-v2').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                this.closest('.lore-card-v2').classList.toggle('expanded');
            });
        });
    }
    attachCardToggle();

    document.querySelectorAll('.lore-type-stat').forEach(function(s) {
        s.addEventListener('click', function() {
            var type = this.getAttribute('data-type');
            document.querySelector('.lore-filter-tab[data-type="' + type + '"]').click();
        });
    });

})();
