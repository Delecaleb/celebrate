import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { Image } from 'expo-image';
import { Dimensions, ScrollView, StyleSheet, View } from 'react-native';

import type { CelebrationPage } from '../../api/types';
import { count, money, shortDate } from '../../lib/format';
import { celebrationType, colors, radii, spacing, statusBadge } from '../../theme';
import { Badge, Eyebrow, Txt } from '../ui';

const { width } = Dimensions.get('window');

/**
 * The top of the celebration page: cover photos, who it's for, the countdown,
 * how much has been raised and who gave.
 *
 * Applies the page's template colours when one is set, which is how the web page
 * themes itself — page_bg behind the block, text_primary for the title.
 */
export function CelebrationHeader({ page }: { page: CelebrationPage }) {
  const celebration = page.celebration.data;
  const template = page.template?.data;
  const t = celebrationType(celebration.celebration_type);
  const badge = statusBadge(celebration.status);

  // custom_bg / custom_text override the template, as on the web.
  const bg = celebration.custom_bg || template?.page_bg || colors.surface;
  const fg = celebration.custom_text || template?.text_primary || colors.ink;
  const sub = template?.text_secondary || colors.muted;

  const photos = celebration.cover_photos;

  return (
    <View style={{ backgroundColor: bg }}>
      {/* Cover: swipeable when there is more than one photo. */}
      <View style={styles.cover}>
        {photos.length > 0 ? (
          <ScrollView horizontal pagingEnabled showsHorizontalScrollIndicator={false}>
            {photos.map((uri) => (
              <Image
                key={uri}
                source={{ uri }}
                style={{ width, height: 260 }}
                contentFit="cover"
                transition={200}
              />
            ))}
          </ScrollView>
        ) : (
          <View style={[StyleSheet.absoluteFill, { backgroundColor: colors.primaryLight }]}>
            <View style={styles.coverPlaceholder}>
              <MaterialCommunityIcons name={t.icon as any} size={44} color={colors.primary} />
            </View>
          </View>
        )}

        {photos.length > 1 ? (
          <View style={styles.photoCount}>
            <MaterialCommunityIcons name="image-multiple" size={11} color={colors.white} />
            <Txt variant="tiny" color={colors.white} style={{ fontSize: 10 }}>
              {photos.length}
            </Txt>
          </View>
        ) : null}
      </View>

      <View style={styles.body}>
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
          <Eyebrow color={template?.accent_color || colors.primary}>{t.label}</Eyebrow>
          {page.is_owner ? <Badge label={badge.label} fg={badge.fg} bg={badge.bg} /> : null}
        </View>

        <Txt variant="h1" color={fg} style={{ marginTop: spacing.sm }}>
          {celebration.title}
        </Txt>

        {celebration.celebrant_name ? (
          <Txt variant="body" color={sub} style={{ marginTop: 6 }}>
            for {celebration.celebrant_name}
          </Txt>
        ) : null}

        {celebration.description ? (
          <Txt variant="small" color={sub} style={{ marginTop: spacing.md, lineHeight: 21 }}>
            {celebration.description}
          </Txt>
        ) : null}

        <View style={styles.metaRow}>
          {shortDate(celebration.display_date) ? (
            <Meta icon="calendar-outline" text={shortDate(celebration.display_date)!} color={sub} />
          ) : null}
          {celebration.venue ? <Meta icon="map-marker-outline" text={celebration.venue} color={sub} /> : null}
          <Meta icon="eye-outline" text={`${count(celebration.view_count)} views`} color={sub} />
        </View>

        {/* Countdown to the end (or start) date. */}
        {page.countdown ? (
          <View style={styles.countdown}>
            <CountUnit value={page.countdown.days} label="days" />
            <CountUnit value={page.countdown.hours} label="hours" />
            <CountUnit value={page.countdown.mins} label="mins" />
          </View>
        ) : null}

        {/* Raised, and who gave. */}
        <View style={styles.raised}>
          <View style={{ flex: 1 }}>
            <Eyebrow>Raised so far</Eyebrow>
            <Txt variant="stat" style={{ marginTop: 4 }}>
              {money(page.totals.raised, page.currency.symbol)}
            </Txt>
          </View>

          <View style={{ alignItems: 'flex-end' }}>
            <Eyebrow>Wishes</Eyebrow>
            <Txt variant="h2" style={{ marginTop: 4 }}>
              {count(page.totals.comments)}
            </Txt>
          </View>
        </View>

        {page.supporters.length > 0 ? (
          <Txt variant="small" color={colors.muted} style={{ marginTop: spacing.md }}>
            From {page.supporters[0].name}
            {page.supporters.length > 1
              ? ` and ${page.supporters.length - 1} other${page.supporters.length > 2 ? 's' : ''}`
              : ''}
          </Txt>
        ) : null}
      </View>
    </View>
  );
}

function Meta({ icon, text, color }: { icon: any; text: string; color: string }) {
  return (
    <View style={{ flexDirection: 'row', alignItems: 'center', gap: 5 }}>
      <MaterialCommunityIcons name={icon} size={13} color={color} />
      <Txt variant="small" color={color}>
        {text}
      </Txt>
    </View>
  );
}

function CountUnit({ value, label }: { value: number; label: string }) {
  return (
    <View style={styles.countUnit}>
      <Txt variant="h2" color={colors.primary}>
        {value}
      </Txt>
      <Eyebrow>{label}</Eyebrow>
    </View>
  );
}

const styles = StyleSheet.create({
  cover: {
    height: 260,
    backgroundColor: colors.surface2,
  },
  coverPlaceholder: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  photoCount: {
    position: 'absolute',
    bottom: spacing.md,
    right: spacing.lg,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: radii.pill,
    backgroundColor: 'rgba(0, 0, 0, 0.55)',
  },
  body: {
    padding: spacing.lg,
  },
  metaRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.lg,
    marginTop: spacing.lg,
  },
  countdown: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.xl,
  },
  countUnit: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: spacing.md,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    backgroundColor: colors.surface,
  },
  raised: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    marginTop: spacing.xl,
    paddingTop: spacing.lg,
    borderTopWidth: 1,
    borderTopColor: colors.line,
  },
});
