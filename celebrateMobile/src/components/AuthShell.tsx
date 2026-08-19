import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { ReactNode } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { colors, radii, spacing } from '../theme';
import { Txt } from './ui';

/**
 * The frame around sign-in, register and forgot-password.
 *
 * Stands in for layouts/guest: the mark, a heading, then the form. Wrapped in a
 * KeyboardAvoidingView because every one of these screens is a form and the
 * submit button must stay reachable with the keyboard up.
 */
export function AuthShell({
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
  const insets = useSafeAreaInsets();

  return (
    <KeyboardAvoidingView
      style={{ flex: 1, backgroundColor: colors.surface }}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView
        contentContainerStyle={[
          styles.body,
          { paddingTop: insets.top + spacing['2xl'], paddingBottom: insets.bottom + spacing['2xl'] },
        ]}
        keyboardShouldPersistTaps="handled"
      >
        <View style={styles.mark}>
          <MaterialCommunityIcons name="party-popper" size={22} color={colors.white} />
        </View>

        <Txt variant="h1" style={{ marginTop: spacing.xl }}>
          {title}
        </Txt>

        {sub ? (
          <Txt variant="small" color={colors.muted} style={{ marginTop: spacing.sm, lineHeight: 20 }}>
            {sub}
          </Txt>
        ) : null}

        <View style={{ marginTop: spacing['2xl'] }}>{children}</View>

        {footer ? <View style={{ marginTop: spacing.xl }}>{footer}</View> : null}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  body: {
    flexGrow: 1,
    paddingHorizontal: spacing.xl,
  },
  mark: {
    width: 44,
    height: 44,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.primary,
    borderRadius: radii.card,
  },
});
