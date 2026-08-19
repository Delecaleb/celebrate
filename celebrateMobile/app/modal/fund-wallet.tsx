import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { wallet } from '../../src/api/endpoints';
import { Sheet } from '../../src/components/Sheet';
import { Button, Field, Flash, Txt } from '../../src/components/ui';
import { money } from '../../src/lib/format';
import { useCheckout } from '../../src/lib/payments';
import { colors, radii, spacing } from '../../src/theme';

/**
 * Fund wallet — the web's fund-wallet modal.
 *
 * Which wallet you top up decides the gateway: USD goes to Stripe, anything else
 * to Paystack. That routing lives on the server (CheckoutService); here it only
 * decides the label and the symbol.
 */
export default function FundWallet() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const checkout = useCheckout();

  const { data: balances } = useQuery({ queryKey: ['wallet', 'balances'], queryFn: wallet.balances });

  const [amount, setAmount] = useState('');
  const [walletType, setWalletType] = useState<'local' | 'global'>('local');
  const [flash, setFlash] = useState<{ kind: 'ok' | 'error'; message: string } | null>(null);

  const symbol = walletType === 'global' ? '$' : (balances?.symbol ?? '');
  const currency = walletType === 'global' ? 'USD' : (balances?.currency ?? '');

  async function submit() {
    const value = parseFloat(amount);

    if (!Number.isFinite(value) || value <= 0) {
      setFlash({ kind: 'error', message: 'Enter an amount greater than zero.' });
      return;
    }

    setFlash(null);

    const outcome = await checkout.run(
      () => wallet.fund({ amount: value, wallet_type: walletType }),
      (reference, sessionId) => wallet.verifyFunding(reference, sessionId),
    );

    if (outcome.ok) {
      // Balances moved, and the home screen's stat tile shows one of them.
      queryClient.invalidateQueries({ queryKey: ['wallet'] });
      queryClient.invalidateQueries({ queryKey: ['dashboard', 'summary'] });
      router.back();
      return;
    }

    setFlash({ kind: 'error', message: outcome.message });
  }

  return (
    <Sheet
      title="Fund wallet"
      sub="Top up by card. You'll finish the payment in a secure browser window and come straight back."
      footer={
        <Button
          title={amount ? `Pay ${money(parseFloat(amount) || 0, symbol)}` : 'Continue to payment'}
          full
          loading={checkout.busy}
          onPress={submit}
        />
      }
    >
      {flash ? <Flash kind={flash.kind} message={flash.message} /> : null}

      <Txt variant="tiny" color={colors.muted} style={{ marginBottom: 8, letterSpacing: 0.4 }}>
        WHICH WALLET
      </Txt>
      <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.xl }}>
        <WalletOption
          label={`Local (${balances?.currency ?? '—'})`}
          balance={money(balances?.local ?? 0, balances?.symbol ?? '')}
          selected={walletType === 'local'}
          onPress={() => setWalletType('local')}
        />
        <WalletOption
          label="Global (USD)"
          balance={money(balances?.global ?? 0, '$')}
          selected={walletType === 'global'}
          onPress={() => setWalletType('global')}
        />
      </View>

      <Field
        label={`Amount (${currency})`}
        value={amount}
        onChangeText={setAmount}
        keyboardType="decimal-pad"
        placeholder="0.00"
        hint={walletType === 'global' ? 'Paid by card through Stripe.' : 'Paid by card through Paystack.'}
      />
    </Sheet>
  );
}

function WalletOption({
  label,
  balance,
  selected,
  onPress,
}: {
  label: string;
  balance: string;
  selected: boolean;
  onPress: () => void;
}) {
  return (
    <Pressable
      accessibilityRole="radio"
      accessibilityState={{ selected }}
      onPress={onPress}
      style={[styles.option, selected && { borderColor: colors.primary, backgroundColor: colors.primaryFaint }]}
    >
      <Txt variant="small" style={{ fontWeight: '700' }} color={selected ? colors.primary : colors.ink}>
        {label}
      </Txt>
      <Txt variant="small" color={colors.muted} style={{ marginTop: 3 }}>
        {balance}
      </Txt>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  option: {
    flex: 1,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
  },
});
