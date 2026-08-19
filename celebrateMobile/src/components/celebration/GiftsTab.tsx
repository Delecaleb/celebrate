import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { Image } from 'expo-image';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import type { CelebrationPage } from '../../api/types';
import { money, relative } from '../../lib/format';
import { colors, radii, spacing } from '../../theme';
import { Button, EmptyState, Eyebrow, SectionHeader, Txt } from '../ui';

/**
 * The gift plate, plus the list of everyone who has given.
 *
 * Prices are already converted into the viewer's currency by the server, so a
 * guest in Lagos and one in London each see their own.
 */
export function GiftsTab({ page, slug }: { page: CelebrationPage; slug: string }) {
  const router = useRouter();
  const [showSupporters, setShowSupporters] = useState(false);

  const plate = page.platform_gifts.data;
  const celebration = page.celebration.data;

  return (
    <View>
      <SectionHeader
        title="Send a gift"
        subtitle={
          page.is_owner
            ? 'What guests can send you'
            : `Pick something for ${celebration.celebrant_name ?? 'the celebrant'}`
        }
      />

      {celebration.allow_gifts === false && !page.is_owner ? (
        <View style={styles.closed}>
          <MaterialCommunityIcons name="lock-outline" size={15} color={colors.muted} />
          <Txt variant="small" color={colors.muted}>
            The celebrant has turned off gifts for this page.
          </Txt>
        </View>
      ) : plate.length === 0 ? (
        <EmptyState
          icon="gift-outline"
          title="No gifts available"
          body="There are no gifts set up on the platform yet. Check back soon."
        />
      ) : (
        <View style={styles.plate}>
          {plate.map((gift) => (
            <Pressable
              key={gift.id}
              accessibilityRole="button"
              disabled={page.is_owner}
              onPress={() =>
                router.push({
                  pathname: '/modal/gift-plate',
                  params: {
                    slug,
                    giftId: String(gift.id),
                    celebrationId: String(celebration.id),
                    name: gift.name,
                    price: String(gift.display_price),
                    symbol: page.currency.symbol,
                    balance: String(page.wallet_balance),
                    authed: page.is_authenticated ? '1' : '0',
                  },
                })
              }
              style={({ pressed }) => [styles.giftCell, pressed && { opacity: 0.85 }]}
            >
              <View style={styles.giftImage}>
                {gift.image ? (
                  <Image
                    source={{ uri: gift.image }}
                    style={{ width: '100%', height: '100%' }}
                    contentFit="contain"
                    transition={150}
                  />
                ) : (
                  <MaterialCommunityIcons name="gift" size={26} color={colors.primary} />
                )}
              </View>

              <Txt variant="small" style={{ fontWeight: '700', marginTop: spacing.sm }} numberOfLines={1}>
                {gift.name}
              </Txt>
              <Txt variant="small" color={colors.primary} style={{ marginTop: 2, fontWeight: '700' }}>
                {money(gift.display_price, page.currency.symbol)}
              </Txt>
            </Pressable>
          ))}
        </View>
      )}

      {/* ── Who has given ── */}
      <View style={{ marginTop: spacing['2xl'] }}>
        <View style={styles.raisedRow}>
          <View>
            <Eyebrow>Total raised</Eyebrow>
            <Txt variant="stat" style={{ marginTop: 4 }}>
              {money(page.totals.raised, page.currency.symbol)}
            </Txt>
          </View>

          {page.supporters.length > 0 ? (
            <Button
              title={showSupporters ? 'Hide' : `${page.supporters.length} supporter${page.supporters.length === 1 ? '' : 's'}`}
              variant="outline"
              size="sm"
              onPress={() => setShowSupporters((v) => !v)}
            />
          ) : null}
        </View>

        {showSupporters ? (
          <View style={styles.supporterList}>
            {page.supporters.map((s, i) => (
              <View key={`${s.name}-${i}`} style={styles.supporterRow}>
                <View style={{ flex: 1 }}>
                  <Txt variant="small" style={{ fontWeight: '700' }}>
                    {s.name}
                  </Txt>
                  <Txt variant="small" color={colors.muted2} style={{ fontSize: 11, marginTop: 2 }}>
                    {s.count > 1 ? `${s.count} gifts · ` : ''}
                    {relative(s.when)}
                  </Txt>
                </View>
                <Txt variant="bodyStrong" color={colors.ok}>
                  {money(s.total, page.currency.symbol)}
                </Txt>
              </View>
            ))}
          </View>
        ) : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  plate: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  giftCell: {
    width: '31%',
    padding: spacing.sm,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    alignItems: 'center',
  },
  giftImage: {
    width: '100%',
    aspectRatio: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.primaryFaint,
    borderRadius: radii.soft.sm,
  },
  closed: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    padding: spacing.md,
    backgroundColor: colors.surface2,
    borderRadius: radii.card,
  },
  raisedRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    paddingTop: spacing.lg,
    borderTopWidth: 1,
    borderTopColor: colors.line,
  },
  supporterList: {
    marginTop: spacing.md,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    overflow: 'hidden',
  },
  supporterRow: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.line2,
  },
});
