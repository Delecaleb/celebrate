import { Link } from 'expo-router';
import { useState } from 'react';

import { ApiError } from '../../src/api/client';
import { auth as authApi } from '../../src/api/endpoints';
import { AuthShell } from '../../src/components/AuthShell';
import { Button, Field, Flash, Txt } from '../../src/components/ui';
import { colors } from '../../src/theme';

export default function ForgotPassword() {
  const [email, setEmail] = useState('');
  const [sent, setSent] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function submit() {
    setBusy(true);
    setError(null);

    try {
      const res = await authApi.forgotPassword(email.trim());
      setSent(res.message);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Could not send the reset link.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <AuthShell
      title="Reset your password"
      sub="Tell us the email on your account and we'll send a link to set a new password."
      footer={
        <Link href="/(auth)/login" asChild>
          <Txt variant="small" color={colors.primary} style={{ fontWeight: '700' }}>
            Back to sign in
          </Txt>
        </Link>
      }
    >
      {sent ? <Flash kind="ok" message={sent} /> : null}
      {error ? <Flash kind="error" message={error} /> : null}

      <Field
        label="Email"
        value={email}
        onChangeText={setEmail}
        autoCapitalize="none"
        autoComplete="email"
        keyboardType="email-address"
        placeholder="you@example.com"
        onSubmitEditing={submit}
        returnKeyType="go"
      />

      {/* The reset link opens in a browser: the token flow is a web page, and
          duplicating it in the app would mean a second place to keep correct. */}
      <Button title="Send reset link" full loading={busy} onPress={submit} />
    </AuthShell>
  );
}
