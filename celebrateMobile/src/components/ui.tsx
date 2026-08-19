import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { forwardRef } from 'react';
import {
  ActivityIndicator,
  Pressable,
  PressableProps,
  StyleSheet,
  Text,
  TextInput,
  TextInputProps,
  TextProps,
  View,
  ViewProps,
} from 'react-native';

import { colors, radii, shadows, spacing, type } from '../theme';

type IconName = React.ComponentProps<typeof MaterialCommunityIcons>['name'];

/* ── Text ──────────────────────────────────────────────────────────────────── */

type TxtProps = TextProps & {
  variant?: keyof typeof type;
  color?: string;
  center?: boolean;
};

/**
 * All copy goes through this so the type scale stays in one place.
 */
export function Txt({ variant = 'body', color = colors.ink, center, style, ...rest }: TxtProps) {
  return (
    <Text
      style={[
        type[variant] as any,
        { color },
        center && { textAlign: 'center' },
        style,
      ]}
      {...rest}
    />
  );
}

export function Eyebrow({ children, color = colors.muted2 }: { children: React.ReactNode; color?: string }) {
  return (
    <Txt variant="eyebrow" color={color}>
      {children}
    </Txt>
  );
}

/* ── Card ──────────────────────────────────────────────────────────────────── */

/**
 * A panel. Square by design — the app shell has no rounded cards, matching the
 * borderRadius override in tailwind.config.js.
 */
export function Card({ style, children, ...rest }: ViewProps) {
  return (
    <View style={[styles.card, style]} {...rest}>
      {children}
    </View>
  );
}

/* ── Button ────────────────────────────────────────────────────────────────── */

type ButtonProps = Omit<PressableProps, 'children'> & {
  title: string;
  variant?: 'solid' | 'outline' | 'quiet' | 'danger';
  size?: 'sm' | 'md' | 'lg';
  icon?: IconName;
  loading?: boolean;
  full?: boolean;
};

export function Button({
  title,
  variant = 'solid',
  size = 'md',
  icon,
  loading,
  full,
  disabled,
  style,
  ...rest
}: ButtonProps) {
  const isDisabled = disabled || loading;

  const palette = {
    solid: { bg: colors.primary, fg: colors.white, border: colors.primary },
    outline: { bg: 'transparent', fg: colors.ink, border: colors.line },
    quiet: { bg: colors.surface2, fg: colors.ink, border: colors.surface2 },
    danger: { bg: 'transparent', fg: colors.danger, border: colors.line },
  }[variant];

  const pad = { sm: 9, md: 13, lg: 16 }[size];
  const fontSize = { sm: 13, md: 15, lg: 16 }[size];

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled: !!isDisabled, busy: !!loading }}
      disabled={isDisabled}
      style={({ pressed }) => [
        styles.button,
        {
          backgroundColor: palette.bg,
          borderColor: palette.border,
          paddingVertical: pad,
          opacity: isDisabled ? 0.55 : pressed ? 0.85 : 1,
          alignSelf: full ? 'stretch' : 'flex-start',
        },
        style as any,
      ]}
      {...rest}
    >
      {loading ? (
        <ActivityIndicator size="small" color={palette.fg} />
      ) : (
        <>
          {icon && <MaterialCommunityIcons name={icon} size={fontSize + 3} color={palette.fg} />}
          <Text style={{ color: palette.fg, fontSize, fontWeight: '700' }}>{title}</Text>
        </>
      )}
    </Pressable>
  );
}

/** A small square icon-only button, as .icon-btn on the web. */
export function IconButton({
  icon,
  color = colors.muted,
  size = 18,
  ...rest
}: Omit<PressableProps, 'children'> & { icon: IconName; color?: string; size?: number }) {
  return (
    <Pressable
      accessibilityRole="button"
      hitSlop={8}
      style={({ pressed }) => [styles.iconButton, { opacity: pressed ? 0.6 : 1 }]}
      {...rest}
    >
      <MaterialCommunityIcons name={icon} size={size} color={color} />
    </Pressable>
  );
}

/* ── Badge ─────────────────────────────────────────────────────────────────── */

export function Badge({
  label,
  fg = colors.muted,
  bg = colors.line2,
  icon,
}: {
  label: string;
  fg?: string;
  bg?: string;
  icon?: IconName;
}) {
  return (
    <View style={[styles.badge, { backgroundColor: bg }]}>
      {icon && <MaterialCommunityIcons name={icon} size={11} color={fg} />}
      <Text style={{ color: fg, fontSize: 10, fontWeight: '800', letterSpacing: 0.6, textTransform: 'uppercase' }}>
        {label}
      </Text>
    </View>
  );
}

/* ── Stat tile ─────────────────────────────────────────────────────────────── */

export function StatCard({
  icon,
  label,
  value,
  sub,
  accent = colors.primary,
}: {
  icon: IconName;
  label: string;
  value: string;
  sub?: string;
  accent?: string;
}) {
  return (
    <Card style={styles.statCard}>
      <View style={[styles.statIcon, { backgroundColor: colors.primaryFaint }]}>
        <MaterialCommunityIcons name={icon} size={18} color={accent} />
      </View>
      <Eyebrow>{label}</Eyebrow>
      <Txt variant="stat" style={{ marginTop: 6 }}>
        {value}
      </Txt>
      {sub ? (
        <Txt variant="small" color={colors.muted} style={{ marginTop: 2 }}>
          {sub}
        </Txt>
      ) : null}
    </Card>
  );
}

