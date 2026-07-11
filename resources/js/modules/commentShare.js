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

    // 2. Dark gradient overlay (top → bottom)
    const grad = ctx.createLinearGradient(0, H * 0.15, 0, H);
    grad.addColorStop(0,   'rgba(0,0,0,0)');
    grad.addColorStop(0.5, 'rgba(0,0,0,0.55)');
    grad.addColorStop(1,   'rgba(0,0,0,0.88)');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, W, H);

    // 3. Card geometry
    const PAD = 52;
    const cx  = PAD,            cy  = Math.round(H * 0.37);
    const cw  = W - PAD * 2,   ch  = Math.round(H * 0.53);
    const IP  = 44;             // inner padding

    // 3a. Card drop shadow
    ctx.save();
    ctx.shadowColor   = 'rgba(0,0,0,0.28)';
    ctx.shadowBlur    = 52;
    ctx.shadowOffsetY = 14;
    roundRect(ctx, cx, cy, cw, ch, 32, 'rgba(255,255,255,0.97)');
    ctx.restore();

    // 3b. Accent colour stripe at top of card
    roundRect(ctx, cx, cy, cw, 8, [32, 32, 0, 0], accent);

    // 4. Celebration title (above comment)
    ctx.save();
    ctx.textBaseline = 'top';
    ctx.textAlign    = 'left';
    ctx.fillStyle    = accent;
    ctx.font         = `700 24px system-ui, -apple-system, BlinkMacSystemFont, sans-serif`;
    const titleStr   = celebrationTitle.length > 48
        ? celebrationTitle.slice(0, 48) + '…'
        : celebrationTitle;
    ctx.fillText(titleStr.toUpperCase(), cx + IP, cy + 24);
    ctx.restore();

    // 5. Decorative opening quote (faded)
    ctx.save();
    ctx.globalAlpha = 0.10;
    ctx.fillStyle   = accent;
    ctx.font        = `700 140px Georgia, 'Times New Roman', serif`;
    ctx.textBaseline = 'top';
    ctx.fillText('“', cx + IP - 12, cy + 20);
    ctx.restore();

    // 6. Comment text (max 4 lines)
    ctx.save();
    ctx.fillStyle    = '#111827';
    ctx.font         = `400 40px system-ui, -apple-system, BlinkMacSystemFont, sans-serif`;
    ctx.textBaseline = 'top';
    ctx.textAlign    = 'left';

    const MAX_LINES  = 4;
    const LINE_H     = 56;
    const maxTextW   = cw - IP * 2;
    const rawLines   = wrapText(ctx, (commentText || '').trim(), maxTextW);
    let   visLines   = rawLines.slice(0, MAX_LINES);

    if (rawLines.length > MAX_LINES) {
        let last = visLines[MAX_LINES - 1];
        while (ctx.measureText(last + '…').width > maxTextW && last.length > 1) {
            last = last.slice(0, -1);
        }
        visLines[MAX_LINES - 1] = last + '…';
    }

    let ty = cy + IP + 52;
    for (const line of visLines) {
        ctx.fillText(line, cx + IP, ty);
        ty += LINE_H;
    }
    ctx.restore();

    // 7. Author name
    ctx.save();
    ctx.fillStyle    = '#6B7280';
    ctx.font         = `600 28px system-ui, -apple-system, BlinkMacSystemFont, sans-serif`;
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

export async function shareComment(buttonEl, data) {
    // Loading state
    const icon = buttonEl.querySelector('i');
    const prev = icon?.className ?? '';
    if (icon) icon.className = 'mdi mdi-loading mdi-spin text-base';
    buttonEl.disabled = true;

    try {
        const canvas = await buildCanvas(data);

        // Try Web Share API (mobile / Chromium)
        if (typeof navigator.share === 'function') {
            try {
                const blob = await new Promise(res => canvas.toBlob(res, 'image/jpeg', 0.92));
                const file = new File([blob], 'celebration-wish.jpg', { type: 'image/jpeg' });
                if (navigator.canShare?.({ files: [file] })) {
                    await navigator.share({
                        files: [file],
                        title: data.celebrationTitle,
                        text:  `"${data.commentText}" — ${data.authorName}`,
                    });
                    return;
                }
            } catch (err) {
                if (err.name === 'AbortError') return; // user cancelled
                // fall through to download
            }
        }

        // Fallback: download the image
        const a    = document.createElement('a');
        a.download = 'celebration-wish.jpg';
        a.href     = canvas.toDataURL('image/jpeg', 0.92);
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

    } finally {
        if (icon) icon.className = prev;
        buttonEl.disabled = false;
    }
}

window.shareComment = shareComment;
