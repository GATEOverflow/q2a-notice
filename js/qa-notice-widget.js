document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.qa-notice-scroll').forEach(scrollBox => {

        const track = scrollBox.querySelector('.qa-notice-track');
        if (!track) return;

        const items = [...track.children];
        if (items.length < 2) return;

        track.innerHTML += track.innerHTML;

        let totalHeight = 0;
        items.forEach(el => {
            totalHeight += el.offsetHeight + 40;
        });

        let pos = 0;
        let paused = false;

        const speed = 0.35; // adjust speed here
        const resetDelay = 600; // ms delay before restarting loop

        scrollBox.addEventListener('mouseenter', () => paused = true);
        scrollBox.addEventListener('mouseleave', () => paused = false);

        function step() {
            if (!paused) {
                pos += speed;

                if (pos >= totalHeight) {
                    pos = -10;
                    track.style.transition = 'none';
                    track.style.transform = `translateY(-${pos}px)`;

                    // small delay before scrolling resumes smoothly
                    setTimeout(() => {
                        track.style.transition = 'transform 0.10s linear'; 
                    }, resetDelay);
                } else {
                    // normal scrolling
                    track.style.transition = 'transform 0.108s linear';
                    track.style.transform = `translateY(-${pos}px)`;
                }
            }

            requestAnimationFrame(step);
        }

        // initialize transition style
        track.style.transition = 'transform 0.108s linear';

        requestAnimationFrame(step);
    });

});
