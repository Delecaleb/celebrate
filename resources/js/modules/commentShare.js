/**
 * Generates a shareable image that composites the celebrant photo
 * with the comment card, then triggers the native share sheet or
 * falls back to downloading the image.
 *
 * Usage:
 *   window.shareComment($buttonEl, {
 *     celebrantPhoto, commentText, authorName, celebrationTitle, accentColor
 *   })
 */

const W = 1080;
const H = 1080;

// ── Canvas helpers ──────────────────────────────────────────────────────────

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload  = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}

function drawCover(ctx, img) {
    const ia = img.naturalWidth / img.naturalHeight;
    const ca = W / H;
    let sx, sy, sw, sh;
    if (ia > ca) {
        sh = img.naturalHeight; sw = sh * ca;
        sx = (img.naturalWidth - sw) / 2; sy = 0;
    } else {
        sw = img.naturalWidth; sh = sw / ca;
        sx = 0; sy = (img.naturalHeight - sh) / 2;
    }
    ctx.drawImage(img, sx, sy, sw, sh, 0, 0, W, H);
}

function drawFallbackBg(ctx, accent) {
    const g = ctx.createLinearGradient(0, 0, W, H);
    g.addColorStop(0, '#F8FAFC');
    g.addColorStop(1, accent + '55');
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, W, H);
}

function roundRect(ctx, x, y, w, h, radii, fill) {
    const r = Array.isArray(radii) ? radii : [radii, radii, radii, radii];
    ctx.beginPath();
    ctx.moveTo(x + r[0], y);
    ctx.lineTo(x + w - r[1], y);
    ctx.arcTo(x + w, y,     x + w, y + r[1],     r[1]);
    ctx.lineTo(x + w, y + h - r[2]);
    ctx.arcTo(x + w, y + h, x + w - r[2], y + h, r[2]);
    ctx.lineTo(x + r[3], y + h);
    ctx.arcTo(x,     y + h, x, y + h - r[3],     r[3]);
    ctx.lineTo(x, y + r[0]);
    ctx.arcTo(x,     y,     x + r[0], y,          r[0]);
    ctx.closePath();
    if (fill) { ctx.fillStyle = fill; ctx.fill(); }
}

function wrapText(ctx, text, maxWidth) {
    const words = text.split(' ');
    const lines = [];
    let   line  = '';
    for (const word of words) {
        const test = line ? line + ' ' + word : word;
        if (ctx.measureText(test).width > maxWidth && line) {
            lines.push(line);
            line = word;
        } else {
            line = test;
        }
    }
    if (line) lines.push(line);
    return lines;
}

// ── Image builder ───────────────────────────────────────────────────────────

