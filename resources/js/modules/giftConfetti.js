/**
 * The confetti that falls when a gift lands.
 *
 * canvas-confetti is already a dependency and hangs off window (see app.js),
 * so this is only the choreography: one burst for the moment itself, then a
 * few seconds of fall from the top edge so the page keeps celebrating while
 * the gift is being written in.
 */

const FALL_MS  = 3200;
const BURST_MS = 250;

/** Purple through gold — the brand ramp, not the library's defaults. */
const COLOURS = ['#7c3aed', '#a680fc', '#ffb43a', '#f59e0b', '#ffffff', '#ded1ff'];

/**
 * @return {Promise<void>} resolves once the show has finished, so a caller can
 *         wait before navigating rather than cutting it off mid-fall.
 */
export function giftConfetti() {
    const confetti = window.confetti;

    if (typeof confetti !== 'function') {
        return Promise.resolve();
    }

    // Someone who asked for less motion gets a single quiet burst, not none —
    // they still deserve to know it worked.
    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
        confetti({ particleCount: 40, spread: 70, origin: { y: 0.6 }, colors: COLOURS, ticks: 120 });

        return new Promise((resolve) => setTimeout(resolve, 600));
    }

    // The moment itself: two arcs crossing in the middle.
    confetti({ particleCount: 140, spread: 100, startVelocity: 55, origin: { x: 0.25, y: 0.65 }, colors: COLOURS, angle: 60 });
    confetti({ particleCount: 140, spread: 100, startVelocity: 55, origin: { x: 0.75, y: 0.65 }, colors: COLOURS, angle: 120 });

    return new Promise((resolve) => {
        const end = Date.now() + FALL_MS;

        const fall = setInterval(() => {
            if (Date.now() > end) {
                clearInterval(fall);
                // Let the last pieces reach the floor before resolving.
                setTimeout(resolve, 900);

                return;
            }

            // Drifting down from both top corners, which reads as falling
            // rather than as something shot out of the middle of the page.
            confetti({ particleCount: 5, angle: 290, spread: 60, origin: { x: 0.05, y: 0 }, colors: COLOURS, scalar: 0.95 });
            confetti({ particleCount: 5, angle: 250, spread: 60, origin: { x: 0.95, y: 0 }, colors: COLOURS, scalar: 0.95 });
        }, BURST_MS);
    });
}

window.giftConfetti = giftConfetti;
