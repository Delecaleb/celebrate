import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { useRouter } from 'expo-router';
import { Alert, StyleSheet, View } from 'react-native';

import { ApiError } from '../../api/client';
import { wishes as wishesApi } from '../../api/endpoints';
import type { CelebrationPage, Wish } from '../../api/types';
import { money } from '../../lib/format';
import { colors, radii, spacing } from '../../theme';
import { Badge, Button, EmptyState, IconButton, Progress, SectionHeader, Txt } from '../ui';

/**
 * The registry.
 *
 * Each item shows what it costs, how much has come in and how far along it is.
 * display_target/display_current are already in the viewer's currency, so this
 * only formats them.
 */
export function RegistryTab({ page, slug }: { page: CelebrationPage; slug: string }) {
  const router = useRouter();
  const queryClient = useQueryClient();
  const items = page.wishes.data;

  const remove = useMutation({
    mutationFn: (id: number) => wishesApi.destroy(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['celebration', slug] }),
    onError: (e) => Alert.alert('Could not remove', e instanceof ApiError ? e.message : 'Please try again.'),
  });

  function confirmRemove(wish: Wish) {
    Alert.alert('Remove this item?', `"${wish.name}" will be taken off the registry.`, [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Remove', style: 'destructive', onPress: () => remove.mutate(wish.id) },
    ]);
  }

  return (
    <View>
      <SectionHeader
        title="Registry"
        subtitle={page.is_owner ? 'What you have asked for' : 'Chip in towards something specific'}
        action={
          page.is_owner ? (
            <Button
              title="Add"
              icon="plus"
              size="sm"
              onPress={() =>
                router.push({
                  pathname: '/modal/add-wishes',
                  params: { slug, id: String(page.celebration.data.id) },
                })
              }
            />
          ) : undefined
        }
      />

      {items.length === 0 ? (
        <EmptyState
          icon="gift-outline"
          title="Nothing on the registry yet"
          body={
            page.is_owner
              ? 'Add the things you would love — guests can chip in towards any of them.'
              : 'The celebrant has not added any registry items yet.'
          }
          action={
            page.is_owner ? (
              <Button
                title="Add registry items"
                icon="plus"
                onPress={() =>
                  router.push({
                    pathname: '/modal/add-wishes',
                    params: { slug, id: String(page.celebration.data.id) },
                  })
                }
              />
            ) : undefined
          }
        />
      ) : (
        <View style={{ gap: spacing.md }}>
          {items.map((wish) => (
            <View key={wish.id} style={styles.item}>
              <View style={{ flexDirection: 'row', gap: spacing.md }}>
                <View style={styles.thumb}>
                  {wish.wish_image ? (
                    <Image
                      source={{ uri: wish.wish_image }}
                      style={{ width: '100%', height: '100%' }}
                      contentFit="cover"
                    />
                  ) : (
                    <MaterialCommunityIcons name="gift-outline" size={22} color={colors.primary} />
                  )}
                </View>

                <View style={{ flex: 1 }}>
                  <View style={{ flexDirection: 'row', alignItems: 'flex-start' }}>
                    <Txt variant="h3" style={{ flex: 1 }} numberOfLines={2}>
                      {wish.name}
                    </Txt>
                    {page.is_owner ? (
                      <IconButton
                        icon="trash-can-outline"
                        color={colors.danger}
                        accessibilityLabel={`Remove ${wish.name}`}
                        onPress={() => confirmRemove(wish)}
                      />
                    ) : null}
                  </View>

                  {wish.description ? (
                    <Txt variant="small" color={colors.muted} style={{ marginTop: 4, lineHeight: 19 }}>
                      {wish.description}
                    </Txt>
                  ) : null}

                  {wish.is_funded ? (
                    <View style={{ marginTop: spacing.sm }}>
                      <Badge label="Fully funded" fg={colors.ok} bg={colors.okTint} icon="check-circle" />
                    </View>
                  ) : null}
                </View>
              </View>

              {wish.display_target > 0 ? (
                <View style={{ marginTop: spacing.md }}>
                  <View style={styles.amounts}>
                    <Txt variant="bodyStrong">
                      {money(wish.display_current, page.currency.symbol)}
                    </Txt>
                    <Txt variant="small" color={colors.muted}>
                      of {money(wish.display_target, page.currency.symbol)}
                    </Txt>
                  </View>

                  <Progress percent={wish.percent_funded} />

                  <Txt variant="small" color={colors.muted2} style={{ marginTop: 6, fontSize: 11 }}>
                    {wish.percent_funded}% funded
                    {wish.contribution_count > 0
                      ? ` · ${wish.contribution_count} contribution${wish.contribution_count === 1 ? '' : 's'}`
                      : ''}
                  </Txt>
                </View>
              ) : null}

              {/* The owner does not contribute to their own registry. */}
              {!page.is_owner && !wish.is_funded ? (
                <Button
                  title="Contribute"
                  variant="outline"
                  size="sm"
                  icon="hand-heart-outline"
                  full
                  style={{ marginTop: spacing.md }}
                  onPress={() =>
                    router.push({
                      pathname: '/modal/contribute',
                      params: {
                        slug,
                        wishId: String(wish.id),
                        name: wish.name,
                        currency: page.currency.code,
                        symbol: page.currency.symbol,
                        remaining: String(Math.max(0, wish.display_target - wish.display_current)),
                        balance: String(page.wallet_balance),
                        authed: page.is_authenticated ? '1' : '0',
                      },
                    })
                  }
                />
              ) : null}
            </View>
          ))}
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  item: {
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    padding: spacing.lg,
  },
  thumb: {
    width: 56,
    height: 56,
    borderRadius: radii.soft.sm,
    backgroundColor: colors.primaryFaint,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  amounts: {
    flexDirection: 'row',
    alignItems: 'baseline',
    gap: 5,
    marginBottom: spacing.sm,
  },
});