async function buildCanvas({ celebrantPhoto, commentText, authorName, celebrationTitle, accentColor }) {
    const accent  = accentColor || '#F43F5E';
    const canvas  = document.createElement('canvas');
    canvas.width  = W;
    canvas.height = H;
    const ctx     = canvas.getContext('2d');

    // 1. Background photo
    if (celebrantPhoto) {
        try { drawCover(ctx, await loadImage(celebrantPhoto)); }
        catch { drawFallbackBg(ctx, accent); }
    } else {
        drawFallbackBg(ctx, accent);
    }

    // 2. Dark gradient overlay — only the lower part, so the celebrant stays
    //    visible now that the card no longer covers half the frame.
    const grad = ctx.createLinearGradient(0, H * 0.42, 0, H);
    grad.addColorStop(0,   'rgba(0,0,0,0)');
    grad.addColorStop(0.6, 'rgba(0,0,0,0.42)');
    grad.addColorStop(1,   'rgba(0,0,0,0.80)');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, W, H);

    /*
     * 3. Card geometry.
     *
     * The card is the guest's message; the picture is the point. It is sized to
     * its content and HARD CAPPED at a third of the image, anchored to the
     * bottom so the celebrant always keeps the top two thirds.
     */
    const MAX_CH = Math.floor(H / 3);
    const PAD    = 52;
    const IP     = 34;                  // inner padding
    const cx     = PAD;
    const cw     = W - PAD * 2;

    const TITLE_H  = 24;
    const TITLE_GAP = 14;
    const AUTHOR_H = 26;
    const AUTHOR_GAP = 14;
    const LINE_H   = 46;

    // How much vertical room the message itself may occupy inside the cap.
    const bodyBudget = MAX_CH - IP * 2 - TITLE_H - TITLE_GAP - AUTHOR_GAP - AUTHOR_H;
    const MAX_LINES  = Math.max(1, Math.floor(bodyBudget / LINE_H));

    // Measure with the same font the message is drawn in.
    ctx.save();
    ctx.font = `400 34px system-ui, -apple-system, BlinkMacSystemFont, sans-serif`;
    const maxTextW = cw - IP * 2;
    const rawLines = wrapText(ctx, (commentText || '').trim(), maxTextW);
    const visLines = rawLines.slice(0, MAX_LINES);

    if (rawLines.length > MAX_LINES) {
        let last = visLines[MAX_LINES - 1];
        while (ctx.measureText(last + '…').width > maxTextW && last.length > 1) {
            last = last.slice(0, -1);
        }
        visLines[MAX_LINES - 1] = last + '…';
    }
    ctx.restore();

    const ch = Math.min(
        MAX_CH,
        IP * 2 + TITLE_H + TITLE_GAP + visLines.length * LINE_H + AUTHOR_GAP + AUTHOR_H
    );
    const cy = H - 64 - ch;   // sits above the watermark

    // 3a. Card drop shadow
    ctx.save();
    ctx.shadowColor   = 'rgba(0,0,0,0.28)';
    ctx.shadowBlur    = 44;
    ctx.shadowOffsetY = 12;
    roundRect(ctx, cx, cy, cw, ch, 28, 'rgba(255,255,255,0.97)');
    ctx.restore();

    // 3b. Accent colour stripe at top of card
    roundRect(ctx, cx, cy, cw, 7, [28, 28, 0, 0], accent);

    // 4. Celebration title (above comment)
    ctx.save();
    ctx.textBaseline = 'top';
    ctx.textAlign    = 'left';
    ctx.fillStyle    = accent;
    ctx.font         = `700 20px system-ui, -apple-system, BlinkMacSystemFont, sans-serif`;
    const titleStr   = celebrationTitle.length > 48
        ? celebrationTitle.slice(0, 48) + '…'
        : celebrationTitle;
    ctx.fillText(titleStr.toUpperCase(), cx + IP, cy + IP);
    ctx.restore();

    // 5. Decorative opening quote (faded)
    ctx.save();
    ctx.globalAlpha  = 0.09;
    ctx.fillStyle    = accent;
    ctx.font         = `700 96px Georgia, 'Times New Roman', serif`;
    ctx.textBaseline = 'top';
    ctx.fillText('“', cx + IP - 8, cy + IP - 6);
    ctx.restore();

    // 6. Comment text
    ctx.save();
    ctx.fillStyle    = '#111827';
    ctx.font         = `400 34px system-ui, -apple-system, BlinkMacSystemFont, sans-serif`;
    ctx.textBaseline = 'top';
    ctx.textAlign    = 'left';

    let ty = cy + IP + TITLE_H + TITLE_GAP;
    for (const line of visLines) {
        ctx.fillText(line, cx + IP, ty);
        ty += LINE_H;
    }
    ctx.restore();

    // 7. Author name
    ctx.save();
    ctx.fillStyle    = '#6B7280';
    ctx.font         = `600 24px system-ui, -apple-system, BlinkMacSystemFont, sans-serif`;
    ctx.textBaseline = 'bottom';
    ctx.textAlign    = 'left';
    ctx.fillText('— ' + authorName, cx + IP, cy + ch - IP);
    ctx.restore();

    // 8. Branding watermark
    ctx.save();
    ctx.fillStyle    = 'rgba(255,255,255,0.50)';
    ctx.font         = `500 20px system-ui, -apple-system, sans-serif`;
    ctx.textAlign    = 'center';
    ctx.textBaseline = 'bottom';
    ctx.fillText('shared via Celebrate', W / 2, H - 24);
    ctx.restore();

    return canvas;
}

// ── Public API ──────────────────────────────────────────────────────────────

/**
 * Share a wish as an image.
 *
 * Preference order:
 *   1. native share sheet WITH the image attached — the real thing, this is
 *      what puts the picture into WhatsApp, Instagram, X and the rest;
 *   2. native share sheet with just a link, for browsers that have share()
 *      but refuse files;
 *   3. our own sheet of social links, with the image offered as a download.
 *
 * Worth knowing: navigator.share only exists in a SECURE CONTEXT. Served over
 * plain http:// it is undefined everywhere except localhost, so every visitor
 * lands on (3) no matter how good their browser is. Serving over https:// is
 * what turns the real share sheet on.
 */
export async function shareComment(buttonEl, data) {
    // Loading state
    const icon = buttonEl.querySelector('i');
    const prev = icon?.className ?? '';
    if (icon) icon.className = 'mdi mdi-loading mdi-spin text-base';
    buttonEl.disabled = true;

    const pageUrl = window.location.href;
    const text    = `"${data.commentText}" — ${data.authorName}`;

    try {
        const canvas = await buildCanvas(data);
        const blob   = await new Promise(res => canvas.toBlob(res, 'image/jpeg', 0.92));
        const file   = new File([blob], 'celebration-wish.jpg', { type: 'image/jpeg' });

        // 1. native sheet, image attached
        if (navigator.canShare?.({ files: [file] })) {
            try {
                await navigator.share({
                    files: [file],
                    title: data.celebrationTitle,
                    text,
                });
                return;
            } catch (err) {
                if (err.name === 'AbortError') return;   // user closed the sheet
                // otherwise fall through
            }
        }

        // 2. native sheet, link only
        if (typeof navigator.share === 'function') {
            try {
                await navigator.share({ title: data.celebrationTitle, text, url: pageUrl });
                return;
            } catch (err) {
                if (err.name === 'AbortError') return;
            }
        }

        // 3. our own sheet — the page listens for this and shows the options
        window.dispatchEvent(new CustomEvent('wish-share-fallback', {
            detail: {
                imageUrl: canvas.toDataURL('image/jpeg', 0.92),
                title:    data.celebrationTitle,
                text,
                pageUrl,
                // true when the browser could have done it but the page is not
                // on https — worth telling the owner rather than the guest
                blockedByHttp: ! window.isSecureContext,
            },
        }));

    } finally {
        if (icon) icon.className = prev;
        buttonEl.disabled = false;
    }
}

window.shareComment = shareComment;
