import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useQuery } from '@tanstack/react-query';
import { Link, useRouter } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { bankAccounts, wallet, withdrawals } from '../../src/api/endpoints';
import type { WalletTransaction, Withdrawal } from '../../src/api/types';
import { PageHead, Screen } from '../../src/components/Screen';
import { Badge, Button, Card, EmptyState, ErrorState, Eyebrow, Loading, SectionHeader, Txt } from '../../src/components/ui';
import { dateTime, money, shortDate } from '../../src/lib/format';
import { colors, radii, spacing } from '../../src/theme';

/**
 * Wallet.
 *
 * Deliberately the same shape as dashboard/partials/wallet.blade.php: balances
 * first, then money out to your bank, and the full ledger folded away at the
 * bottom rather than led with — nobody wants their spending totalled at them.
 */
export default function WalletScreen() {
  const router = useRouter();
  const [ledgerOpen, setLedgerOpen] = useState(false);

  const balances = useQuery({ queryKey: ['wallet', 'balances'], queryFn: wallet.balances });
  const payouts = useQuery({ queryKey: ['withdrawals'], queryFn: () => withdrawals.list() });
  const banks = useQuery({ queryKey: ['bank-accounts'], queryFn: bankAccounts.list });
  const ledger = useQuery({
    queryKey: ['wallet', 'transactions'],
    queryFn: () => wallet.transactions(),
    // Only fetched once the disclosure is opened.
    enabled: ledgerOpen,
  });

  const refresh = () => {
    balances.refetch();
    payouts.refetch();
    banks.refetch();
    if (ledgerOpen) ledger.refetch();
  };

  if (balances.isLoading) return <Loading />;

  if (balances.isError && !balances.data) {
    return <ErrorState message={(balances.error as ApiError)?.message ?? 'Could not load your wallet.'} onRetry={refresh} />;
  }

  const b = balances.data!;
  const hasBank = (banks.data?.data.length ?? 0) > 0;
  const payoutItems = payouts.data?.data ?? [];

  return (
    <Screen refreshing={balances.isFetching || payouts.isFetching} onRefresh={refresh}>
      <PageHead
        title="Wallet"
        sub="Your balances, and the money you've moved to your bank."
        action={<Button title="Fund" icon="plus" size="sm" onPress={() => router.push('/modal/fund-wallet')} />}
      />

      <View style={{ gap: spacing.md, marginBottom: spacing.xl }}>
        <BalanceCard
          chip={`Local wallet (${b.currency})`}
          icon="wallet"
          amount={money(b.local, b.symbol)}
          tone="primary"
        />
        <BalanceCard chip="Global wallet (USD)" icon="earth" amount={money(b.global, '$')} tone="ink" />
      </View>

      {/* Bank account is a setting rather than a destination, so it lives here
          instead of taking one of the five tab slots. */}
      <Link href="/bank" asChild>
        <Pressable style={({ pressed }) => [styles.bankRow, pressed && { opacity: 0.85 }]}>
          <MaterialCommunityIcons name="bank-outline" size={20} color={colors.primary} />
          <View style={{ flex: 1 }}>
            <Txt variant="bodyStrong">Bank account</Txt>
            <Txt variant="small" color={colors.muted} style={{ marginTop: 2 }}>
              {hasBank
                ? `${banks.data!.data.length} saved · paid into ${banks.data!.data.find((a) => a.is_default)?.bank_name ?? '—'}`
                : 'Add one so you can withdraw'}
            </Txt>
          </View>
          <MaterialCommunityIcons name="chevron-right" size={20} color={colors.muted2} />
        </Pressable>
      </Link>

      <SectionHeader
        title="Withdrawals"
        subtitle="Money you've moved out to your bank account"
        action={
          hasBank ? (
            <Button title="Request" variant="outline" size="sm" onPress={() => router.push('/modal/withdraw')} />
          ) : undefined
        }
      />

      {payoutItems.length === 0 ? (
        <EmptyState
          icon="bank-transfer-out"
          title="No withdrawals yet"
          body="When you move money from your wallet to your bank account, each payout will be listed here."
          action={
            hasBank ? (
              <Button title="Request withdrawal" icon="bank-transfer-out" onPress={() => router.push('/modal/withdraw')} />
            ) : (
              <Button title="Add a bank account" icon="plus" onPress={() => router.push('/bank')} />
            )
          }
        />
      ) : (
        <View style={styles.list}>
          {payoutItems.map((w) => (
            <PayoutRow key={w.id} withdrawal={w} />
          ))}
        </View>
      )}

      {/* The full ledger, folded away. */}
      <Pressable onPress={() => setLedgerOpen((v) => !v)} style={styles.disclosure}>
        <MaterialCommunityIcons
          name={ledgerOpen ? 'chevron-up' : 'chevron-down'}
          size={18}
          color={colors.primary}
        />
        <Txt variant="small" color={colors.primary} style={{ fontWeight: '700' }}>
          {ledgerOpen ? 'Hide transaction history' : 'View transaction history'}
        </Txt>
      </Pressable>

      {ledgerOpen ? (
        ledger.isLoading ? (
          <Loading />
        ) : (
          <View style={styles.list}>
            {(ledger.data?.data ?? []).map((tx) => (
              <LedgerRow key={tx.id} tx={tx} symbol={b.symbol} />
            ))}
            {(ledger.data?.data.length ?? 0) === 0 ? (
              <View style={{ padding: spacing.lg }}>
                <Txt variant="small" color={colors.muted}>
                  No transactions yet.
                </Txt>
              </View>
            ) : null}
          </View>
        )
      ) : null}
    </Screen>
  );
}

