import './bootstrap';

import Alpine    from 'alpinejs';
import confetti  from 'canvas-confetti';
import flatpickr from 'flatpickr';

// ── Feature modules ────────────────────────────────────────────────────────
import './modules/alert-message';       // Global showAlert() helper
import './modules/add_cover_img';       // Cover photo upload handler
import './modules/submit_comment';      // wishForm() Alpine component
import './modules/giftPlate';               // giftPlate() Alpine component
import './modules/wishlistForm';            // wishlistForm() Alpine component
import './modules/wishContributionModal';   // wishContributionModal() Alpine component
import './modules/celebrationCustomizer'; // celebrationCustomizer() Alpine component
import './modules/commentShare';          // shareComment() — comment-to-image share
import './modules/photobookGenerator';    // photobookGenerator() Alpine component

// ── Global registrations ───────────────────────────────────────────────────
window.confetti = confetti;
window.Alpine   = Alpine;

Alpine.start();

// ── Third-party init ───────────────────────────────────────────────────────

// Initialise flatpickr on any input with the 'datepicker' class
document.addEventListener('DOMContentLoaded', () => {
    flatpickr('.datepicker', { dateFormat: 'Y-m-d' });
});
