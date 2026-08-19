import { useQueryClient } from '@tanstack/react-query';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { View } from 'react-native';

import { gifts, wallet, wishes } from '../../src/api/endpoints';
import { Button, Flash, Loading, Txt } from '../../src/components/ui';
import { colors, spacing } from '../../src/theme';

/**
 * Cold-start fallback for celebratemi://payment/return.
 *
 * Normally the in-app browser catches the redirect itself and useCheckout
 * finishes the job without this screen ever appearing. It exists for the case
 * where that browser session is gone — the app was killed mid-payment, or the
 * user finished in an external browser and tapped through — because the payment
 * still has to be verified and credited.
 */
export default function PaymentReturn() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const params = useLocalSearchParams<{
    reference?: string;
    session_id?: string;
    flow?: string;
    slug?: string;
    status?: string;
  }>();

  const [state, setState] = useState<'working' | 'done' | 'failed'>('working');
  const [message, setMessage] = useState('');

  useEffect(() => {
    const { reference, session_id, flow, status } = params;

    if (status === 'cancelled' || !reference) {
      setState('failed');
      setMessage(status === 'cancelled' ? 'Payment cancelled.' : 'No payment reference was returned.');
      return;
    }

    const verify =
      flow === 'gift'
        ? gifts.verify
        : flow === 'wish'
          ? wishes.verifyContribution
          : wallet.verifyFunding;

    verify(reference, session_id ?? null)
      .then((res) => {
        setState('done');
        setMessage(res.message ?? 'Payment confirmed.');
        queryClient.invalidateQueries({ queryKey: ['wallet'] });
        queryClient.invalidateQueries({ queryKey: ['dashboard'] });
        if (params.slug) queryClient.invalidateQueries({ queryKey: ['celebration', params.slug] });
      })
      .catch((e) => {
        setState('failed');
        setMessage(e?.message ?? 'We could not confirm that payment.');
      });
    // Runs once for the params this screen was opened with.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  if (state === 'working') return <Loading label="Confirming your payment…" />;

  return (
    <View style={{ flex: 1, justifyContent: 'center', padding: spacing.xl, backgroundColor: colors.surface }}>
      <Flash kind={state === 'done' ? 'ok' : 'error'} message={message} />

      <Txt variant="small" color={colors.muted} style={{ marginBottom: spacing.xl, lineHeight: 20 }}>
        {state === 'done'
          ? 'You can close this and carry on.'
          : 'If you were charged and this keeps failing, contact support and quote the reference.'}
      </Txt>

      <Button
        title="Back to CelebrateMi"
        full
        onPress={() => (params.slug ? router.replace(`/celebration/${params.slug}`) : router.replace('/(tabs)'))}
      />
    </View>
  );
}
