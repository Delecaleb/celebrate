/**
 * JSON POSTs that survive the session being replaced underneath them.
 *
 * A celebration page is public and sits open for a long time: a guest reads the
 * wishes, wanders off, comes back an hour later to send a gift. By then the
 * token rendered into that page can be dead — the session passed its lifetime,
 * or signing in on another tab regenerated it (AuthenticatedSessionController
 * calls session()->regenerate(), and logging out calls regenerateToken()).
 *
 * Laravel answers a dead token with a 419 whose body is {"message":"CSRF token
 * mismatch."}. Printed into a payment dialog that reads as a bug and leaves the
 * visitor stuck, so: fetch a live token and send the request once more. Only
 * once — if the second attempt is refused too, the session really is gone and
 * the page needs reloading.
 */

const TOKEN_URL = '/csrf-token';

/**
 * @param  {string} url
 * @param  {object} body
 * @param  {string} token   the token rendered into the page
 * @return {Promise<{res: Response, data: object|null, token: string}>}
 *         `token` is the one that worked — hold on to it for the next call.
 */
export async function postJson(url, body, token) {
    let used = token;
    let res  = await send(url, body, used);

    if (res.status === 419) {
        const fresh = await freshToken();

        if (fresh && fresh !== used) {
            used = fresh;
            res  = await send(url, body, used);
        }
    }

    return { res, data: await readJson(res), token: used };
}

/**
 * What to tell someone when a request did not succeed.
 *
 * Anything the application itself said is worth showing — "Insufficient
 * balance" is useful. Anything the framework said is not.
 */
export function failureMessage(res, data, fallback = 'Something went wrong. Please try again.') {
    if (res.status === 419) {
        return 'Your session timed out while this page was open. Refresh the page and try again — nothing has been charged.';
    }

    if (res.status === 429) {
        return 'That is a few too many attempts in a row. Wait a moment and try again.';
    }

    // No JSON body means an error page, not an answer from the application.
    if (! data || typeof data.message !== 'string') {
        return fallback;
    }

    return data.message;
}

function send(url, body, token) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            Accept: 'application/json',
        },
        body: JSON.stringify(body),
    });
}

async function freshToken() {
    try {
        const res = await fetch(TOKEN_URL, {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });

        if (! res.ok) {
            return null;
        }

        const data = await res.json();

        return typeof data.token === 'string' ? data.token : null;
    } catch {
        return null;
    }
}

async function readJson(res) {
    try {
        return await res.json();
    } catch {
        return null;
    }
}