function BalanceCard({
  chip,
  icon,
  amount,
  tone,
}: {
  chip: string;
  icon: any;
  amount: string;
  tone: 'primary' | 'ink';
}) {
  const bg = tone === 'primary' ? colors.primary : colors.ink;

  return (
    <View style={[styles.balance, { backgroundColor: bg }]}>
      <View style={styles.chip}>
        <MaterialCommunityIcons name={icon} size={13} color={colors.white} />
        <Txt variant="tiny" color={colors.white} style={{ fontSize: 10 }}>
          {chip}
        </Txt>
      </View>

      <Txt variant="small" color={colors.onDark} style={{ marginTop: spacing.lg }}>
        Available balance
      </Txt>
      <Txt variant="h1" color={colors.white} style={{ marginTop: 4 }}>
        {amount}
      </Txt>
    </View>
  );
}

function PayoutRow({ withdrawal }: { withdrawal: Withdrawal }) {
  const tone =
    withdrawal.status === 'approved' || withdrawal.status === 'paid'
      ? { fg: colors.ok, bg: colors.okTint }
      : withdrawal.status === 'rejected'
        ? { fg: colors.danger, bg: colors.dangerTint }
        : { fg: colors.muted, bg: colors.warnTint };

  return (
    <View style={styles.row}>
      <View style={{ flex: 1 }}>
        <Txt variant="bodyStrong">{money(withdrawal.amount, withdrawal.currency === 'USD' ? '$' : '')}</Txt>
        <Txt variant="small" color={colors.muted} style={{ marginTop: 2 }}>
          {withdrawal.bank_name} · {withdrawal.masked_number}
        </Txt>
        <Txt variant="small" color={colors.muted2} style={{ marginTop: 4, fontSize: 11 }}>
          {shortDate(withdrawal.created_at)}
        </Txt>
      </View>
      <Badge label={withdrawal.status} fg={tone.fg} bg={tone.bg} />
    </View>
  );
}

function LedgerRow({ tx, symbol }: { tx: WalletTransaction; symbol: string }) {
  const credit = tx.type === 'credit';

  return (
    <View style={styles.row}>
      <View style={[styles.txIcon, { backgroundColor: credit ? colors.okTint : colors.line2 }]}>
        <MaterialCommunityIcons
          name={credit ? 'arrow-down' : 'arrow-up'}
          size={14}
          color={credit ? colors.ok : colors.muted}
        />
      </View>

      <View style={{ flex: 1 }}>
        <Txt variant="small" style={{ fontWeight: '600' }} numberOfLines={2}>
          {tx.description}
        </Txt>
        <Txt variant="small" color={colors.muted2} style={{ marginTop: 3, fontSize: 11 }}>
          {dateTime(tx.created_at)}
        </Txt>
      </View>

      <View style={{ alignItems: 'flex-end' }}>
        <Txt variant="bodyStrong" color={credit ? colors.ok : colors.ink}>
          {credit ? '+' : '−'}
          {money(tx.amount, tx.currency === 'USD' ? '$' : symbol)}
        </Txt>
        <Eyebrow>{tx.status}</Eyebrow>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  balance: {
    padding: spacing.xl,
    borderRadius: radii.card,
  },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    alignSelf: 'flex-start',
    paddingHorizontal: 9,
    paddingVertical: 5,
    borderRadius: radii.pill,
    backgroundColor: colors.onDarkLine,
  },
  bankRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: spacing.lg,
    marginBottom: spacing.xl,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    backgroundColor: colors.surface2,
  },
  list: {
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    overflow: 'hidden',
    marginBottom: spacing.xl,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: spacing.lg,
    borderBottomWidth: 1,
    borderBottomColor: colors.line2,
  },
  txIcon: {
    width: 28,
    height: 28,
    borderRadius: radii.pill,
    alignItems: 'center',
    justifyContent: 'center',
  },
  disclosure: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingVertical: spacing.md,
    marginBottom: spacing.md,
  },
});
