document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.qa-notice-scroll').forEach(scrollBox => {

        const track = scrollBox.querySelector('.qa-notice-track');
        if (!track) return;

        let paused = false;
        let pos = 0;
        let direction = 1;            // 1 = down, -1 = up
        const forward_speed = 0.85;           // pixels per frame
		const reverse_speed = 10;           // pixels per frame
		let speed = forward_speed;

        scrollBox.addEventListener('mouseenter', () => paused = true);
        scrollBox.addEventListener('mouseleave', () => paused = false);

        function step() {
            if (!paused) {
                pos += direction * speed;

                const maxScroll = track.scrollHeight - scrollBox.clientHeight;

                // reached bottom → reverse direction
                if (pos >= maxScroll) {
                    pos = maxScroll;
                    direction = -1;
					speed=reverse_speed;
                }

                // reached top → reverse direction
                if (pos <= 0) {
                    pos = 0;
                    direction = 1;
					speed=forward_speed;
                }

                track.style.transform = `translateY(-${pos}px)`;
            }

            requestAnimationFrame(step);
        }

        requestAnimationFrame(step);
    });

});
