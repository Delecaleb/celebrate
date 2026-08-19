import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useState } from 'react';
import { Alert, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { wishes } from '../../src/api/endpoints';
import { Sheet } from '../../src/components/Sheet';
import { Button, Field, Flash, Txt } from '../../src/components/ui';
import { money } from '../../src/lib/format';
import { useCheckout } from '../../src/lib/payments';
import { colors, spacing } from '../../src/theme';

/**
 * Contribute to a registry item — the web's wish contribution modal.
 *
 * Same two payment routes as the gift plate: wallet or card.
 */
export default function Contribute() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const checkout = useCheckout();

  const params = useLocalSearchParams<{
    slug: string;
    wishId: string;
    name: string;
    currency: string;
    symbol: string;
    remaining: string;
    balance: string;
    authed: string;
  }>();

  const wishId = Number(params.wishId);
  const remaining = parseFloat(params.remaining ?? '0');
  const balance = parseFloat(params.balance ?? '0');
  const isAuthed = params.authed === '1';
  const symbol = params.symbol ?? '';
  const currency = params.currency ?? 'USD';

  // Default to whatever is still needed — the most likely amount.
  const [amount, setAmount] = useState(remaining > 0 ? String(remaining) : '');
  const [message, setMessage] = useState('');
  const [guestName, setGuestName] = useState('');
  const [guestEmail, setGuestEmail] = useState('');
  const [flash, setFlash] = useState<string | null>(null);

  const value = parseFloat(amount);
  const valid = Number.isFinite(value) && value > 0;
  const canUseWallet = isAuthed && valid && balance >= value;

  const done = (text: string) => {
    queryClient.invalidateQueries({ queryKey: ['celebration', params.slug] });
    queryClient.invalidateQueries({ queryKey: ['wallet'] });
    Alert.alert('Thank you', text);
    router.back();
  };

  const fromWallet = useMutation({
    mutationFn: () =>
      wishes.contributeFromWallet(wishId, {
        amount: value,
        currency,
        message: message.trim() || undefined,
      }),
    onSuccess: (res) => done(res.message),
    onError: (e) => setFlash(e instanceof ApiError ? e.message : 'Could not record your contribution.'),
  });

  async function payByCard() {
    if (!valid) {
      setFlash('Enter an amount greater than zero.');
      return;
    }

    if (!isAuthed && (!guestName.trim() || !guestEmail.trim())) {
      setFlash('Tell us your name and email so the celebrant knows who it is from.');
      return;
    }

    setFlash(null);

    const outcome = await checkout.run(
      () =>
        wishes.payToContribute(wishId, {
          amount: value,
          currency,
          message: message.trim() || undefined,
          guest_name: isAuthed ? undefined : guestName.trim(),
          guest_email: isAuthed ? undefined : guestEmail.trim(),
        }),
      (reference, sessionId) => wishes.verifyContribution(reference, sessionId),
    );

    if (outcome.ok) {
      done(outcome.message);
      return;
    }

    setFlash(outcome.message);
  }

  return (
    <Sheet
      title={`Contribute to ${params.name ?? 'this item'}`}
      sub={remaining > 0 ? `${money(remaining, symbol)} still needed.` : undefined}
      footer={
        <View style={{ gap: spacing.sm }}>
          {isAuthed ? (
            <Button
              title={canUseWallet ? `Pay from wallet (${money(balance, symbol)})` : 'Not enough in your wallet'}
              full
              disabled={!canUseWallet}
              loading={fromWallet.isPending}
              onPress={() => {
                setFlash(null);
                fromWallet.mutate();
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
        label={`Amount (${currency})`}
        value={amount}
        onChangeText={setAmount}
        keyboardType="decimal-pad"
        placeholder="0.00"
        hint="Any amount helps — you don't have to cover the whole thing."
      />

      <Field
        label="Message (optional)"
        value={message}
        onChangeText={setMessage}
        multiline
        maxLength={500}
        style={{ minHeight: 80, textAlignVertical: 'top' }}
        placeholder="Add a note with your contribution."
      />

      {!isAuthed ? (
        <>
          <Txt variant="small" color={colors.muted} style={{ marginBottom: spacing.md, lineHeight: 19 }}>
            You don't need an account to contribute by card.
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
