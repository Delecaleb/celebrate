import './bootstrap';

import Alpine    from 'alpinejs';
import confetti  from 'canvas-confetti';
import flatpickr from 'flatpickr';

// ── Feature modules ────────────────────────────────────────────────────────
import './modules/alert-message';       // Global showAlert() helper
import './modules/add_cover_img';       // Cover photo upload handler
import './modules/submit_comment';      // wishForm() Alpine component
import './modules/giftPlate';               // giftPlate() Alpine component
import './modules/giftConfetti';            // giftConfetti() — the gift celebration
import './modules/wishlistForm';            // wishlistForm() Alpine component
import './modules/wishContributionModal';   // wishContributionModal() Alpine component
import './modules/celebrationCustomizer'; // celebrationCustomizer() Alpine component
import './modules/commentShare';          // shareComment() — comment-to-image share
import './modules/photobookGenerator';    // photobookGenerator() Alpine component
import './modules/pageRouter';            // AJAX navigation for the marketing site and dashboard
import './modules/slugEditor';            // slugEditor() — custom celebration URL
import './modules/phoneInput';          // phoneInput() — phone field with country picker
import './modules/imageLightbox';       // tap a photo on a celebration page to see all of it

// ── Global registrations ───────────────────────────────────────────────────
window.confetti = confetti;
window.Alpine   = Alpine;

Alpine.start();

// ── Third-party init ───────────────────────────────────────────────────────

/**
 * Initialise flatpickr on any input with the 'datepicker' class.
 *
 * The `:not(.flatpickr-input)` guard skips inputs flatpickr has already taken
 * over, so this is safe to call more than once.
 */
function initDatepickers(root = document) {
    const inputs = root.querySelectorAll('.datepicker:not(.flatpickr-input)');
    if (!inputs.length) return;

    flatpickr(inputs, { dateFormat: 'Y-m-d' });
}

document.addEventListener('DOMContentLoaded', () => initDatepickers());

// Pages swapped in by the AJAX router may bring their own date inputs.
window.addEventListener('route-changed', () => initDatepickers());
