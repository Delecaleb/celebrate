import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useState } from 'react';

import { ApiError } from '../../src/api/client';
import { bankAccounts } from '../../src/api/endpoints';
import { Sheet } from '../../src/components/Sheet';
import { Button, Field, Flash } from '../../src/components/ui';

/**
 * Add or edit a payout account — the web's add-bank / edit-bank modals.
 *
 * Doubles as the edit form: pass id, bank_name, account_number and account_name
 * as params and it saves over that account instead of creating one.
 */
export default function AddBank() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const params = useLocalSearchParams<{
    id?: string;
    bank_name?: string;
    account_number?: string;
    account_name?: string;
  }>();

  const editingId = params.id ? Number(params.id) : null;

  const [bankName, setBankName] = useState(params.bank_name ?? '');
  const [accountNumber, setAccountNumber] = useState(params.account_number ?? '');
  const [accountName, setAccountName] = useState(params.account_name ?? '');
  const [errors, setErrors] = useState<Record<string, string | undefined>>({});
  const [flash, setFlash] = useState<string | null>(null);

  const save = useMutation({
    mutationFn: () => {
      const body = {
        bank_name: bankName.trim(),
        account_number: accountNumber.trim(),
        account_name: accountName.trim(),
      };

      return editingId ? bankAccounts.update(editingId, body) : bankAccounts.create(body);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bank-accounts'] });
      queryClient.invalidateQueries({ queryKey: ['wallet'] });
      router.back();
    },
    onError: (e) => {
      if (e instanceof ApiError) {
        setErrors({
          bank_name: e.fieldError('bank_name'),
          account_number: e.fieldError('account_number'),
          account_name: e.fieldError('account_name'),
        });
        if (!Object.keys(e.errors).length) setFlash(e.message);
      } else {
        setFlash('Could not save the account.');
      }
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
          loading={save.isPending}
          onPress={() => {
            setErrors({});
            setFlash(null);
            save.mutate();
          }}
        />
      }
    >
      {flash ? <Flash kind="error" message={flash} /> : null}

      <Field
        label="Bank name"
        value={bankName}
        onChangeText={setBankName}
        error={errors.bank_name}
        placeholder="GTBank"
      />

      {/* The API requires exactly ten digits, so the field is limited to match
          rather than letting the server reject it. */}
      <Field
        label="Account number"
        value={accountNumber}
        onChangeText={(t) => setAccountNumber(t.replace(/[^0-9]/g, ''))}
        error={errors.account_number}
        keyboardType="number-pad"
        maxLength={10}
        placeholder="0123456789"
        hint="10 digits."
      />

      <Field
        label="Account name"
        value={accountName}
        onChangeText={setAccountName}
        error={errors.account_name}
        placeholder="As it appears on your account"
      />
    </Sheet>
  );
}
