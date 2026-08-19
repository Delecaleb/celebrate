import { useRouter } from 'expo-router';
import { ReactNode } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { colors, spacing } from '../theme';
import { IconButton, Txt } from './ui';

/**
 * The frame for every modal route.
 *
 * These stand in for the web's <x-modal> dialogs — create-event, fund-wallet,
 * request-withdrawal, add-bank and the rest. Presented as native sheets, with a
 * title row and a close button, and keyboard-aware because most of them are
 * forms.
 */
export function Sheet({
  title,
  sub,
  children,
  footer,
}: {
  title: string;
  sub?: string;
  children: ReactNode;
  footer?: ReactNode;
}) {
  const router = useRouter();
  const insets = useSafeAreaInsets();

  return (
    <KeyboardAvoidingView
      style={styles.root}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <View style={styles.header}>
        <View style={{ flex: 1, paddingRight: spacing.md }}>
          <Txt variant="h2">{title}</Txt>
          {sub ? (
            <Txt variant="small" color={colors.muted} style={{ marginTop: 5, lineHeight: 19 }}>
              {sub}
            </Txt>
          ) : null}
        </View>

        <IconButton icon="close" accessibilityLabel="Close" onPress={() => router.back()} />
      </View>

      <ScrollView
        contentContainerStyle={[styles.body, { paddingBottom: insets.bottom + spacing.xl }]}
        keyboardShouldPersistTaps="handled"
      >
        {children}
      </ScrollView>

      {footer ? (
        <View style={[styles.footer, { paddingBottom: insets.bottom + spacing.md }]}>{footer}</View>
      ) : null}
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: colors.surface,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
    paddingBottom: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.line2,
  },
  body: {
    padding: spacing.lg,
  },
  footer: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.line2,
    backgroundColor: colors.surface,
  },
});
