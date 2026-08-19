import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { Image } from 'expo-image';
import { Link } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import type { Celebration } from '../api/types';
import { count, shortDate } from '../lib/format';
import { celebrationType, colors, radii, shadows, spacing, statusBadge } from '../theme';
import { Badge, Eyebrow, IconButton, Txt } from './ui';

/**
 * One celebration, as the event cards on My Events / Upcoming / Discover.
 *
 * Mirrors dashboard/partials/events.blade.php: cover photo with a status badge
 * and a type glyph, then type / title / date / the view+message+gift counts.
 */
export function EventCard({
  celebration,
  onDelete,
  showOwner,
}: {
  celebration: Celebration;
  onDelete?: (c: Celebration) => void;
  showOwner?: boolean;
}) {
  const t = celebrationType(celebration.celebration_type);
  const badge = statusBadge(celebration.status);
  const date = shortDate(celebration.display_date);

  return (
    <Link href={`/celebration/${celebration.slug}`} asChild>
      <Pressable style={({ pressed }) => [styles.card, pressed && { opacity: 0.9 }]}>
        <View style={styles.cover}>
          {celebration.cover_photo ? (
            <Image
              source={{ uri: celebration.cover_photo }}
              style={StyleSheet.absoluteFill}
              contentFit="cover"
              transition={180}
            />
          ) : (
            // Same idea as .event-cover-gradient — a branded placeholder rather
            // than an empty grey box.
            <View style={[StyleSheet.absoluteFill, { backgroundColor: colors.primaryLight }]} />
          )}

          <View style={styles.badgeSlot}>
            <Badge label={badge.label} fg={badge.fg} bg={colors.white} />
          </View>

          <View style={styles.typeGlyph}>
            <MaterialCommunityIcons name={t.icon as any} size={16} color={colors.primary} />
          </View>
        </View>

        <View style={styles.body}>
          <Eyebrow color={colors.primary}>{t.label}</Eyebrow>

          <Txt variant="h3" numberOfLines={2} style={{ marginTop: 6 }}>
            {celebration.title}
          </Txt>

          {showOwner && celebration.owner?.name ? (
            <Txt variant="small" color={colors.muted} style={{ marginTop: 4 }}>
              by {celebration.owner.name}
            </Txt>
          ) : null}

          {date ? (
            <View style={styles.dateRow}>
              <MaterialCommunityIcons name="calendar-outline" size={13} color={colors.muted} />
              <Txt variant="small" color={colors.muted}>
                {date}
              </Txt>
            </View>
          ) : null}

          <View style={styles.stats}>
            <Stat icon="eye-outline" value={count(celebration.view_count)} label="views" />
            <Stat icon="message-outline" value={count(celebration.comment_count)} label="msgs" />
            {celebration.gifts_count ? (
              <Stat icon="gift-outline" value={count(celebration.gifts_count)} label="gifts" />
            ) : null}
          </View>

          {onDelete ? (
            <View style={styles.actions}>
              <IconButton
                icon="trash-can-outline"
                color={colors.danger}
                accessibilityLabel={`Delete ${celebration.title}`}
                onPress={() => onDelete(celebration)}
              />
            </View>
          ) : null}
        </View>
      </Pressable>
    </Link>
  );
}

function Stat({ icon, value, label }: { icon: any; value: string; label: string }) {
  return (
    <View style={styles.stat}>
      <MaterialCommunityIcons name={icon} size={13} color={colors.muted2} />
      <Txt variant="tiny">{value}</Txt>
      <Txt variant="small" color={colors.muted2} style={{ fontSize: 11 }}>
        {label}
      </Txt>
    </View>
  );
}

/** The "New celebration" tile that leads the My Events grid. */
export function AddEventCard({ onPress }: { onPress: () => void }) {
  return (
    <Pressable
      accessibilityRole="button"
      onPress={onPress}
      style={({ pressed }) => [styles.addCard, pressed && { opacity: 0.8 }]}
    >
      <MaterialCommunityIcons name="plus-circle-outline" size={30} color={colors.primary} />
      <Txt variant="bodyStrong" color={colors.primary} style={{ marginTop: spacing.sm }}>
        New celebration
      </Txt>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    marginBottom: spacing.lg,
    overflow: 'hidden',
    ...(shadows.soft as object),
  },
  cover: {
    height: 150,
    backgroundColor: colors.surface2,
  },
  badgeSlot: {
    position: 'absolute',
    top: spacing.md,
    left: spacing.md,
  },
  typeGlyph: {
    position: 'absolute',
    bottom: spacing.md,
    left: spacing.md,
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.white,
    borderRadius: radii.card,
  },
  body: {
    padding: spacing.lg,
  },
  dateRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    marginTop: spacing.sm,
  },
  stats: {
    flexDirection: 'row',
    gap: spacing.lg,
    marginTop: spacing.md,
    flexWrap: 'wrap',
  },
  stat: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  actions: {
    flexDirection: 'row',
    justifyContent: 'flex-end',
    marginTop: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: colors.line2,
    paddingTop: spacing.sm,
  },
  addCard: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: spacing['2xl'],
    marginBottom: spacing.lg,
    borderWidth: 1,
    borderColor: colors.primaryLight,
    borderStyle: 'dashed',
    borderRadius: radii.card,
    backgroundColor: colors.primaryFaint,
  },
});
