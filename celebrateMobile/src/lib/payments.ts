import * as WebBrowser from 'expo-web-browser';
import { useCallback, useState } from 'react';

import { ApiError } from '../api/client';
import { PAYMENT_RETURN_URL } from '../api/config';
import type { CheckoutIntent, VerifyResult } from '../api/types';

export type CheckoutOutcome =
  | { ok: true; result: VerifyResult; message: string }
  | { ok: false; cancelled: boolean; message: string };

/**
 * Pull `reference` and `session_id` off the URL the gateway came back on.
 *
 * The URL is celebratemi://payment/return?…, produced by
 * PaymentBridgeController — neither Paystack nor Stripe will redirect to a
 * custom scheme directly, so the bridge does it for them.
 */
function paramsFrom(url: string): { reference?: string; sessionId?: string; cancelled: boolean } {
  const query = url.split('?')[1] ?? '';
  const params = new URLSearchParams(query);

  return {
    reference: params.get('reference') ?? params.get('trxref') ?? undefined,
    sessionId: params.get('session_id') ?? undefined,
    cancelled: params.get('status') === 'cancelled',
  };
}

/**
 * Runs the whole card-payment round trip.
 *
 * initiate() creates the pending record and returns the gateway URL; the user
 * pays in an in-app browser; verify() then confirms with the gateway and moves
 * the money.
 *
 * verify() is always called with the reference we already hold, even when the
 * browser reports 'cancel' or 'dismiss' — those also fire when someone paid and
 * then closed the sheet by hand, and skipping the check there would lose a real
 * payment. It is idempotent and answers 'unconfirmed' when nothing was paid, so
 * calling it on a genuine cancel is harmless.
 */
export function useCheckout() {
  const [busy, setBusy] = useState(false);

  const run = useCallback(
    async (
      initiate: () => Promise<CheckoutIntent>,
      verify: (reference: string, sessionId?: string | null) => Promise<VerifyResult>,
    ): Promise<CheckoutOutcome> => {
      setBusy(true);

      try {
        const intent = await initiate();

        const browser = await WebBrowser.openAuthSessionAsync(intent.authorization_url, PAYMENT_RETURN_URL);

        let reference = intent.reference;
        let sessionId: string | undefined;
        let cancelledByGateway = false;

        if (browser.type === 'success' && browser.url) {
          const parsed = paramsFrom(browser.url);
          reference = parsed.reference ?? reference;
          sessionId = parsed.sessionId;
          cancelledByGateway = parsed.cancelled;
        }

        if (cancelledByGateway) {
          return { ok: false, cancelled: true, message: 'Payment cancelled.' };
        }

        const result = await verify(reference, sessionId);

        return {
          ok: true,
          result,
          message: result.message ?? 'Payment confirmed.',
        };
      } catch (e) {
        if (e instanceof ApiError) {
          // 422 from verify means the gateway would not confirm it — which is
          // also what a plain abandonment looks like.
          return {
            ok: false,
            cancelled: e.status === 422,
            message: e.message,
          };
        }

        return { ok: false, cancelled: false, message: 'Payment could not be completed.' };
      } finally {
        setBusy(false);
      }
    },
    [],
  );

  return { run, busy };
}
