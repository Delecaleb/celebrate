/**
 * Paystack's inline checkout, loaded only when somebody actually pays.
 *
 * A celebration page is public and mostly read, not paid on. Putting a
 * third-party script in every one of those page loads costs every visitor a
 * request and hands Paystack a view of people who never went near the card
 * form, so the script is fetched on the first payment attempt instead.
 */

const SRC = 'https://js.paystack.co/v2/inline.js';

let loading = null;

/**
 * @return {Promise<boolean>} false when the script cannot be fetched at all —
 *         an ad blocker, an offline moment, a network that filters it. The
 *         caller falls back to the hosted checkout rather than failing.
 */
export function loadPaystack() {
    if (window.PaystackPop) {
        return Promise.resolve(true);
    }

    if (loading) {
        return loading;
    }

    loading = new Promise((resolve) => {
        const tag = document.createElement('script');

        tag.src   = SRC;
        tag.async = true;

        tag.onload  = () => resolve(Boolean(window.PaystackPop));
        tag.onerror = () => {
            // Let a later attempt try again; the block may have been transient.
            loading = null;
            resolve(false);
        };

        document.head.appendChild(tag);
    });

    return loading;
}

/**
 * Resume a transaction the server already initialised.
 *
 * resumeTransaction is used rather than newTransaction deliberately: the
 * amount, currency and reference are fixed by our initialise call and the
 * browser only carries the access code, so nothing about what is charged can
 * be edited in the page.
 *
 * @param  {string} accessCode
 * @return {Promise<{outcome: 'success'|'cancelled'|'error', reference?: string, message?: string}>}
 */
export function payInline(accessCode) {
    return new Promise((resolve) => {
        let popup;

        try {
            popup = new window.PaystackPop();
        } catch {
            resolve({ outcome: 'error', message: 'The card form could not be opened.' });

            return;
        }

        popup.resumeTransaction(accessCode, {
            // Paystack saying "done" is a prompt to go and ask Paystack, never
            // proof on its own — the server verifies before anything settles.
            onSuccess: (txn) => resolve({ outcome: 'success', reference: txn?.reference }),
            onCancel:  ()    => resolve({ outcome: 'cancelled' }),
            onError:   (err) => resolve({ outcome: 'error', message: err?.message }),
        });
    });
}
