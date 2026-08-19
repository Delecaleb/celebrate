import { Link } from 'expo-router';
import { useState } from 'react';
import { View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { AuthShell } from '../../src/components/AuthShell';
import { Button, Field, Flash, Txt } from '../../src/components/ui';
import { useAuth } from '../../src/store/auth';
import { colors, spacing } from '../../src/theme';

export default function Login() {
  const { signIn } = useAuth();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [flash, setFlash] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function submit() {
    setBusy(true);
    setErrors({});
    setFlash(null);

    try {
      await signIn(email.trim(), password);
      // AuthGate moves us to the dashboard once `user` lands.
    } catch (e) {
      if (e instanceof ApiError) {
        // A wrong password comes back as a 422 keyed on `email`, matching the
        // web form; anything else is a banner.
        if (Object.keys(e.errors).length) {
          setErrors({
            email: e.fieldError('email') ?? '',
            password: e.fieldError('password') ?? '',
          });
        } else {
          setFlash(e.message);
        }
      } else {
        setFlash('Something went wrong. Please try again.');
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <AuthShell
      title="Welcome back"
      sub="Sign in to manage your celebrations, wallet and payouts."
      footer={
        <View style={{ gap: spacing.md }}>
          <Link href="/(auth)/forgot-password" asChild>
            <Txt variant="small" color={colors.primary}>
              Forgot your password?
            </Txt>
          </Link>

          <View style={{ flexDirection: 'row', gap: 5 }}>
            <Txt variant="small" color={colors.muted}>
              New to CelebrateMi?
            </Txt>
            <Link href="/(auth)/register" asChild>
              <Txt variant="small" color={colors.primary} style={{ fontWeight: '700' }}>
                Create an account
              </Txt>
            </Link>
          </View>
        </View>
      }
    >
      {flash ? <Flash kind="error" message={flash} /> : null}

      <Field
        label="Email"
        value={email}
        onChangeText={setEmail}
        error={errors.email || undefined}
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
        error={errors.password || undefined}
        secureTextEntry
        autoComplete="current-password"
        placeholder="••••••••"
        textContentType="password"
        onSubmitEditing={submit}
        returnKeyType="go"
      />

      <Button title="Sign in" full loading={busy} onPress={submit} />
    </AuthShell>
  );
}
