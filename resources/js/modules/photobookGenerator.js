/**
 * Photobook Generator
 *
 * Renders a multi-page photobook from celebration comments entirely on the
 * client side using the Canvas 2D API.  Each page is stored as a JPEG
 * data-URL and displayed in a preview carousel; individual pages or the
 * whole book can be downloaded as images or a single PDF.
 *
 * Alpine component: photobookGenerator(config)
 * Triggered by:    window.photobookGenerator = photobookGenerator
 */

import { jsPDF } from 'jspdf';

// ─────────────────────────────────────────────────────────────────────────────
// Shared canvas helpers
// ─────────────────────────────────────────────────────────────────────────────

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload  = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}

/** Draw an image cover-cropped (object-fit: cover) into a rectangle. */
function drawCover(ctx, img, x, y, w, h) {
    const ia = img.naturalWidth / img.naturalHeight;
    const ca = w / h;
    let sx, sy, sw, sh;
    if (ia > ca) {
        sh = img.naturalHeight; sw = sh * ca;
        sx = (img.naturalWidth - sw) / 2; sy = 0;
    } else {
        sw = img.naturalWidth; sh = sw / ca;
        sx = 0; sy = (img.naturalHeight - sh) / 2;
    }
    ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
}

/** Draw a rounded rectangle, optionally filling it. */
function roundRect(ctx, x, y, w, h, r, fill, stroke) {
    const rad = typeof r === 'number' ? [r, r, r, r] : r;
    ctx.beginPath();
    ctx.moveTo(x + rad[0], y);
    ctx.lineTo(x + w - rad[1], y);
    ctx.arcTo(x + w, y,     x + w, y + rad[1],     rad[1]);
    ctx.lineTo(x + w, y + h - rad[2]);
    ctx.arcTo(x + w, y + h, x + w - rad[2], y + h, rad[2]);
    ctx.lineTo(x + rad[3], y + h);
    ctx.arcTo(x,     y + h, x, y + h - rad[3],     rad[3]);
    ctx.lineTo(x, y + rad[0]);
    ctx.arcTo(x,     y,     x + rad[0], y,          rad[0]);
    ctx.closePath();
    if (fill)   { ctx.fillStyle = fill;     ctx.fill();   }
    if (stroke) { ctx.strokeStyle = stroke; ctx.stroke(); }
}

/** Word-wrap text and return an array of lines, capped at maxLines. */
function wrapText(ctx, text, maxWidth, maxLines) {
    const words = String(text || '').trim().split(' ');
    const lines = [];
    let line = '';
    for (const word of words) {
        const test = line ? `${line} ${word}` : word;
        if (ctx.measureText(test).width > maxWidth && line) {
            lines.push(line);
            if (maxLines && lines.length >= maxLines - 1) {
                // last allowed line — join remaining with ellipsis
                const rest = words.slice(words.indexOf(word)).join(' ');
                let last = rest;
                while (ctx.measureText(last + '…').width > maxWidth && last.length > 1) {
                    last = last.slice(0, -1);
                }
                lines.push(last + (rest !== last ? '…' : ''));
                return lines;
            }
            line = word;
        } else {
            line = test;
        }
    }
    if (line) lines.push(line);
    return maxLines ? lines.slice(0, maxLines) : lines;
}

/** Draw a circular avatar — image if URL loads, otherwise coloured initials. */
async function drawAvatar(ctx, url, name, cx, cy, radius) {
    const safeR    = Math.max(1, Math.round(radius));
    const initial  = (name || '?').charAt(0).toUpperCase();
    const palette  = ['#7C3AED','#A855F7','#EC4899','#F59E0B','#10B981','#3B82F6','#EF4444'];
    const bgColor  = palette[(initial.charCodeAt(0)) % palette.length];

    let drawn = false;
    if (url) {
        try {
            const img = await loadImage(url);
            ctx.save();
            ctx.beginPath();
            ctx.arc(cx, cy, safeR, 0, Math.PI * 2);
            ctx.clip();
            ctx.drawImage(img, cx - safeR, cy - safeR, safeR * 2, safeR * 2);
            ctx.restore();
            drawn = true;
        } catch { /* fall through to initials */ }
    }

    if (!drawn) {
        ctx.save();
        ctx.beginPath();
        ctx.arc(cx, cy, safeR, 0, Math.PI * 2);
        ctx.fillStyle = bgColor;
        ctx.fill();
        ctx.fillStyle   = '#ffffff';
        ctx.font        = `700 ${Math.round(safeR * 0.72)}px system-ui,sans-serif`;
        ctx.textAlign   = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(initial, cx, cy + 1);
        ctx.restore();
    }

    // White ring
    ctx.save();
    ctx.beginPath();
    ctx.arc(cx, cy, safeR, 0, Math.PI * 2);
    ctx.strokeStyle = 'rgba(255,255,255,0.7)';
    ctx.lineWidth   = Math.max(2, Math.round(safeR * 0.1));
    ctx.stroke();
    ctx.restore();
}

