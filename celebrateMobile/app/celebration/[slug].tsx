import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useState } from 'react';
import { Pressable, ScrollView, Share, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { ApiError } from '../../src/api/client';
import { celebrations } from '../../src/api/endpoints';
import { CelebrationHeader } from '../../src/components/celebration/Header';
import { GiftsTab } from '../../src/components/celebration/GiftsTab';
import { RegistryTab } from '../../src/components/celebration/RegistryTab';
import { SettingsTab } from '../../src/components/celebration/SettingsTab';
import { WishesTab } from '../../src/components/celebration/WishesTab';
import { ErrorState, IconButton, Loading, Txt } from '../../src/components/ui';
import { colors, radii, spacing } from '../../src/theme';

type TabKey = 'wishes' | 'registry' | 'gifts' | 'photobook' | 'settings';

const TABS: { key: TabKey; label: string; icon: any; ownerOnly?: boolean }[] = [
  { key: 'wishes', label: 'Wishes', icon: 'message-text-outline' },
  { key: 'registry', label: 'Registry', icon: 'gift-outline' },
  { key: 'gifts', label: 'Gifts', icon: 'party-popper' },
  { key: 'photobook', label: 'Photobook', icon: 'image-multiple-outline' },
  { key: 'settings', label: 'Settings', icon: 'cog-outline', ownerOnly: true },
];

/**
 * The celebration page.
 *
 * One screen with the same five tabs as celebrations/show.blade.php. Everything
 * comes from a single GET — the server has already converted every amount into
 * the viewer's currency, so no tab does maths on money.
 *
 * Public: someone following a shared link can read it, post a wish and pay for a
 * gift without an account. Settings only appears for the owner.
 */
export default function CelebrationScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const [tab, setTab] = useState<TabKey>('wishes');

  const query = useQuery({
    queryKey: ['celebration', slug],
    queryFn: () => celebrations.page(slug!),
    enabled: !!slug,
  });

  if (query.isLoading) return <Loading label="Opening the celebration…" />;

  if (query.isError || !query.data) {
    return (
      <ErrorState
        message={(query.error as ApiError)?.message ?? 'This celebration could not be found.'}
        onRetry={query.refetch}
      />
    );
  }

  const page = query.data;
  const celebration = page.celebration.data;
  const visibleTabs = TABS.filter((t) => !t.ownerOnly || page.is_owner);

  async function share() {
    try {
      await Share.share({
        message: `${celebration.title} — join the celebration: ${celebration.web_url}`,
        url: celebration.web_url,
      });
    } catch {
      /* the user dismissed the share sheet */
    }
  }

  return (
    <View style={{ flex: 1, backgroundColor: colors.surface }}>
      {/* Floating controls over the cover image. */}
      <View style={[styles.topBar, { paddingTop: insets.top + spacing.sm }]}>
        <RoundButton icon="arrow-left" onPress={() => router.back()} label="Go back" />
        <RoundButton icon="share-variant" onPress={share} label="Share this celebration" />
      </View>

      <ScrollView
        contentContainerStyle={{ paddingBottom: insets.bottom + spacing['3xl'] }}
        refreshControl={undefined}
      >
        <CelebrationHeader page={page} />

        {/* Tab strip — horizontally scrollable, since five tabs will not fit. */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.tabStrip}
        >
          {visibleTabs.map((t) => {
            const active = tab === t.key;

            return (
              <Pressable
                key={t.key}
                accessibilityRole="tab"
                accessibilityState={{ selected: active }}
                onPress={() => setTab(t.key)}
                style={[styles.tab, active && { borderBottomColor: colors.primary }]}
              >
                <MaterialCommunityIcons
                  name={t.icon}
                  size={15}
                  color={active ? colors.primary : colors.muted2}
                />
                <Txt variant="small" color={active ? colors.primary : colors.muted} style={{ fontWeight: '700' }}>
                  {t.label}
                </Txt>
              </Pressable>
            );
          })}
        </ScrollView>

        <View style={styles.tabBody}>
          {tab === 'wishes' && <WishesTab page={page} slug={slug!} />}
          {tab === 'registry' && <RegistryTab page={page} slug={slug!} />}
          {tab === 'gifts' && <GiftsTab page={page} slug={slug!} />}
          {tab === 'photobook' && <PhotobookTab page={page} />}
          {tab === 'settings' && <SettingsTab page={page} slug={slug!} />}
        </View>
      </ScrollView>
    </View>
  );
}

/** The photobook tab — a grid of every cover photo, plus the export action. */
function PhotobookTab({ page }: { page: import('../../src/api/types').CelebrationPage }) {
  const router = useRouter();
  const celebration = page.celebration.data;
  const photos = celebration.cover_photos;

  return (
    <View>
      <Txt variant="h3">Photobook</Txt>
      <Txt variant="small" color={colors.muted} style={{ marginTop: 6, lineHeight: 20 }}>
        Every photo from this celebration, ready to save or share as one keepsake page.
      </Txt>

      {photos.length === 0 ? (
        <Txt variant="small" color={colors.muted2} style={{ marginTop: spacing.xl }}>
          No photos yet. Add cover photos from the Settings tab and they'll appear here.
        </Txt>
      ) : (
        <>
          <View style={styles.photoGrid}>
            {photos.map((uri) => (
              <View key={uri} style={styles.photoCell}>
                <Image
                  source={{ uri }}
                  style={{ width: '100%', height: '100%' }}
                  contentFit="cover"
                  transition={150}
                />
              </View>
            ))}
          </View>

          <Pressable
            accessibilityRole="button"
            onPress={() => router.push({ pathname: '/modal/photobook', params: { slug: celebration.slug } })}
            style={styles.photobookButton}
          >
            <MaterialCommunityIcons name="book-open-page-variant-outline" size={17} color={colors.white} />
            <Txt variant="bodyStrong" color={colors.white}>
              Open photobook
            </Txt>
          </Pressable>
        </>
      )}
    </View>
  );
}

function RoundButton({ icon, onPress, label }: { icon: any; onPress: () => void; label: string }) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      onPress={onPress}
      style={({ pressed }) => [styles.roundButton, pressed && { opacity: 0.8 }]}
    >
      <MaterialCommunityIcons name={icon} size={19} color={colors.ink} />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  topBar: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    zIndex: 10,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
  },
  roundButton: {
    width: 38,
    height: 38,
    borderRadius: radii.pill,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabStrip: {
    paddingHorizontal: spacing.lg,
    borderBottomWidth: 1,
    borderBottomColor: colors.line,
  },
  tab: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.md,
    borderBottomWidth: 2,
    borderBottomColor: 'transparent',
  },
  tabBody: {
    padding: spacing.lg,
  },
  photoGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.lg,
  },
  photoCell: {
    width: '48%',
    aspectRatio: 1,
    borderRadius: radii.soft.sm,
    overflow: 'hidden',
    backgroundColor: colors.surface2,
  },
  photobookButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    marginTop: spacing.xl,
    paddingVertical: spacing.md,
    backgroundColor: colors.primary,
    borderRadius: radii.card,
  },
});
