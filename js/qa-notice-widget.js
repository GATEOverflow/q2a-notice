document.addEventListener('DOMContentLoaded', function () {

    var cfg = window.QA_NOTICE_CONFIG || { mode: 'cards', interval: 10 };

    document.querySelectorAll('.qa-notice-widget').forEach(function (widget) {

        var scrollBox = widget.querySelector('.qa-notice-scroll');
        var track = scrollBox ? scrollBox.querySelector('.qa-notice-track') : null;
        if (!track) return;

        var userid = widget.dataset.userid || null;
        var storageKey = userid ? 'qa_notices_read_' + userid : null;
        var pauseStorageKey = 'qa_notice_paused';
        var allItems = Array.from(track.querySelectorAll('.qa-notice-item'));
        var allReadEl = widget.querySelector('.qa-notice-allread');
        var showAllEl = widget.querySelector('.qa-notice-show-all');
        var markAllBtn = widget.querySelector('.qa-notice-mark-all');
        var cardNav = widget.querySelector('.qa-notice-card-nav');

        // --- localStorage helpers ---
        function getReadSet() {
            if (!storageKey) return {};
            try { return JSON.parse(localStorage.getItem(storageKey)) || {}; }
            catch (e) { return {}; }
        }

        function markRead(noticeId) {
            if (!storageKey) return;
            var readSet = getReadSet();
            readSet[noticeId] = Date.now();
            localStorage.setItem(storageKey, JSON.stringify(readSet));
        }

        function clearAllRead() {
            if (!storageKey) return;
            localStorage.removeItem(storageKey);
        }

        function purgeStaleEntries() {
            if (!storageKey) return;
            var readSet = getReadSet();
            var activeIds = {};
            allItems.forEach(function (item) { activeIds[item.dataset.noticeId] = true; });
            var changed = false;
            for (var key in readSet) {
                if (!activeIds[key]) { delete readSet[key]; changed = true; }
            }
            if (changed) localStorage.setItem(storageKey, JSON.stringify(readSet));
        }

        // --- Filter notices based on read state ---
        var visibleItems = [];

        function applyReadFilter() {
            if (!userid) {
                visibleItems = allItems.slice();
                return;
            }
            purgeStaleEntries();
            var readSet = getReadSet();
            visibleItems = [];

            allItems.forEach(function (item) {
                var nid = item.dataset.noticeId;
                if (readSet[nid]) {
                    item.classList.add('qa-notice-read');
                    item.style.display = 'none';
                } else {
                    item.classList.remove('qa-notice-read');
                    item.style.display = '';
                    visibleItems.push(item);
                }
            });

            if (visibleItems.length === 0) {
                if (allReadEl) allReadEl.style.display = '';
                if (showAllEl) showAllEl.style.display = '';
                if (markAllBtn) markAllBtn.style.display = 'none';
                if (cardNav) cardNav.style.display = 'none';
            } else {
                if (allReadEl) allReadEl.style.display = 'none';
                if (showAllEl) showAllEl.style.display = 'none';
                if (markAllBtn) markAllBtn.style.display = '';
            }
        }

        function showAllNotices() {
            allItems.forEach(function (item) {
                item.classList.remove('qa-notice-read');
                item.style.display = '';
            });
            visibleItems = allItems.slice();
            if (allReadEl) allReadEl.style.display = 'none';
            if (showAllEl) showAllEl.style.display = 'none';
            if (markAllBtn) markAllBtn.style.display = '';
        }

        // --- Dismiss button ---
        if (userid) {
            track.addEventListener('click', function (e) {
                var btn = e.target.closest('.qa-notice-dismiss');
                if (!btn) return;
                e.preventDefault();
                e.stopPropagation();
                var item = btn.closest('.qa-notice-item');
                if (!item) return;
                markRead(item.dataset.noticeId);

                item.style.transition = 'opacity 0.3s, max-height 0.3s';
                item.style.opacity = '0';
                item.style.maxHeight = item.offsetHeight + 'px';
                item.style.overflow = 'hidden';
                setTimeout(function () {
                    item.style.maxHeight = '0';
                    item.style.padding = '0';
                    item.style.margin = '0';
                    item.style.border = 'none';
                }, 50);
                setTimeout(function () {
                    applyReadFilter();
                    item.style.transition = '';
                    item.style.opacity = '';
                    item.style.maxHeight = '';
                    item.style.overflow = '';
                    item.style.padding = '';
                    item.style.margin = '';
                    item.style.border = '';
                    if (cfg.mode === 'cards') initCards();
                    else resetScroll();
                }, 400);
            });

            if (showAllEl) {
                showAllEl.addEventListener('click', function (e) {
                    e.preventDefault();
                    clearAllRead();
                    showAllNotices();
                    if (cfg.mode === 'cards') initCards();
                    else resetScroll();
                });
            }

            if (markAllBtn) {
                markAllBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    allItems.forEach(function (item) { markRead(item.dataset.noticeId); });
                    applyReadFilter();
                    if (cfg.mode === 'cards') initCards();
                    else resetScroll();
                });
            }
        }

        // =============== CARDS MODE ===============
        var currentCard = 0;
        var cardTimer = null;
        var isPaused = false;

        function initCards() {
            if (cfg.mode !== 'cards') return;

            widget.classList.add('qa-notice-mode-cards');
            widget.classList.remove('qa-notice-mode-scroll');
            scrollBox.classList.add('qa-notice-no-fade');

            if (visibleItems.length <= 1) {
                if (cardNav) cardNav.style.display = 'none';
            } else {
                if (cardNav) cardNav.style.display = '';
            }

            // Lock container height to tallest card to prevent layout jumps
            scrollBox.style.height = 'auto';
            visibleItems.forEach(function (item) { item.style.display = ''; });
            var maxH = 0;
            visibleItems.forEach(function (item) {
                var h = item.offsetHeight;
                if (h > maxH) maxH = h;
            });
            if (maxH > 0) scrollBox.style.minHeight = (maxH + 20) + 'px';

            currentCard = Math.min(currentCard, Math.max(0, visibleItems.length - 1));
            showCardAt(currentCard, 'none');
            updateIndicator();

            isPaused = localStorage.getItem(pauseStorageKey) === '1';
            updatePauseBtn();
            startAutoplay();
        }

        function showCardAt(idx, direction) {
            visibleItems.forEach(function (item, i) {
                if (i === idx) {
                    item.style.display = '';
                    item.classList.remove('qa-notice-card-enter-left', 'qa-notice-card-enter-right');
                    if (direction !== 'none') {
                        void item.offsetWidth;
                        item.classList.add(direction === 'next' ? 'qa-notice-card-enter-right' : 'qa-notice-card-enter-left');
                        setTimeout(function () {
                            item.classList.remove('qa-notice-card-enter-left', 'qa-notice-card-enter-right');
                        }, 350);
                    }
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function nextCard() {
            if (visibleItems.length === 0) return;
            currentCard = (currentCard + 1) % visibleItems.length;
            showCardAt(currentCard, 'next');
            updateIndicator();
        }

        function prevCard() {
            if (visibleItems.length === 0) return;
            currentCard = (currentCard - 1 + visibleItems.length) % visibleItems.length;
            showCardAt(currentCard, 'prev');
            updateIndicator();
        }

        function updateIndicator() {
            var indicator = widget.querySelector('.qa-notice-indicator');
            if (indicator && visibleItems.length > 0) {
                indicator.textContent = (currentCard + 1) + ' / ' + visibleItems.length;
            }
        }

        function startAutoplay() {
            stopAutoplay();
            if (isPaused || visibleItems.length <= 1) return;
            cardTimer = setInterval(function () { nextCard(); }, cfg.interval * 1000);
        }

        function stopAutoplay() {
            if (cardTimer) { clearInterval(cardTimer); cardTimer = null; }
        }

        function togglePause() {
            isPaused = !isPaused;
            localStorage.setItem(pauseStorageKey, isPaused ? '1' : '0');
            updatePauseBtn();
            if (isPaused) stopAutoplay();
            else startAutoplay();
        }

        function updatePauseBtn() {
            var btn = widget.querySelector('.qa-notice-pause');
            if (!btn) return;
            btn.innerHTML = isPaused ? '&#9654;' : '&#9208;';
            btn.title = isPaused ? 'Resume auto-advance' : 'Pause auto-advance';
            btn.classList.toggle('qa-notice-pause-active', isPaused);
        }

        if (cardNav) {
            var prevBtn = cardNav.querySelector('.qa-notice-prev');
            var nextBtn = cardNav.querySelector('.qa-notice-next');
            var pauseBtn = cardNav.querySelector('.qa-notice-pause');
            if (prevBtn) prevBtn.addEventListener('click', function () { prevCard(); stopAutoplay(); startAutoplay(); });
            if (nextBtn) nextBtn.addEventListener('click', function () { nextCard(); stopAutoplay(); startAutoplay(); });
            if (pauseBtn) pauseBtn.addEventListener('click', function () { togglePause(); });
        }

        // =============== SCROLL MODE ===============
        var paused_scroll = false;
        var pos = 0;
        var direction = 1;
        var forward_speed = 0.75;
        var reverse_speed = 6;
        var speed = forward_speed;
        var animId = null;

        function initScroll() {
            if (cfg.mode !== 'scroll') return;

            widget.classList.add('qa-notice-mode-scroll');
            widget.classList.remove('qa-notice-mode-cards');
            if (cardNav) cardNav.style.display = 'none';

            scrollBox.addEventListener('mouseenter', function () { paused_scroll = true; });
            scrollBox.addEventListener('mouseleave', function () { paused_scroll = false; });

            resetScroll();
            requestAnimationFrame(step);
        }

        function resetScroll() {
            pos = 0;
            direction = 1;
            speed = forward_speed;
            track.style.transform = 'translateY(0px)';
        }

        function step() {
            if (!paused_scroll) {
                var maxScroll = track.scrollHeight - scrollBox.clientHeight;
                if (maxScroll <= 0) {
                    track.style.transform = 'translateY(0px)';
                    animId = requestAnimationFrame(step);
                    return;
                }
                pos += direction * speed;
                if (pos >= maxScroll) { pos = maxScroll; direction = -1; speed = reverse_speed; }
                if (pos <= 0) { pos = 0; direction = 1; speed = forward_speed; }
                track.style.transform = 'translateY(-' + pos + 'px)';
            }
            animId = requestAnimationFrame(step);
        }

        // =============== INIT ===============
        applyReadFilter();

        if (cfg.mode === 'cards') {
            initCards();
        } else {
            initScroll();
        }
    });

});
