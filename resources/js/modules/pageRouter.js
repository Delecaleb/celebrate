/**
 * Client-side router for the marketing site and the signed-in dashboard.
 *
 * Intercepts clicks on `a[data-nav]`, fetches the target page as a partial and
 * swaps it into `#view` — no full page reload. Links remain real `href` values,
 * so with JavaScript disabled (or if a request fails) the browser just follows
 * them normally.
 *
 * The server contract (see MainController::respond and
 * DashboardController::respond) is:
 *   request  → header `X-Partial: 1`
 *   response → { title, description, nav, html, cache }
 *
 * `cache: false` keeps a page out of the in-memory cache — the dashboard sets
 * it because its pages render live balances and counts.
 */

const VIEW_ID     = 'view';
const PROGRESS_ID = 'route-progress';

/** In-memory cache of fetched partials, keyed by pathname. */
const cache = new Map();

let progressTimer = null;

function progress(state) {
    const bar = document.getElementById(PROGRESS_ID);
    if (!bar) return;

    clearTimeout(progressTimer);

    if (state === 'start') {
        bar.classList.add('is-active');
        bar.style.width = '0';
        // next frame, so the transition actually runs
        requestAnimationFrame(() => { bar.style.width = '65%'; });
        return;
    }

    bar.style.width = '100%';
    progressTimer = setTimeout(() => {
        bar.classList.remove('is-active');
        bar.style.width = '0';
    }, 220);
}

/** Mark the nav link matching `pathname` as current. */
function setActive(pathname) {
    document.querySelectorAll('a[data-nav]').forEach((a) => {
        const isMatch = new URL(a.href, window.location.origin).pathname === pathname;
        // Only the nav bar / drawer links carry the underline treatment
        if (a.classList.contains('nav-link')) {
            isMatch ? a.setAttribute('aria-current', 'page') : a.removeAttribute('aria-current');
        }
    });
}

/** True when we should let the browser handle the click itself. */
function shouldIgnore(event, anchor) {
    if (event.defaultPrevented) return true;
    if (event.button !== 0) return true;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return true;
    if (anchor.target && anchor.target !== '_self') return true;
    if (anchor.hasAttribute('download')) return true;

    const url = new URL(anchor.href, window.location.origin);
    if (url.origin !== window.location.origin) return true;

    return false;
}

async function navigate(pathname, { push = true } = {}) {
    const view = document.getElementById(VIEW_ID);
    if (!view) { window.location.assign(pathname); return; }

    progress('start');

    try {
        let data = cache.get(pathname);

        if (!data) {
            // The partial is fetched from its own URL and never cached by the
            // browser. Fetched from the page's own URL, the JSON was filed in
            // the HTTP cache under that address — and pressing Back could then
            // replay it in place of the page, showing raw markup.
            const res = await fetch(`${pathname}?_partial=1`, {
                headers: { 'X-Partial': '1', 'Accept': 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            data = await res.json();
            // Pages that render live figures (the dashboard) opt out with
            // `cache: false`, so revisiting one always re-fetches it.
            if (data.cache !== false) cache.set(pathname, data);
        }

        // Replacing innerHTML is enough for Alpine: its MutationObserver picks up
        // and initialises any x-data in the injected markup.
        view.innerHTML = data.html;

        if (data.title) document.title = data.title;
        if (data.description) {
            document.querySelector('meta[name="description"]')?.setAttribute('content', data.description);
        }

        if (push) history.pushState({ pathname }, '', pathname);

        setActive(pathname);

        // restart the enter animation on the freshly swapped view
        view.style.animation = 'none';
        void view.offsetHeight;
        view.style.animation = '';

        window.scrollTo({ top: 0, behavior: 'auto' });

        // lets the nav close its mobile drawer, etc.
        window.dispatchEvent(new CustomEvent('route-changed', { detail: { pathname } }));
    } catch (err) {
        // Anything unexpected: fall back to a real navigation rather than
        // leaving the user on a half-updated page.
        window.location.assign(pathname);
        return;
    } finally {
        progress('done');
    }
}

document.addEventListener('click', (event) => {
    const anchor = event.target.closest('a[data-nav]');
    if (!anchor || shouldIgnore(event, anchor)) return;

    const url = new URL(anchor.href, window.location.origin);

    // same page — nothing to do beyond scrolling up
    if (url.pathname === window.location.pathname) {
        event.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    event.preventDefault();
    navigate(url.pathname);
});

window.addEventListener('popstate', () => {
    navigate(window.location.pathname, { push: false });
});

// Make sure the very first entry can be returned to via the back button.
if (window.history.state === null) {
    history.replaceState({ pathname: window.location.pathname }, '', window.location.href);
}
