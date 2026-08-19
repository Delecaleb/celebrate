import { ReactNode } from 'react';
import { RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { colors, spacing } from '../theme';
import { Eyebrow, Txt } from './ui';

/**
 * The page shell.
 *
 * Equivalent of .content + .page-head in layouts/dashboard: a scrolling body
 * with the heading block at the top. The bottom padding clears the tab bar,
 * which floats over the content.
 */
export function Screen({
  children,
  refreshing,
  onRefresh,
  scroll = true,
  padded = true,
}: {
  children: ReactNode;
  refreshing?: boolean;
  onRefresh?: () => void;
  scroll?: boolean;
  padded?: boolean;
}) {
  const insets = useSafeAreaInsets();
  const contentStyle = [
    padded && styles.padded,
    { paddingBottom: insets.bottom + spacing['3xl'] },
  ];

  if (!scroll) {
    return <View style={[styles.root, contentStyle]}>{children}</View>;
  }

  return (
    <ScrollView
      style={styles.root}
      contentContainerStyle={contentStyle}
      keyboardShouldPersistTaps="handled"
      refreshControl={
        onRefresh ? (
          <RefreshControl refreshing={!!refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
        ) : undefined
      }
    >
      {children}
    </ScrollView>
  );
}

export function PageHead({
  eyebrow,
  title,
  sub,
  action,
}: {
  eyebrow?: string;
  title: string;
  sub?: string;
  action?: ReactNode;
}) {
  return (
    <View style={styles.head}>
      <View style={{ flex: 1, paddingRight: spacing.md }}>
        {eyebrow ? <Eyebrow color={colors.primary}>{eyebrow}</Eyebrow> : null}
        <Txt variant="h1" style={{ marginTop: eyebrow ? 8 : 0 }}>
          {title}
        </Txt>
        {sub ? (
          <Txt variant="small" color={colors.muted} style={{ marginTop: 8, lineHeight: 20 }}>
            {sub}
          </Txt>
        ) : null}
      </View>
      {action}
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: colors.surface,
  },
  padded: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
  },
  head: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    marginBottom: spacing.xl,
  },
});
