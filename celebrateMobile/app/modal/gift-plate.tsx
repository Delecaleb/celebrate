import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useState } from 'react';
import { Alert, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { gifts } from '../../src/api/endpoints';
import { Sheet } from '../../src/components/Sheet';
import { Button, Field, Flash, Txt } from '../../src/components/ui';
import { money } from '../../src/lib/format';
import { useCheckout } from '../../src/lib/payments';
import { colors, spacing } from '../../src/theme';

/**
 * Send a gift — the web's gift plate modal.
 *
 * Two ways to pay, exactly as on the web: out of your wallet (instant, needs an
 * account and enough balance) or by card (works for guests too).
 */
export default function GiftPlate() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const checkout = useCheckout();

  const params = useLocalSearchParams<{
    slug: string;
    giftId: string;
    celebrationId: string;
    name: string;
    price: string;
    symbol: string;
    balance: string;
    authed: string;
  }>();

  const giftId = Number(params.giftId);
  const celebrationId = Number(params.celebrationId);
  const price = parseFloat(params.price ?? '0');
  const balance = parseFloat(params.balance ?? '0');
  const isAuthed = params.authed === '1';
  const symbol = params.symbol ?? '';

  const [message, setMessage] = useState('');
  const [guestName, setGuestName] = useState('');
  const [guestEmail, setGuestEmail] = useState('');
  const [flash, setFlash] = useState<string | null>(null);

  const canUseWallet = isAuthed && balance >= price;

  const done = (text: string) => {
    queryClient.invalidateQueries({ queryKey: ['celebration', params.slug] });
    queryClient.invalidateQueries({ queryKey: ['wallet'] });
    queryClient.invalidateQueries({ queryKey: ['dashboard', 'summary'] });
    Alert.alert('Gift sent', text);
    router.back();
  };

  const sendFromWallet = useMutation({
    mutationFn: () =>
      gifts.sendFromWallet({
        platform_gift_id: giftId,
        celebration_id: celebrationId,
        message: message.trim() || undefined,
      }),
    onSuccess: (res) => done(res.message),
    onError: (e) => setFlash(e instanceof ApiError ? e.message : 'Could not send the gift.'),
  });

  async function payByCard() {
    if (!isAuthed && (!guestName.trim() || !guestEmail.trim())) {
      setFlash('Tell us your name and email so the celebrant knows who it is from.');
      return;
    }

    setFlash(null);

    const outcome = await checkout.run(
      () =>
        gifts.pay({
          platform_gift_id: giftId,
          celebration_id: celebrationId,
          message: message.trim() || undefined,
          guest_name: isAuthed ? undefined : guestName.trim(),
          guest_email: isAuthed ? undefined : guestEmail.trim(),
        }),
      (reference, sessionId) => gifts.verify(reference, sessionId),
    );

    if (outcome.ok) {
      done(outcome.message);
      return;
    }

    setFlash(outcome.message);
  }

  return (
    <Sheet
      title={`Send ${params.name ?? 'a gift'}`}
      sub={`${money(price, symbol)} — goes straight into the celebrant's wallet.`}
      footer={
        <View style={{ gap: spacing.sm }}>
          {isAuthed ? (
            <Button
              title={canUseWallet ? `Pay from wallet (${money(balance, symbol)})` : 'Not enough in your wallet'}
              full
              disabled={!canUseWallet}
              loading={sendFromWallet.isPending}
              onPress={() => {
                setFlash(null);
                sendFromWallet.mutate();
              }}
            />
          ) : null}

          <Button
            title="Pay by card"
            variant={isAuthed ? 'outline' : 'solid'}
            icon="credit-card-outline"
            full
            loading={checkout.busy}
            onPress={payByCard}
          />
        </View>
      }
    >
      {flash ? <Flash kind="error" message={flash} /> : null}

      <Field
        label="Message (optional)"
        value={message}
        onChangeText={setMessage}
        multiline
        maxLength={500}
        style={{ minHeight: 80, textAlignVertical: 'top' }}
        placeholder="Say something alongside your gift."
      />

      {/* Paying by card needs no account — just a name and an email for the
          receipt, which is the same rule the web checkout follows. */}
      {!isAuthed ? (
        <>
          <Txt variant="small" color={colors.muted} style={{ marginBottom: spacing.md, lineHeight: 19 }}>
            You don't need an account to send a gift by card.
          </Txt>

          <Field label="Your name" value={guestName} onChangeText={setGuestName} placeholder="Ada Lovelace" />
          <Field
            label="Your email"
            value={guestEmail}
            onChangeText={setGuestEmail}
            keyboardType="email-address"
            autoCapitalize="none"
            placeholder="you@example.com"
            hint="For your receipt only."
          />
        </>
      ) : null}
    </Sheet>
  );
}
