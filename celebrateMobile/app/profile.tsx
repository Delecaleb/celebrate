import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useMutation } from '@tanstack/react-query';
import { Image } from 'expo-image';
import * as ImagePicker from 'expo-image-picker';
import { useState } from 'react';
import { Alert, Platform, Pressable, StyleSheet, View } from 'react-native';

import { ApiError } from '../src/api/client';
import { auth as authApi } from '../src/api/endpoints';
import { PageHead, Screen } from '../src/components/Screen';
import { Badge, Button, Field, Flash, SectionHeader, Txt } from '../src/components/ui';
import { initials } from '../src/lib/format';
import { useAuth } from '../src/store/auth';
import { colors, radii, spacing } from '../src/theme';

/**
 * Profile — the mobile stand-in for profile/edit.blade.php: your details, your
 * password, and deleting the account.
 */
export default function Profile() {
  const { user, refreshUser, signOut } = useAuth();

  const [firstName, setFirstName] = useState(user?.first_name ?? '');
  const [lastName, setLastName] = useState(user?.last_name ?? '');
  const [phone, setPhone] = useState(user?.phone ?? '');
  const [bio, setBio] = useState(user?.bio ?? '');
  const [flash, setFlash] = useState<{ kind: 'ok' | 'error'; message: string } | null>(null);

  const [currentPassword, setCurrentPassword] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');

  const save = useMutation({
    mutationFn: () =>
      authApi.updateProfile({
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        phone: phone.trim() || null,
        bio: bio.trim() || null,
      }),
    onSuccess: async () => {
      await refreshUser();
      setFlash({ kind: 'ok', message: 'Profile updated.' });
    },
    onError: (e) => setFlash({ kind: 'error', message: e instanceof ApiError ? e.message : 'Could not save.' }),
  });

  const avatar = useMutation({
    mutationFn: (uri: string) => authApi.updateAvatar(uri),
    onSuccess: async () => {
      await refreshUser();
      setFlash({ kind: 'ok', message: 'Photo updated.' });
    },
    onError: (e) =>
      setFlash({ kind: 'error', message: e instanceof ApiError ? e.message : 'Could not upload the photo.' }),
  });

  const changePassword = useMutation({
    mutationFn: () =>
      authApi.updatePassword({
        current_password: currentPassword,
        password,
        password_confirmation: confirm,
      }),
    onSuccess: () => {
      setCurrentPassword('');
      setPassword('');
      setConfirm('');
      setFlash({ kind: 'ok', message: 'Password updated.' });
    },
    onError: (e) =>
      setFlash({
        kind: 'error',
        message: e instanceof ApiError ? (e.fieldError('current_password') ?? e.message) : 'Could not change password.',
      }),
  });

  async function pickAvatar() {
    const res = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      aspect: [1, 1],
      quality: 0.85,
    });

    if (!res.canceled && res.assets[0]) {
      avatar.mutate(res.assets[0].uri);
    }
  }

  async function runDelete(withPassword: string) {
    try {
      await authApi.deleteAccount(withPassword);
      await signOut();
    } catch (e) {
      Alert.alert('Could not delete', e instanceof ApiError ? e.message : 'Please try again.');
    }
  }

  function confirmDelete() {
    /*
     * Alert.prompt is iOS-only, so the two platforms take different routes to
     * the same guarantee: the account is never deleted without the password.
     * On Android there is no prompt to type into, so the Current password field
     * above is what confirms it.
     */
    if (Platform.OS === 'ios' && typeof Alert.prompt === 'function') {
      Alert.prompt(
        'Delete your account',
        'This removes your account and everything on it. Enter your password to confirm.',
        [
          { text: 'Cancel', style: 'cancel' },
          {
            text: 'Delete',
            style: 'destructive',
            onPress: (value?: string) => {
              if (value) void runDelete(value);
            },
          },
        ],
        'secure-text',
      );

      return;
    }

    if (!currentPassword) {
      setFlash({
        kind: 'error',
        message: 'Enter your current password in the Password section, then tap Delete again.',
      });
      return;
    }

    Alert.alert('Delete your account', 'This removes your account and everything on it. This cannot be undone.', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Delete', style: 'destructive', onPress: () => void runDelete(currentPassword) },
    ]);
  }

  return (
    <Screen>
      <PageHead title="Profile" sub="Your details, and how you sign in." />

      {flash ? <Flash kind={flash.kind} message={flash.message} /> : null}

      {/* ── Avatar ── */}
      <View style={styles.avatarRow}>
        <Pressable onPress={pickAvatar} style={styles.avatar} accessibilityLabel="Change your photo">
          {user?.profile_photo ? (
            <Image source={{ uri: user.profile_photo }} style={{ width: '100%', height: '100%' }} contentFit="cover" />
          ) : (
            <Txt variant="h2" color={colors.primary}>
              {initials(user?.name)}
            </Txt>
          )}
        </Pressable>

        <View style={{ flex: 1 }}>
          <Txt variant="h3">{user?.name}</Txt>
          <Txt variant="small" color={colors.muted} style={{ marginTop: 3 }}>
            {user?.email}
          </Txt>
          <View style={{ marginTop: spacing.sm, flexDirection: 'row' }}>
            {user?.email_verified ? (
              <Badge label="Verified" fg={colors.ok} bg={colors.okTint} icon="check-circle" />
            ) : (
              <Badge label="Unverified" fg={colors.muted} bg={colors.warnTint} />
            )}
          </View>
          <Button
            title={avatar.isPending ? 'Uploading…' : 'Change photo'}
            variant="outline"
            size="sm"
            loading={avatar.isPending}
            style={{ marginTop: spacing.md }}
            onPress={pickAvatar}
          />
        </View>
      </View>

      {/* ── Details ── */}
      <SectionHeader title="Your details" />

      <Field label="First name" value={firstName} onChangeText={setFirstName} />
      <Field label="Last name" value={lastName} onChangeText={setLastName} />
      <Field label="Phone" value={phone} onChangeText={setPhone} keyboardType="phone-pad" placeholder="Optional" />
      <Field
        label="Bio"
        value={bio}
        onChangeText={setBio}
        multiline
        style={{ minHeight: 80, textAlignVertical: 'top' }}
        placeholder="Optional"
      />

      <Button title="Save changes" full loading={save.isPending} onPress={() => save.mutate()} />

      {/* ── Password ── */}
      <View style={{ marginTop: spacing['2xl'] }}>
        <SectionHeader title="Password" subtitle="Use a long, unique password." />

        <Field
          label="Current password"
          value={currentPassword}
          onChangeText={setCurrentPassword}
          secureTextEntry
          autoComplete="current-password"
        />
        <Field
          label="New password"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          autoComplete="new-password"
        />
        <Field label="Confirm new password" value={confirm} onChangeText={setConfirm} secureTextEntry />

        <Button
          title="Update password"
          variant="outline"
          full
          loading={changePassword.isPending}
          onPress={() => changePassword.mutate()}
        />
      </View>

      {/* ── Sign out / delete ── */}
      <View style={{ marginTop: spacing['2xl'], gap: spacing.sm }}>
        <Button title="Sign out" variant="quiet" icon="logout" full onPress={signOut} />

        <Pressable onPress={confirmDelete} style={styles.deleteRow}>
          <MaterialCommunityIcons name="trash-can-outline" size={15} color={colors.danger} />
          <Txt variant="small" color={colors.danger}>
            Delete my account
          </Txt>
        </Pressable>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  avatarRow: {
    flexDirection: 'row',
    gap: spacing.lg,
    marginBottom: spacing.xl,
    paddingBottom: spacing.xl,
    borderBottomWidth: 1,
    borderBottomColor: colors.line2,
  },
  avatar: {
    width: 76,
    height: 76,
    borderRadius: radii.pill,
    backgroundColor: colors.primaryLight,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  deleteRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingVertical: spacing.md,
  },
});
