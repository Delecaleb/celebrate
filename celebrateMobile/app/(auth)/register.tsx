import { Link } from 'expo-router';
import { useState } from 'react';
import { View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { AuthShell } from '../../src/components/AuthShell';
import { Button, Field, Flash, Txt } from '../../src/components/ui';
import { useAuth } from '../../src/store/auth';
import { colors } from '../../src/theme';

export default function Register() {
  const { register } = useAuth();

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [errors, setErrors] = useState<Record<string, string | undefined>>({});
  const [flash, setFlash] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function submit() {
    setBusy(true);
    setErrors({});
    setFlash(null);

    try {
      await register(name.trim(), email.trim(), password, confirm);
    } catch (e) {
      if (e instanceof ApiError) {
        setErrors({
          name: e.fieldError('name'),
          email: e.fieldError('email'),
          password: e.fieldError('password'),
        });

        if (!Object.keys(e.errors).length) setFlash(e.message);
      } else {
        setFlash('Something went wrong. Please try again.');
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <AuthShell
      title="Create your account"
      sub="It takes a minute, and your first celebration page is free."
      footer={
        <View style={{ flexDirection: 'row', gap: 5 }}>
          <Txt variant="small" color={colors.muted}>
            Already have an account?
          </Txt>
          <Link href="/(auth)/login" asChild>
            <Txt variant="small" color={colors.primary} style={{ fontWeight: '700' }}>
              Sign in
            </Txt>
          </Link>
        </View>
      }
    >
      {flash ? <Flash kind="error" message={flash} /> : null}

      {/* One name field, split into first/last server-side — the same contract
          the web registration form uses. */}
      <Field
        label="Full name"
        value={name}
        onChangeText={setName}
        error={errors.name}
        autoComplete="name"
        placeholder="Ada Lovelace"
        textContentType="name"
      />

      <Field
        label="Email"
        value={email}
        onChangeText={setEmail}
        error={errors.email}
        autoCapitalize="none"
        autoComplete="email"
        keyboardType="email-address"
        placeholder="you@example.com"
        textContentType="emailAddress"
      />

      <Field
        label="Password"
        value={password}
        onChangeText={setPassword}
        error={errors.password}
        secureTextEntry
        autoComplete="new-password"
        placeholder="At least 8 characters"
        textContentType="newPassword"
      />

      <Field
        label="Confirm password"
        value={confirm}
        onChangeText={setConfirm}
        secureTextEntry
        autoComplete="new-password"
        placeholder="Repeat your password"
        onSubmitEditing={submit}
        returnKeyType="go"
      />

      <Button title="Create account" full loading={busy} onPress={submit} />
    </AuthShell>
  );
}
