/**
 * Tap a photo on a celebration page, see the whole photo.
 *
 * Wishes arrive with pictures in them, and on the wall those pictures are a
 * thumbnail cropped to fit the message. The picture somebody chose to send is
 * worth more than that, so any image marked `zoomable` opens over the page —
 * at its own size, in a panel, not stretched across the whole screen.
 *
 * Delegated from the document rather than bound per image: the wall grows as
 * wishes are posted, and the cover photos live inside a carousel that moves
 * them around.
 */

const BOX_ID = 'image-lightbox';

let box;
let picture;
let lastFocused = null;

function build() {
    if (box) {
        return box;
    }

    box = document.createElement('div');
    box.id = BOX_ID;
    box.className = 'lightbox';
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.setAttribute('aria-label', 'Photo');
    box.hidden = true;
    box.style.display = 'none';

    box.innerHTML = `
        <div class="lightbox-panel">
            <img class="lightbox-img" alt="">
            <button type="button" class="lightbox-close" aria-label="Close photo">
                <i class="mdi mdi-close"></i>
            </button>
        </div>
    `;

    picture = box.querySelector('.lightbox-img');

    // Anywhere outside the photo closes it, which is what people try first.
    // The close button is not the photo either, so it needs nothing of its own.
    box.addEventListener('click', (event) => {
        if (event.target !== picture) {
            close();
        }
    });

    document.body.appendChild(box);

    return box;
}

function open(source, alt) {
    build();

    lastFocused = document.activeElement;

    picture.src = source;
    picture.alt = alt || '';

    box.hidden = false;
    // Set on the element as well as through `hidden`, so the sheet lifts even
    // on a page that does not carry the stylesheet.
    box.style.display = 'flex';
    // The page behind must not scroll away under the photo.
    document.body.style.overflow = 'hidden';

    box.querySelector('.lightbox-close').focus();
}

function close() {
    if (! box || box.hidden) {
        return;
    }

    box.hidden = true;
    box.style.display = 'none';
    document.body.style.overflow = '';

    // Free the memory a large photo holds; the next open sets it again.
    picture.removeAttribute('src');

    lastFocused?.focus?.();
    lastFocused = null;
}

document.addEventListener('click', (event) => {
    const image = event.target.closest('img.zoomable, .zoomable img');

    if (! image) {
        return;
    }

    // Inside the owner's settings a photo sits under a remove button; that
    // button must still do its own job.
    if (event.target.closest('button, a')) {
        return;
    }

    event.preventDefault();

    // The full picture, not the cropped thumbnail, when the two differ.
    open(image.dataset.full || image.currentSrc || image.src, image.alt);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        close();
    }
});

window.closeImageLightbox = close;