// ─────────────────────────────────────────────────────────────────────────────
// COVER PAGE
// ─────────────────────────────────────────────────────────────────────────────

async function buildCoverPage(W, H, cfg) {
    const canvas = document.createElement('canvas');
    canvas.width  = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');
    const accent = cfg.accentColor || '#7C3AED';

    // ── Background ───────────────────────────────────────────────────────────
    if (cfg.coverPhoto) {
        try {
            const bg = await loadImage(cfg.coverPhoto);
            drawCover(ctx, bg, 0, 0, W, H);
        } catch {
            drawGradientBg(ctx, W, H, accent);
        }
    } else {
        drawGradientBg(ctx, W, H, accent);
    }

    // Dark vignette
    const vig = ctx.createLinearGradient(0, 0, 0, H);
    vig.addColorStop(0,   'rgba(0,0,0,0.10)');
    vig.addColorStop(0.4, 'rgba(0,0,0,0.45)');
    vig.addColorStop(1,   'rgba(0,0,0,0.80)');
    ctx.fillStyle = vig;
    ctx.fillRect(0, 0, W, H);

    // ── Decorative dot grid (subtle) ─────────────────────────────────────────
    ctx.save();
    ctx.globalAlpha = 0.08;
    ctx.fillStyle   = '#ffffff';
    const dot = Math.round(W * 0.008);
    const gap = Math.round(W * 0.06);
    for (let rx = gap; rx < W; rx += gap) {
        for (let ry = gap; ry < H; ry += gap) {
            ctx.beginPath();
            ctx.arc(rx, ry, dot, 0, Math.PI * 2);
            ctx.fill();
        }
    }
    ctx.restore();

    // ── Centre content ───────────────────────────────────────────────────────
    const MID  = W / 2;
    const VMID = H / 2;
    const fs   = (s) => Math.round(W * s);  // font size helper

    // Glowing circle backdrop behind emoji
    ctx.save();
    const glow = ctx.createRadialGradient(MID, VMID - fs(0.15), 0, MID, VMID - fs(0.15), fs(0.25));
    glow.addColorStop(0, accent + '55');
    glow.addColorStop(1, 'transparent');
    ctx.fillStyle = glow;
    ctx.fillRect(0, 0, W, H);
    ctx.restore();

    // Emoji
    const emojiMap = {
        birthday:    '🎂', wedding:  '💍', graduation: '🎓',
        anniversary: '💑', memorial: '🕊️', default:   '🎉',
    };
    const emoji = emojiMap[cfg.celebrationType] || emojiMap.default;
    ctx.font         = `${fs(0.12)}px serif`;
    ctx.textAlign    = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(emoji, MID, VMID - fs(0.18));

    // Title
    ctx.save();
    ctx.fillStyle    = '#ffffff';
    ctx.font         = `800 ${fs(0.065)}px system-ui,sans-serif`;
    ctx.textAlign    = 'center';
    ctx.textBaseline = 'middle';
    ctx.shadowColor   = 'rgba(0,0,0,0.4)';
    ctx.shadowBlur    = fs(0.02);
    const titleLines = wrapText(ctx, cfg.celebrationTitle || 'Celebration', W * 0.80, 2);
    const titleLH    = fs(0.082);
    const titleTop   = VMID - ((titleLines.length - 1) * titleLH) / 2;
    titleLines.forEach((line, i) => ctx.fillText(line, MID, titleTop + i * titleLH));
    ctx.restore();

    // Accent divider
    const divW = fs(0.2);
    const divY = VMID + fs(0.085) + (titleLines.length - 1) * titleLH / 2 + fs(0.03);
    roundRect(ctx, MID - divW / 2, divY, divW, fs(0.006), fs(0.003), accent);

    // Subtitle
    ctx.save();
    ctx.fillStyle    = 'rgba(255,255,255,0.75)';
    ctx.font         = `500 ${fs(0.030)}px system-ui,sans-serif`;
    ctx.textAlign    = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('Wishes & Messages', MID, divY + fs(0.05));
    ctx.restore();

    // Date + celebrant
    if (cfg.eventDate || cfg.celebrantName) {
        ctx.save();
        ctx.fillStyle    = 'rgba(255,255,255,0.55)';
        ctx.font         = `400 ${fs(0.024)}px system-ui,sans-serif`;
        ctx.textAlign    = 'center';
        ctx.textBaseline = 'middle';
        const subLine = [cfg.celebrantName, cfg.eventDate].filter(Boolean).join(' · ');
        ctx.fillText(subLine, MID, divY + fs(0.10));
        ctx.restore();
    }

    // Branding
    ctx.save();
    ctx.fillStyle    = 'rgba(255,255,255,0.35)';
    ctx.font         = `400 ${fs(0.020)}px system-ui,sans-serif`;
    ctx.textAlign    = 'center';
    ctx.textBaseline = 'bottom';
    ctx.fillText(`Made with ${cfg.appName || 'Celebrate'}`, MID, H - fs(0.025));
    ctx.restore();

    return canvas;
}

