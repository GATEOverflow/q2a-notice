document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.qa-notice-widget').forEach(widget => {

        const scrollBox = widget.querySelector('.qa-notice-scroll');
        const track = scrollBox ? scrollBox.querySelector('.qa-notice-track') : null;
        if (!track) return;

        const userid = widget.dataset.userid || null;
        const storageKey = userid ? 'qa_notices_read_' + userid : null;
        const allItems = Array.from(track.querySelectorAll('.qa-notice-item'));
        const allReadEl = widget.querySelector('.qa-notice-allread');
        const showAllEl = widget.querySelector('.qa-notice-show-all');
        const markAllBtn = widget.querySelector('.qa-notice-mark-all');

        // --- localStorage helpers (only for logged-in users) ---
        function getReadSet() {
            if (!storageKey) return {};
            try {
                return JSON.parse(localStorage.getItem(storageKey)) || {};
            } catch (e) {
                return {};
            }
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

        // Remove localStorage entries for notices no longer on the page
        // (admin deleted or expired them)
        function purgeStaleEntries() {
            if (!storageKey) return;
            var readSet = getReadSet();
            var activeIds = {};
            allItems.forEach(function (item) {
                activeIds[item.dataset.noticeId] = true;
            });
            var changed = false;
            for (var key in readSet) {
                if (!activeIds[key]) {
                    delete readSet[key];
                    changed = true;
                }
            }
            if (changed) localStorage.setItem(storageKey, JSON.stringify(readSet));
        }

        // --- Filter notices based on read state ---
        function applyReadFilter() {
            if (!userid) return; // guests see everything
            purgeStaleEntries();
            var readSet = getReadSet();
            var unreadCount = 0;

            allItems.forEach(function (item) {
                var nid = item.dataset.noticeId;
                if (readSet[nid]) {
                    item.classList.add('qa-notice-read');
                    item.style.display = 'none';
                } else {
                    item.classList.remove('qa-notice-read');
                    item.style.display = '';
                    unreadCount++;
                }
            });

            if (unreadCount === 0) {
                // All read — show banner + "Show all" link
                if (allReadEl) allReadEl.style.display = '';
                if (showAllEl) showAllEl.style.display = '';
                if (markAllBtn) markAllBtn.style.display = 'none';
            } else {
                if (allReadEl) allReadEl.style.display = 'none';
                if (showAllEl) showAllEl.style.display = 'none';
                if (markAllBtn) markAllBtn.style.display = '';
            }

            resetScroll();
        }

        function showAllNotices(showBanner) {
            allItems.forEach(function (item) {
                item.classList.remove('qa-notice-read');
                item.style.display = '';
            });
            if (showBanner && allReadEl) allReadEl.style.display = '';
            if (showAllEl) showAllEl.style.display = 'none';
            if (markAllBtn) markAllBtn.style.display = '';
            resetScroll();
        }

        // --- Dismiss button handling ---
        if (userid) {
            track.addEventListener('click', function (e) {
                var btn = e.target.closest('.qa-notice-dismiss');
                if (!btn) return;
                e.preventDefault();
                e.stopPropagation();
                var item = btn.closest('.qa-notice-item');
                if (!item) return;
                var nid = item.dataset.noticeId;
                markRead(nid);

                // Fade out then refilter
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
                    // Restore inline styles for when items are shown again
                    item.style.transition = '';
                    item.style.opacity = '';
                    item.style.maxHeight = '';
                    item.style.overflow = '';
                    item.style.padding = '';
                    item.style.margin = '';
                    item.style.border = '';
                }, 400);
            });

            // "Show all notices" link
            if (showAllEl) {
                showAllEl.addEventListener('click', function (e) {
                    e.preventDefault();
                    clearAllRead();
                    showAllNotices(false);
                });
            }

            // "Mark all as read" button
            if (markAllBtn) {
                markAllBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    allItems.forEach(function (item) {
                        var nid = item.dataset.noticeId;
                        markRead(nid);
                    });
                    applyReadFilter();
                });
            }
        }

        // --- Scrolling animation ---
        var paused = false;
        var pos = 0;
        var direction = 1;
        var forward_speed = 0.75;
        var reverse_speed = 6;
        var speed = forward_speed;
        var animId = null;

        scrollBox.addEventListener('mouseenter', function () { paused = true; });
        scrollBox.addEventListener('mouseleave', function () { paused = false; });

        function resetScroll() {
            pos = 0;
            direction = 1;
            speed = forward_speed;
            track.style.transform = 'translateY(0px)';
        }

        function step() {
            if (!paused) {
                var maxScroll = track.scrollHeight - scrollBox.clientHeight;

                if (maxScroll <= 0) {
                    // Content fits — no scrolling needed
                    track.style.transform = 'translateY(0px)';
                    animId = requestAnimationFrame(step);
                    return;
                }

                pos += direction * speed;

                if (pos >= maxScroll) {
                    pos = maxScroll;
                    direction = -1;
                    speed = reverse_speed;
                }

                if (pos <= 0) {
                    pos = 0;
                    direction = 1;
                    speed = forward_speed;
                }

                track.style.transform = 'translateY(-' + pos + 'px)';
            }

            animId = requestAnimationFrame(step);
        }

        // --- Init ---
        applyReadFilter();
        requestAnimationFrame(step);
    });

});
