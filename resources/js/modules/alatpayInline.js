/**
 * AlatPay's own checkout, loaded only when somebody actually pays.
 *
 * The popup is what gives the payer a choice — card, bank transfer and USSD are
 * AlatPay's own tabs, rendered by AlatPay — and it is why no card detail ever
 * reaches this site. The script is fetched on the first payment attempt rather
 * than on every celebration page, for the same reason Paystack's is.
 */

let loading = null;

/**
 * @param  {string} src  where their client script lives, from the server
 * @return {Promise<boolean>} false when it cannot be fetched at all — blocked,
 *         offline, filtered. The caller falls back to a bank transfer.
 */
export function loadAlatPay(src) {
    if (window.Alatpay) {
        return Promise.resolve(true);
    }

    if (loading) {
        return loading;
    }

    loading = new Promise((resolve) => {
        const tag = document.createElement('script');

        tag.src   = src;
        tag.async = true;

        tag.onload  = () => resolve(Boolean(window.Alatpay));
        tag.onerror = () => {
            // Let a later attempt try again; the block may have been transient.
            loading = null;
            resolve(false);
        };

        document.head.appendChild(tag);
    });

    return loading;
}

/** AlatPay reports the transaction under more than one shape; take any of them. */
function transactionIdOf(response) {
    return response?.data?.transactionId
        ?? response?.data?.id
        ?? response?.transactionId
        ?? response?.id
        ?? null;
}

/**
 * Open the checkout and wait for the payer to finish with it.
 *
 * "Done" only means AlatPay handed control back — never that money moved. The
 * server asks AlatPay directly before anything is credited.
 *
 * @param  {object} options  as built by AlatPayService::popupOptions()
 * @return {Promise<{outcome: 'done'|'closed'|'error', transactionId?: string, message?: string}>}
 */
export function payWithAlatPay(options) {
    return new Promise((resolve) => {
        let settled = false;

        const finish = (result) => {
            if (! settled) {
                settled = true;
                resolve(result);
            }
        };

        try {
            const customer = {
                email:     options.email,
                firstName: options.first_name,
                lastName:  options.last_name,
                phone:     options.phone || '',
            };

            const handler = window.Alatpay.setup({
                // Their current script reads publicKey and a nested customer;
                // their own WooCommerce plugin sends apiKey and flat fields.
                // Both are sent, so either build finds what it looks for.
                publicKey:  options.api_key,
                apiKey:     options.api_key,
                businessId: options.business_id,
                amount:     options.amount,
                currency:   options.currency,
                customer,
                ...customer,
                metadata:   options.metadata,

                autoCloseModal: true,

                onTransaction: (response) => finish({ outcome: 'done', transactionId: transactionIdOf(response) }),
                // Older builds report the same thing under different names.
                onSuccess:     (response) => finish({ outcome: 'done', transactionId: transactionIdOf(response) }),
                onError:       (error)    => finish({ outcome: 'error', message: error?.message }),
                onClose:       ()         => finish({ outcome: 'closed' }),
            });

            // Some builds open on setup, others hand back something to open.
            if (handler && typeof handler.render === 'function') {
                handler.render();
            } else if (handler && typeof handler.open === 'function') {
                handler.open();
            }
        } catch (e) {
            finish({ outcome: 'error', message: e?.message });
        }
    });
}
