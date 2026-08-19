import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { Alert, StyleSheet, View } from 'react-native';

import { ApiError } from '../src/api/client';
import { bankAccounts } from '../src/api/endpoints';
import type { BankAccount } from '../src/api/types';
import { PageHead, Screen } from '../src/components/Screen';
import { Badge, Button, EmptyState, ErrorState, IconButton, Loading, SectionHeader, StatCard, Txt } from '../src/components/ui';
import { colors, radii, spacing } from '../src/theme';

/**
 * Bank account — where withdrawals get paid.
 *
 * Mirrors dashboard/partials/bank.blade.php, including the two summary tiles and
 * the rule that there is always exactly one default account.
 */
export default function BankScreen() {
  const router = useRouter();
  const queryClient = useQueryClient();

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['bank-accounts'],
    queryFn: bankAccounts.list,
  });

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['bank-accounts'] });
    // The wallet screen shows which account you're paid into.
    queryClient.invalidateQueries({ queryKey: ['wallet'] });
  };

  const setDefault = useMutation({
    mutationFn: (id: number) => bankAccounts.setDefault(id),
    onSuccess: invalidate,
    onError: (e) => Alert.alert('Could not update', e instanceof ApiError ? e.message : 'Please try again.'),
  });

  const remove = useMutation({
    mutationFn: (id: number) => bankAccounts.destroy(id),
    onSuccess: invalidate,
    onError: (e) => Alert.alert('Could not remove', e instanceof ApiError ? e.message : 'Please try again.'),
  });

  function confirmRemove(account: BankAccount) {
    Alert.alert(
      'Remove this account?',
      `${account.bank_name} (${account.account_number}) will be removed. This cannot be undone.`,
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Remove', style: 'destructive', onPress: () => remove.mutate(account.id) },
      ],
    );
  }

  if (isLoading) return <Loading />;

  if (isError && !data) {
    return <ErrorState message={(error as ApiError)?.message ?? 'Could not load your accounts.'} onRetry={refetch} />;
  }

  const accounts = data?.data ?? [];
  const defaultAccount = accounts.find((a) => a.is_default);

  return (
    <Screen refreshing={isFetching} onRefresh={refetch}>
      <PageHead title="Bank account" sub="Where your withdrawals get paid. Only you can see these details." />

      <View style={{ flexDirection: 'row', gap: spacing.md, marginBottom: spacing.xl }}>
        <StatCard
          icon="bank-outline"
          label="Saved accounts"
          value={String(accounts.length)}
          sub={accounts.length === 0 ? 'add one to withdraw' : 'available for payouts'}
        />
        <StatCard
          icon="star-check-outline"
          label="Paid into"
          value={defaultAccount?.bank_name ?? 'Not set'}
          sub={defaultAccount?.masked_number ?? 'pick a default account'}
          accent={colors.ok}
        />
      </View>

      <SectionHeader
        title="Your accounts"
        subtitle="Saved details used for withdrawal payments"
        action={<Button title="Add" icon="plus" size="sm" onPress={() => router.push('/modal/add-bank')} />}
      />

      {accounts.length === 0 ? (
        <EmptyState
          icon="bank-off-outline"
          title="No bank account saved"
          body="Add a bank account so we can process your withdrawal requests quickly."
          action={<Button title="Add bank account" icon="plus" onPress={() => router.push('/modal/add-bank')} />}
        />
      ) : (
        <View style={styles.list}>
          {accounts.map((account) => (
            <View key={account.id} style={styles.row}>
              <View style={styles.icon}>
                <MaterialCommunityIcons name="bank" size={17} color={colors.primary} />
              </View>

              <View style={{ flex: 1 }}>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
                  <Txt variant="bodyStrong">{account.bank_name}</Txt>
                  {account.is_default ? <Badge label="Default" fg={colors.ok} bg={colors.okTint} /> : null}
                </View>
                <Txt variant="small" color={colors.muted} style={{ marginTop: 3 }}>
                  {account.account_number} · {account.account_name}
                </Txt>
              </View>

              <View style={{ flexDirection: 'row' }}>
                {!account.is_default ? (
                  <IconButton
                    icon="star-outline"
                    color={colors.secondary}
                    accessibilityLabel={`Make ${account.bank_name} the default`}
                    onPress={() => setDefault.mutate(account.id)}
                  />
                ) : null}
                <IconButton
                  icon="trash-can-outline"
                  color={colors.danger}
                  accessibilityLabel={`Remove ${account.bank_name}`}
                  onPress={() => confirmRemove(account)}
                />
              </View>
            </View>
          ))}
        </View>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  list: {
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    overflow: 'hidden',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: spacing.lg,
    borderBottomWidth: 1,
    borderBottomColor: colors.line2,
  },
  icon: {
    width: 34,
    height: 34,
    borderRadius: radii.card,
    backgroundColor: colors.primaryFaint,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
