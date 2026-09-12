import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { bankAccounts } from '../../src/api/endpoints';
import type { ResolvedAccount } from '../../src/api/types';
import { Sheet } from '../../src/components/Sheet';
import { Button, Field, Flash, Txt } from '../../src/components/ui';
import { colors, radii, spacing } from '../../src/theme';

/**
 * Add or edit a payout account — the web's add-bank / edit-bank modals.
 *
 * Nobody types the account holder name. Pick a bank, enter ten digits, and we
 * ask the bank who owns it; Save only wakes up once it answers, and the API
 * runs the same lookup again before it writes anything.
 *
 * Doubles as the edit form: pass id, bank_code and account_number as params and
 * it saves over that account instead of creating one.
 */
export default function AddBank() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const params = useLocalSearchParams<{
    id?: string;
    bank_code?: string;
    account_number?: string;
  }>();

  const editingId = params.id ? Number(params.id) : null;

  const [bankCode, setBankCode] = useState(params.bank_code ?? '');
  const [accountNumber, setAccountNumber] = useState(params.account_number ?? '');
  const [pickerOpen, setPickerOpen] = useState(false);
  const [search, setSearch] = useState('');

  const [resolved, setResolved] = useState<ResolvedAccount | null>(null);
  const [checking, setChecking] = useState(false);
  const [lookupError, setLookupError] = useState<string | null>(null);
  const [flash, setFlash] = useState<string | null>(null);

  const banksQuery = useQuery({
    queryKey: ['banks'],
    queryFn: () => bankAccounts.banks(),
    staleTime: 24 * 60 * 60 * 1000,
  });

  const banks = banksQuery.data?.data ?? [];
  const selectedBank = banks.find((bank) => bank.code === bankCode) ?? null;

  const matches = useMemo(() => {
    const term = search.trim().toLowerCase();
    const list = term ? banks.filter((bank) => bank.name.toLowerCase().includes(term)) : banks;

    // The full list runs to a couple of hundred rows; show a workable slice and
    // let the search box do the rest.
    return list.slice(0, 40);
  }, [banks, search]);

  // Look the account up whenever the pair is complete. Debounced, because it
  // fires on every keystroke of the last few digits.
  useEffect(() => {
    setResolved(null);
    setLookupError(null);

    if (!bankCode || accountNumber.length !== 10) {
      setChecking(false);
      return;
    }

    let cancelled = false;
    setChecking(true);

    const timer = setTimeout(async () => {
      try {
        const data = await bankAccounts.resolve({
          bank_code: bankCode,
          account_number: accountNumber,
        });
        if (!cancelled) setResolved(data);
      } catch (e) {
        if (cancelled) return;
        setLookupError(
          e instanceof ApiError
            ? e.fieldError('account_number') ?? e.fieldError('bank_code') ?? e.message
            : 'We could not check that account. Try again in a moment.',
        );
      } finally {
        if (!cancelled) setChecking(false);
      }
    }, 350);

    return () => {
      cancelled = true;
      clearTimeout(timer);
    };
  }, [bankCode, accountNumber]);

  const save = useMutation({
    mutationFn: () => {
      const body = { bank_code: bankCode, account_number: accountNumber };

      return editingId ? bankAccounts.update(editingId, body) : bankAccounts.create(body);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bank-accounts'] });
      queryClient.invalidateQueries({ queryKey: ['wallet'] });
      router.back();
    },
    onError: (e) => {
      setFlash(
        e instanceof ApiError
          ? e.fieldError('account_number') ?? e.fieldError('account_name') ?? e.message
          : 'Could not save the account.',
      );
    },
  });

  return (
    <Sheet
      title={editingId ? 'Edit bank account' : 'Add bank account'}
      sub="Only you can see these details. They're used to pay out your withdrawals."
      footer={
        <Button
          title={editingId ? 'Save changes' : 'Save account'}
          full
          disabled={!resolved}
          loading={save.isPending}
          onPress={() => {
            setFlash(null);
            save.mutate();
          }}
        />
      }
    >
      {flash ? <Flash kind="error" message={flash} /> : null}

      {/* ── Bank ─────────────────────────────────────────────────────────── */}
      <View style={{ marginBottom: spacing.lg }}>
        <Txt variant="small" color={colors.muted} style={styles.label}>
          BANK
        </Txt>

        <Pressable
          accessibilityRole="button"
          accessibilityLabel={selectedBank ? `Bank: ${selectedBank.name}. Change` : 'Select your bank'}
          style={styles.select}
          onPress={() => setPickerOpen((open) => !open)}
        >
          <Txt color={selectedBank ? colors.ink : colors.muted2}>
            {selectedBank?.name ?? (banksQuery.isLoading ? 'Loading banks…' : 'Select your bank')}
          </Txt>
          <MaterialCommunityIcons
            name={pickerOpen ? 'chevron-up' : 'chevron-down'}
            size={20}
            color={colors.muted}
          />
        </Pressable>

        {banksQuery.isError ? (
          <Txt variant="small" color={colors.danger} style={{ marginTop: 5 }}>
            Could not load the bank list. Pull back and try again.
          </Txt>
        ) : null}

        {pickerOpen ? (
          <View style={styles.picker}>
            <Field
              label=""
              value={search}
              onChangeText={setSearch}
              placeholder="Search banks"
              autoCorrect={false}
              style={{ marginBottom: 0 }}
            />

            <ScrollView style={styles.pickerList} keyboardShouldPersistTaps="handled" nestedScrollEnabled>
              {matches.map((bank) => (
                <Pressable
                  key={bank.code}
                  style={styles.pickerRow}
                  onPress={() => {
                    setBankCode(bank.code);
                    setPickerOpen(false);
                    setSearch('');
                  }}
                >
                  <Txt>{bank.name}</Txt>
                  {bank.code === bankCode ? (
                    <MaterialCommunityIcons name="check" size={18} color={colors.primary} />
                  ) : null}
                </Pressable>
              ))}

              {matches.length === 0 ? (
                <Txt variant="small" color={colors.muted} style={{ padding: spacing.md }}>
                  No bank matches that.
                </Txt>
              ) : null}
            </ScrollView>
          </View>
        ) : null}
      </View>

      {/* ── Account number ───────────────────────────────────────────────── */}
      <Field
        label="Account number"
        value={accountNumber}
        onChangeText={(t) => setAccountNumber(t.replace(/[^0-9]/g, ''))}
        keyboardType="number-pad"
        maxLength={10}
        placeholder="0123456789"
        hint="10 digits."
      />

      {/* ── The name, straight from the bank ─────────────────────────────── */}
      <View>
        <Txt variant="small" color={colors.muted} style={styles.label}>
          ACCOUNT HOLDER
        </Txt>

        <View
          style={[
            styles.nameBox,
            resolved && { borderColor: colors.ok, backgroundColor: colors.okTint },
            lookupError && { borderColor: colors.danger },
          ]}
        >
          {checking ? (
            <>
              <ActivityIndicator size="small" color={colors.muted} />
              <Txt color={colors.muted}>Checking with the bank…</Txt>
            </>
          ) : resolved ? (
            <>
              <MaterialCommunityIcons name="check-decagram" size={19} color={colors.ok} />
              <Txt variant="bodyStrong" style={{ flex: 1 }}>
                {resolved.account_name}
              </Txt>
            </>
          ) : (
            <Txt color={colors.muted2} style={{ flex: 1 }}>
              Pick a bank and enter the account number
            </Txt>
          )}
        </View>

        {lookupError ? (
          <Txt variant="small" color={colors.danger} style={{ marginTop: 5 }}>
            {lookupError}
          </Txt>
        ) : (
          <Txt variant="small" color={colors.muted} style={{ marginTop: 5 }}>
            We fetch this from your bank — it cannot be edited, so a transfer never
            bounces on a mistyped name.
          </Txt>
        )}
      </View>
    </Sheet>
  );
}

const styles = StyleSheet.create({
  label: {
    fontWeight: '700',
    letterSpacing: 0.6,
    marginBottom: 6,
  },
  select: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    paddingHorizontal: spacing.md,
    paddingVertical: 13,
    backgroundColor: colors.surface,
  },
  picker: {
    marginTop: spacing.sm,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    padding: spacing.sm,
    backgroundColor: colors.surface,
  },
  pickerList: {
    maxHeight: 220,
  },
  pickerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 11,
    paddingHorizontal: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.line2,
  },
  nameBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    minHeight: 50,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
    backgroundColor: colors.surface2,
  },
});