function drawGradientBg(ctx, W, H, accent) {
    const g = ctx.createLinearGradient(0, 0, W, H);
    g.addColorStop(0, '#1e1b4b');
    g.addColorStop(1, accent);
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, W, H);
}

// ─────────────────────────────────────────────────────────────────────────────
// COMMENT PAGE
// ─────────────────────────────────────────────────────────────────────────────

async function buildCommentPage(W, H, comments, pageNum, totalPages, cfg) {
    const canvas = document.createElement('canvas');
    canvas.width  = W;
    canvas.height = H;
    const ctx     = canvas.getContext('2d');
    const accent  = cfg.accentColor || '#7C3AED';
    const n       = comments.length;

    // ── Page background ──────────────────────────────────────────────────────
    const bg = ctx.createLinearGradient(0, 0, W, H);
    bg.addColorStop(0, '#FAFAFA');
    bg.addColorStop(1, '#F3F0FF');
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, W, H);

    // Subtle corner accent blob
    ctx.save();
    const blob = ctx.createRadialGradient(W, 0, 0, W, 0, W * 0.45);
    blob.addColorStop(0, accent + '14');
    blob.addColorStop(1, 'transparent');
    ctx.fillStyle = blob;
    ctx.fillRect(0, 0, W, H);
    ctx.restore();

    // ── Header bar ───────────────────────────────────────────────────────────
    const HDR = Math.round(H * 0.075);
    const PAD = Math.round(W * 0.05);

    // Thin accent top stripe
    ctx.fillStyle = accent;
    ctx.fillRect(0, 0, W, Math.round(H * 0.007));

    // App name (left)
    ctx.save();
    ctx.fillStyle    = accent;
    ctx.font         = `700 ${Math.round(W * 0.022)}px system-ui,sans-serif`;
    ctx.textBaseline = 'middle';
    ctx.textAlign    = 'left';
    ctx.fillText(cfg.appName || 'Celebrate', PAD, HDR / 2 + Math.round(H * 0.007) / 2);
    ctx.restore();

    // Celebration title (right, truncated)
    ctx.save();
    ctx.fillStyle    = '#9CA3AF';
    ctx.font         = `400 ${Math.round(W * 0.019)}px system-ui,sans-serif`;
    ctx.textBaseline = 'middle';
    ctx.textAlign    = 'right';
    let title = cfg.celebrationTitle || '';
    const maxTW = W * 0.50;
    while (title.length > 3 && ctx.measureText(title).width > maxTW) title = title.slice(0, -1);
    if (title !== (cfg.celebrationTitle || '')) title += '…';
    ctx.fillText(title, W - PAD, HDR / 2 + Math.round(H * 0.007) / 2);
    ctx.restore();

    // ── Footer ───────────────────────────────────────────────────────────────
    const FTR     = Math.round(H * 0.065);
    const FTR_Y   = H - FTR;
    ctx.save();
    ctx.fillStyle    = '#9CA3AF';
    ctx.font         = `400 ${Math.round(W * 0.019)}px system-ui,sans-serif`;
    ctx.textBaseline = 'middle';
    ctx.textAlign    = 'center';
    ctx.fillText(`Page ${pageNum} of ${totalPages}`, W / 2, FTR_Y + FTR / 2);
    ctx.restore();

    // ── Comment cards area ───────────────────────────────────────────────────
    const AREA_Y  = HDR + Math.round(H * 0.012);
    const AREA_H  = FTR_Y - AREA_Y - Math.round(H * 0.012);
    const AREA_X  = PAD;
    const AREA_W  = W - PAD * 2;
    const GAP     = Math.round(H * 0.022);
    const cardH   = Math.round((AREA_H - GAP * (n - 1)) / n);

    for (let i = 0; i < n; i++) {
        const c    = comments[i];
        const cardY = AREA_Y + i * (cardH + GAP);
        await drawCommentCard(ctx, c, AREA_X, cardY, AREA_W, cardH, n, accent, cfg);
    }

    return canvas;
}