/* ── Empty state ───────────────────────────────────────────────────────────── */

export function EmptyState({
  icon,
  title,
  body,
  action,
}: {
  icon: IconName;
  title: string;
  body: string;
  action?: React.ReactNode;
}) {
  return (
    <View style={styles.empty}>
      <MaterialCommunityIcons name={icon} size={40} color={colors.muted2} />
      <Txt variant="h3" center style={{ marginTop: spacing.lg }}>
        {title}
      </Txt>
      <Txt variant="small" color={colors.muted} center style={{ marginTop: 6, lineHeight: 20, maxWidth: 320 }}>
        {body}
      </Txt>
      {action ? <View style={{ marginTop: spacing.xl }}>{action}</View> : null}
    </View>
  );
}

/* ── Form field ────────────────────────────────────────────────────────────── */

type FieldProps = TextInputProps & {
  label: string;
  error?: string;
  hint?: string;
};

export const Field = forwardRef<TextInput, FieldProps>(function Field(
  { label, error, hint, style, ...rest },
  ref,
) {
  return (
    <View style={{ marginBottom: spacing.lg }}>
      <Text style={styles.label}>{label}</Text>
      <TextInput
        ref={ref}
        placeholderTextColor={colors.muted2}
        style={[styles.input, !!error && { borderColor: colors.danger }, style]}
        {...rest}
      />
      {error ? (
        <Txt variant="small" color={colors.danger} style={{ marginTop: 5 }}>
          {error}
        </Txt>
      ) : hint ? (
        <Txt variant="small" color={colors.muted} style={{ marginTop: 5 }}>
          {hint}
        </Txt>
      ) : null}
    </View>
  );
});

/* ── Section header ────────────────────────────────────────────────────────── */

export function SectionHeader({
  title,
  subtitle,
  action,
}: {
  title: string;
  subtitle?: string;
  action?: React.ReactNode;
}) {
  return (
    <View style={styles.sectionHeader}>
      <View style={{ flex: 1, paddingRight: spacing.md }}>
        <Txt variant="h2">{title}</Txt>
        {subtitle ? (
          <Txt variant="small" color={colors.muted} style={{ marginTop: 4 }}>
            {subtitle}
          </Txt>
        ) : null}
      </View>
      {action}
    </View>
  );
}

/* ── Flash message ─────────────────────────────────────────────────────────── */

export function Flash({ kind, message }: { kind: 'ok' | 'error'; message: string }) {
  const ok = kind === 'ok';

  return (
    <View style={[styles.flash, { backgroundColor: ok ? colors.okTint : colors.dangerTint }]}>
      <MaterialCommunityIcons
        name={ok ? 'check-circle' : 'alert-circle'}
        size={16}
        color={ok ? colors.ok : colors.danger}
      />
      <Txt variant="small" color={ok ? colors.ok : colors.danger} style={{ flex: 1 }}>
        {message}
      </Txt>
    </View>
  );
}

/* ── Loading / error ───────────────────────────────────────────────────────── */

export function Loading({ label }: { label?: string }) {
  return (
    <View style={styles.centered}>
      <ActivityIndicator color={colors.primary} />
      {label ? (
        <Txt variant="small" color={colors.muted} style={{ marginTop: spacing.md }}>
          {label}
        </Txt>
      ) : null}
    </View>
  );
}

export function ErrorState({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return (
    <View style={styles.centered}>
      <MaterialCommunityIcons name="cloud-off-outline" size={38} color={colors.muted2} />
      <Txt variant="small" color={colors.muted} center style={{ marginTop: spacing.md, maxWidth: 300 }}>
        {message}
      </Txt>
      {onRetry ? <Button title="Try again" variant="outline" size="sm" onPress={onRetry} style={{ marginTop: spacing.lg }} /> : null}
    </View>
  );
}

/** A thin progress bar, as the registry's funding meter. */
export function Progress({ percent, color = colors.primary }: { percent: number; color?: string }) {
  return (
    <View style={styles.progressTrack}>
      <View style={[styles.progressFill, { width: `${Math.max(0, Math.min(100, percent))}%`, backgroundColor: color }]} />
    </View>
  );
}

export const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    padding: spacing.lg,
  },
  button: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 7,
    paddingHorizontal: spacing.lg,
    borderWidth: 1,
    borderRadius: radii.card,
  },
  iconButton: {
    width: 34,
    height: 34,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: radii.card,
  },
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: radii.pill,
    alignSelf: 'flex-start',
  },
  statCard: {
    flex: 1,
    minWidth: 150,
    ...(shadows.soft as object),
  },
  statIcon: {
    width: 34,
    height: 34,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
    borderRadius: radii.card,
  },
  empty: {
    alignItems: 'center',
    paddingVertical: spacing['3xl'],
    paddingHorizontal: spacing.xl,
  },
  label: {
    ...(type.tiny as object),
    color: colors.muted,
    marginBottom: 7,
    letterSpacing: 0.4,
  },
  input: {
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
    fontSize: 15,
    color: colors.ink,
    backgroundColor: colors.surface,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    marginBottom: spacing.lg,
  },
  flash: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    padding: spacing.md,
    marginBottom: spacing.lg,
    borderRadius: radii.card,
  },
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.xl,
  },
  progressTrack: {
    height: 6,
    backgroundColor: colors.line2,
    borderRadius: radii.pill,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: radii.pill,
  },
});
