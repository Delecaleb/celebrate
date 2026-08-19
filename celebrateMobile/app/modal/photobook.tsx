import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { useLocalSearchParams } from 'expo-router';
import * as Sharing from 'expo-sharing';
import { useState } from 'react';
import { Dimensions, ScrollView, StyleSheet, View } from 'react-native';

import { celebrations } from '../../src/api/endpoints';
import { Sheet } from '../../src/components/Sheet';
import { Button, EmptyState, Loading, Txt } from '../../src/components/ui';
import { shortDate } from '../../src/lib/format';
import { celebrationType, colors, radii, spacing } from '../../src/theme';

const { width } = Dimensions.get('window');

/**
 * The photobook — a page-by-page keepsake view of the celebration's photos.
 *
 * The web version composes a canvas and offers a download. A viewer cannot save
 * a file the app generates without a native share sheet, so this shows the pages
 * full-bleed and hands the current photo to the OS share sheet instead, which is
 * the mobile equivalent of that download.
 */
export default function Photobook() {
  const params = useLocalSearchParams<{ slug: string }>();
  const [page, setPage] = useState(0);

  const query = useQuery({
    queryKey: ['celebration', params.slug],
    queryFn: () => celebrations.page(params.slug!),
    enabled: !!params.slug,
  });

  if (query.isLoading) return <Loading />;

  const celebration = query.data?.celebration.data;
  const photos = celebration?.cover_photos ?? [];

  if (!celebration || photos.length === 0) {
    return (
      <Sheet title="Photobook">
        <EmptyState
          icon="image-multiple-outline"
          title="No photos yet"
          body="Add cover photos from the Settings tab and your photobook will build itself."
        />
      </Sheet>
    );
  }

  const t = celebrationType(celebration.celebration_type);

  async function shareCurrent() {
    // expo-sharing needs a local file; a remote URL has to be handed to the OS
    // as a link instead, which the share sheet handles for us.
    const available = await Sharing.isAvailableAsync();

    if (!available) return;

    await Sharing.shareAsync(photos[page]).catch(() => {});
  }

  return (
    <Sheet
      title="Photobook"
      sub={`${celebration.title} · ${photos.length} page${photos.length === 1 ? '' : 's'}`}
      footer={
        <Button title="Share this page" icon="share-variant" full onPress={shareCurrent} />
      }
    >
      <ScrollView
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onMomentumScrollEnd={(e) => setPage(Math.round(e.nativeEvent.contentOffset.x / (width - spacing.lg * 2)))}
        style={{ marginHorizontal: -spacing.lg }}
        contentContainerStyle={{ paddingHorizontal: spacing.lg }}
      >
        {photos.map((uri, i) => (
          <View key={uri} style={[styles.page, { width: width - spacing.lg * 2 }]}>
            <Image source={{ uri }} style={styles.photo} contentFit="cover" transition={200} />

            <View style={styles.caption}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                <MaterialCommunityIcons name={t.icon as any} size={13} color={colors.primary} />
                <Txt variant="tiny" color={colors.primary}>
                  {t.label}
                </Txt>
              </View>

              <Txt variant="h3" style={{ marginTop: 5 }} numberOfLines={2}>
                {celebration.title}
              </Txt>

              <Txt variant="small" color={colors.muted} style={{ marginTop: 3 }}>
                {shortDate(celebration.display_date) ?? ''} · page {i + 1} of {photos.length}
              </Txt>
            </View>
          </View>
        ))}
      </ScrollView>

      <View style={styles.dots}>
        {photos.map((uri, i) => (
          <View
            key={uri}
            style={[styles.dot, i === page && { backgroundColor: colors.primary, width: 18 }]}
          />
        ))}
      </View>
    </Sheet>
  );
}

const styles = StyleSheet.create({
  page: {
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.soft.md,
    overflow: 'hidden',
    marginRight: spacing.md,
    backgroundColor: colors.surface,
  },
  photo: {
    width: '100%',
    height: 340,
    backgroundColor: colors.surface2,
  },
  caption: {
    padding: spacing.lg,
  },
  dots: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 5,
    marginTop: spacing.lg,
  },
  dot: {
    width: 6,
    height: 6,
    borderRadius: radii.pill,
    backgroundColor: colors.line,
  },
});