async function drawCommentCard(ctx, comment, x, y, w, h, perPage, accent, cfg) {
    const isSingle   = perPage === 1;
    const IP         = Math.round(w * (isSingle ? 0.055 : 0.042));   // inner padding
    const RADIUS     = Math.round(Math.min(w, h) * 0.045);

    // Card shadow
    ctx.save();
    ctx.shadowColor   = 'rgba(99,50,180,0.10)';
    ctx.shadowBlur    = Math.round(h * 0.12);
    ctx.shadowOffsetY = Math.round(h * 0.04);
    roundRect(ctx, x, y, w, h, RADIUS, 'rgba(255,255,255,0.96)');
    ctx.restore();

    // Left accent stripe
    const stripeW = Math.round(w * 0.007);
    roundRect(ctx, x, y, stripeW, h, [RADIUS, 0, 0, RADIUS], accent);

    const iX = x + stripeW + IP;
    const iW = w - stripeW - IP * 2;

    if (isSingle) {
        // ── SINGLE: big quote + large text + avatar at bottom ────────────────

        // Decorative opening quote
        ctx.save();
        ctx.globalAlpha  = 0.07;
        ctx.fillStyle    = accent;
        ctx.font         = `700 ${Math.round(h * 0.35)}px Georgia,serif`;
        ctx.textBaseline = 'top';
        ctx.textAlign    = 'left';
        ctx.fillText('"', iX - Math.round(w * 0.01), y + IP * 0.5);
        ctx.restore();

        // Message text
        const msgFontSize = Math.round(h * 0.072);
        const lineH       = Math.round(msgFontSize * 1.45);
        const maxLines    = Math.floor((h * 0.52) / lineH);
        ctx.save();
        ctx.fillStyle    = '#111827';
        ctx.font         = `400 ${msgFontSize}px system-ui,sans-serif`;
        ctx.textBaseline = 'top';
        ctx.textAlign    = 'left';
        const lines = wrapText(ctx, comment.message, iW, maxLines);
        const textTop = y + IP + Math.round(h * 0.07);
        lines.forEach((ln, li) => ctx.fillText(ln, iX, textTop + li * lineH));
        ctx.restore();

        // Separator
        const sepY = y + h - Math.round(h * 0.26);
        ctx.save();
        ctx.strokeStyle = '#E5E7EB';
        ctx.lineWidth   = 1;
        ctx.beginPath();
        ctx.moveTo(iX, sepY);
        ctx.lineTo(iX + iW, sepY);
        ctx.stroke();
        ctx.restore();

        // Avatar + name
        const avR   = Math.round(h * 0.075);
        const avCX  = iX + avR;
        const avCY  = sepY + Math.round(h * 0.1);
        await drawAvatar(ctx, comment.avatar, comment.author, avCX, avCY, avR);

        ctx.save();
        ctx.fillStyle    = '#111827';
        ctx.font         = `700 ${Math.round(h * 0.052)}px system-ui,sans-serif`;
        ctx.textBaseline = 'middle';
        ctx.textAlign    = 'left';
        ctx.fillText(comment.author, avCX + avR + Math.round(w * 0.02), avCY - Math.round(h * 0.015));
        ctx.restore();

        ctx.save();
        ctx.fillStyle    = '#9CA3AF';
        ctx.font         = `400 ${Math.round(h * 0.038)}px system-ui,sans-serif`;
        ctx.textBaseline = 'middle';
        ctx.textAlign    = 'left';
        ctx.fillText(comment.time || '', avCX + avR + Math.round(w * 0.02), avCY + Math.round(h * 0.035));
        ctx.restore();

    } else {
        // ── MULTI: avatar + name header, then message ────────────────────────

        const avR   = Math.round(h * (perPage <= 2 ? 0.175 : 0.155));
        const avCX  = iX + avR;
        const avCY  = y + Math.round(h * 0.38);
        await drawAvatar(ctx, comment.avatar, comment.author, avCX, avCY, avR);

        // Name
        const nameFontSize = Math.round(h * (perPage <= 2 ? 0.175 : 0.155));
        ctx.save();
        ctx.fillStyle    = '#111827';
        ctx.font         = `700 ${nameFontSize}px system-ui,sans-serif`;
        ctx.textBaseline = 'middle';
        ctx.textAlign    = 'left';
        ctx.fillText(comment.author, avCX + avR + Math.round(w * 0.018), avCY - nameFontSize * 0.25);
        ctx.restore();

        // Time
        ctx.save();
        ctx.fillStyle    = '#9CA3AF';
        ctx.font         = `400 ${Math.round(nameFontSize * 0.72)}px system-ui,sans-serif`;
        ctx.textBaseline = 'middle';
        ctx.textAlign    = 'left';
        ctx.fillText(comment.time || '', avCX + avR + Math.round(w * 0.018), avCY + nameFontSize * 0.55);
        ctx.restore();

        // Message
        const msgTop    = avCY + avR + Math.round(h * 0.12);
        const msgFontSz = Math.round(h * (perPage <= 2 ? 0.145 : 0.125));
        const lineH     = Math.round(msgFontSz * 1.4);
        const maxH      = y + h - msgTop - IP;
        const maxLines  = Math.max(1, Math.floor(maxH / lineH));
        ctx.save();
        ctx.fillStyle    = '#374151';
        ctx.font         = `400 ${msgFontSz}px system-ui,sans-serif`;
        ctx.textBaseline = 'top';
        ctx.textAlign    = 'left';
        const lines = wrapText(ctx, comment.message, iW - avR * 0.5, maxLines);
        lines.forEach((ln, li) => ctx.fillText(ln, iX, msgTop + li * lineH));
        ctx.restore();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Alpine component
// ─────────────────────────────────────────────────────────────────────────────

export function photobookGenerator(config) {
    return {
        // ── Visibility ─────────────────────────────────────────────────────────
        open: false,

        // ── Settings ───────────────────────────────────────────────────────────
        perPage:   1,
        dimension: 'square',

        dimensions: {
            square:    { w: 1080, h: 1080, label: 'Square',      ratio: '1:1'  },
            portrait:  { w: 1080, h: 1350, label: 'Portrait',    ratio: '4:5'  },
            landscape: { w: 1920, h: 1080, label: 'Landscape',   ratio: '16:9' },
            a4:        { w: 1240, h: 1754, label: 'A4 Portrait', ratio: 'A4'   },
            a3:        { w: 1754, h: 1240, label: 'A3 Landscape',ratio: 'A3'   },
        },

        // ── State ──────────────────────────────────────────────────────────────
        generating:      false,
        progress:        0,        // 0-100
        pages:           [],       // JPEG data-URL per page
        currentPage:     0,
        generated:       false,
        downloadFormat:  'image',  // 'image' | 'pdf'
        pdfBuilding:     false,

        // ── Data from Blade ────────────────────────────────────────────────────
        comments:         config.comments  || [],
        celebrationTitle: config.title     || '',
        celebrantName:    config.celebrantName || '',
        coverPhoto:       config.coverPhoto || '',
        accentColor:      config.accentColor || '#7C3AED',
        eventDate:        config.eventDate  || '',
        celebrationType:  config.celebrationType || 'celebration',
        appName:          config.appName   || 'Celebrate',

        // ── Computed ───────────────────────────────────────────────────────────
        get currentDim()   { return this.dimensions[this.dimension]; },
        get totalPages()   { return this.pages.length; },
        get currentPreview() { return this.pages[this.currentPage] || ''; },
        get pageLabel()    {
            if (!this.generated) return '';
            return `${this.currentPage + 1} / ${this.totalPages}`;
        },
        get commentsFiltered() {
            return this.comments.filter(c => c.message && c.message.trim());
        },

        // ── Helpers ────────────────────────────────────────────────────────────
        prevPage() {
            if (this.currentPage > 0) this.currentPage--;
        },
        nextPage() {
            if (this.currentPage < this.totalPages - 1) this.currentPage++;
        },

        aspectRatioStyle() {
            const d = this.currentDim;
            return `padding-bottom: ${(d.h / d.w) * 100}%`;
        },

        // ── Generation ─────────────────────────────────────────────────────────
        async generate() {
            if (this.generating) return;
            if (!this.commentsFiltered.length) return;

            this.generating  = true;
            this.progress    = 0;
            this.pages       = [];
            this.currentPage = 0;
            this.generated   = false;

            const { w, h } = this.currentDim;

            const cfg = {
                celebrationTitle:  this.celebrationTitle,
                celebrantName:     this.celebrantName,
                coverPhoto:        this.coverPhoto,
                accentColor:       this.accentColor,
                eventDate:         this.eventDate,
                celebrationType:   this.celebrationType,
                appName:           this.appName,
            };

            const comments = this.commentsFiltered;
            const chunks   = [];
            for (let i = 0; i < comments.length; i += this.perPage) {
                chunks.push(comments.slice(i, i + this.perPage));
            }

            const total = 1 + chunks.length;  // cover + comment pages

            // Cover page
            try {
                const coverCanvas = await buildCoverPage(w, h, cfg);
                this.pages.push(coverCanvas.toDataURL('image/jpeg', 0.92));
            } catch (e) {
                console.error('Cover page error', e);
                this.pages.push('');
            }
            this.progress = Math.round((1 / total) * 100);

            // Comment pages
            for (let i = 0; i < chunks.length; i++) {
                try {
                    const pageCanvas = await buildCommentPage(
                        w, h, chunks[i], i + 2, total, cfg
                    );
                    this.pages.push(pageCanvas.toDataURL('image/jpeg', 0.92));
                } catch (e) {
                    console.error(`Page ${i + 2} error`, e);
                    this.pages.push('');
                }
                this.progress = Math.round(((i + 2) / total) * 100);
                // yield to UI between pages so the progress bar updates
                await new Promise(r => setTimeout(r, 0));
            }

            this.progress   = 100;
            this.generating = false;
            this.generated  = true;
        },

        // ── Download ───────────────────────────────────────────────────────────

        _slug() {
            return (this.celebrationTitle || 'photobook')
                .toLowerCase().replace(/[^a-z0-9]+/g, '-').slice(0, 40);
        },

        _pdfDims() {
            const { w, h } = this.currentDim;
            const MM = 0.264583;
            return { wMm: w * MM, hMm: h * MM, orientation: w >= h ? 'l' : 'p' };
        },

        // ── Image download ─────────────────────────────────────────────────────

        downloadPageAsImage(index) {
            const dataUrl = this.pages[index];
            if (!dataUrl) return;
            const a = document.createElement('a');
            a.download = `${this._slug()}-page-${index + 1}.jpg`;
            a.href     = dataUrl;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        },

        async downloadAllAsImages() {
            for (let i = 0; i < this.pages.length; i++) {
                this.downloadPageAsImage(i);
                await new Promise(r => setTimeout(r, 350));
            }
        },

        // ── PDF download ───────────────────────────────────────────────────────

        downloadPageAsPdf(index) {
            const dataUrl = this.pages[index];
            if (!dataUrl) return;
            const { wMm, hMm, orientation } = this._pdfDims();
            const doc = new jsPDF({ orientation, unit: 'mm', format: [wMm, hMm] });
            doc.addImage(dataUrl, 'JPEG', 0, 0, wMm, hMm);
            doc.save(`${this._slug()}-page-${index + 1}.pdf`);
        },

        async downloadAllAsPdf() {
            if (!this.pages.length) return;
            this.pdfBuilding = true;
            const { wMm, hMm, orientation } = this._pdfDims();
            const doc = new jsPDF({ orientation, unit: 'mm', format: [wMm, hMm] });
            for (let i = 0; i < this.pages.length; i++) {
                if (!this.pages[i]) continue;
                if (i > 0) doc.addPage([wMm, hMm], orientation);
                doc.addImage(this.pages[i], 'JPEG', 0, 0, wMm, hMm);
                // yield between pages to keep UI responsive
                await new Promise(r => setTimeout(r, 0));
            }
            doc.save(`${this._slug()}-photobook.pdf`);
            this.pdfBuilding = false;
        },

        // ── Unified dispatch (used by modal buttons) ───────────────────────────

        downloadPage(index) {
            if (this.downloadFormat === 'pdf') this.downloadPageAsPdf(index);
            else this.downloadPageAsImage(index);
        },

        async downloadAll() {
            if (this.downloadFormat === 'pdf') await this.downloadAllAsPdf();
            else await this.downloadAllAsImages();
        },
    };
}

window.photobookGenerator = photobookGenerator;
