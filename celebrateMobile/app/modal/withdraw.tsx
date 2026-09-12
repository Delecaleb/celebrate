import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, Pressable, StyleSheet, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { bankAccounts, wallet, withdrawals } from '../../src/api/endpoints';
import { Sheet } from '../../src/components/Sheet';
import { Button, EmptyState, Field, Flash, Loading, Txt } from '../../src/components/ui';
import { money } from '../../src/lib/format';
import { colors, radii, spacing } from '../../src/theme';

/**
 * Request a withdrawal — the web's request-withdrawal modal.
 */
export default function Withdraw() {
  const router = useRouter();
  const queryClient = useQueryClient();

  const banks = useQuery({ queryKey: ['bank-accounts'], queryFn: bankAccounts.list });
  const balances = useQuery({ queryKey: ['wallet', 'balances'], queryFn: wallet.balances });

  const [amount, setAmount] = useState('');
  const [walletType, setWalletType] = useState<'local' | 'global'>('local');
  const [accountId, setAccountId] = useState<number | null>(null);
  const [flash, setFlash] = useState<string | null>(null);

  // Preselect the account you're normally paid into.
  useEffect(() => {
    if (accountId === null && banks.data?.data.length) {
      setAccountId((banks.data.data.find((a) => a.is_default) ?? banks.data.data[0]).id);
    }
  }, [banks.data, accountId]);

  const create = useMutation({
    mutationFn: () =>
      withdrawals.create({
        amount: parseFloat(amount),
        bank_account_id: accountId!,
        wallet_type: balances.data?.has_local_wallet ? walletType : 'global',
      }),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ['wallet'] });
      queryClient.invalidateQueries({ queryKey: ['withdrawals'] });
      queryClient.invalidateQueries({ queryKey: ['dashboard', 'summary'] });
      Alert.alert('Withdrawal requested', res.message);
      router.back();
    },
    onError: (e) => setFlash(e instanceof ApiError ? e.message : 'Could not submit the request.'),
  });

  if (banks.isLoading || balances.isLoading) return <Loading />;

  const accounts = banks.data?.data ?? [];

  if (accounts.length === 0) {
    return (
      <Sheet title="Request withdrawal">
        <EmptyState
          icon="bank-off-outline"
          title="No bank account saved"
          body="Add a bank account first — that's where the money goes."
          action={
            <Button
              title="Add bank account"
              icon="plus"
              onPress={() => router.replace('/modal/add-bank')}
            />
          }
        />
      </Sheet>
    );
  }

  const b = balances.data!;
  // Where USD checkout works there is only the global wallet to draw on.
  const activeWallet = b.has_local_wallet ? walletType : 'global';
  const available = activeWallet === 'global' ? b.global : b.local;
  const symbol = activeWallet === 'global' ? '$' : b.symbol;

  function submit() {
    const value = parseFloat(amount);

    if (!Number.isFinite(value) || value < 1) {
      setFlash('Enter an amount of at least 1.');
      return;
    }

    if (value > available) {
      setFlash(
        b.has_local_wallet
          ? `That's more than your ${activeWallet} balance of ${money(available, symbol)}.`
          : `That's more than your balance of ${money(available, symbol)}.`,
      );
      return;
    }

    setFlash(null);
    create.mutate();
  }

  return (
    <Sheet
      title="Request withdrawal"
      sub="We'll process it within 1–2 business days."
      footer={<Button title="Submit request" full loading={create.isPending} onPress={submit} />}
    >
      {flash ? <Flash kind="error" message={flash} /> : null}

      {b.has_local_wallet ? (
        <>
          <Txt variant="tiny" color={colors.muted} style={{ marginBottom: 8, letterSpacing: 0.4 }}>
            FROM WHICH WALLET
          </Txt>
          <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.xl }}>
            {(['local', 'global'] as const).map((t) => {
              const selected = walletType === t;
              const value = t === 'global' ? b.global : b.local;

              return (
                <Pressable
                  key={t}
                  accessibilityRole="radio"
                  accessibilityState={{ selected }}
                  onPress={() => setWalletType(t)}
                  style={[styles.option, selected && { borderColor: colors.primary, backgroundColor: colors.primaryFaint }]}
                >
                  <Txt variant="small" style={{ fontWeight: '700' }} color={selected ? colors.primary : colors.ink}>
                    {t === 'global' ? 'Global (USD)' : `Local (${b.currency})`}
                  </Txt>
                  <Txt variant="small" color={colors.muted} style={{ marginTop: 3 }}>
                    {money(value, t === 'global' ? '$' : b.symbol)}
                  </Txt>
                </Pressable>
              );
            })}
          </View>
        </>
      ) : null}

      <Field
        label="Amount"
        value={amount}
        onChangeText={setAmount}
        keyboardType="decimal-pad"
        placeholder="0.00"
        hint={`Available: ${money(available, symbol)}`}
      />

      <Txt variant="tiny" color={colors.muted} style={{ marginBottom: 8, letterSpacing: 0.4 }}>
        PAY INTO
      </Txt>
      <View style={{ gap: spacing.sm }}>
        {accounts.map((account) => {
          const selected = accountId === account.id;

          return (
            <Pressable
              key={account.id}
              accessibilityRole="radio"
              accessibilityState={{ selected }}
              onPress={() => setAccountId(account.id)}
              style={[styles.accountRow, selected && { borderColor: colors.primary, backgroundColor: colors.primaryFaint }]}
            >
              <MaterialCommunityIcons
                name={selected ? 'radiobox-marked' : 'radiobox-blank'}
                size={18}
                color={selected ? colors.primary : colors.muted2}
              />
              <View style={{ flex: 1 }}>
                <Txt variant="small" style={{ fontWeight: '700' }}>
                  {account.bank_name}
                </Txt>
                <Txt variant="small" color={colors.muted} style={{ marginTop: 2 }}>
                  {account.masked_number} · {account.account_name}
                </Txt>
              </View>
            </Pressable>
          );
        })}
      </View>
    </Sheet>
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
  accountRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
  },
});
