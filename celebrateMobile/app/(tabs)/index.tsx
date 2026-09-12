import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { Link, useRouter } from 'expo-router';
import { Alert, Pressable, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { celebrations, dashboard } from '../../src/api/endpoints';
import type { Celebration } from '../../src/api/types';
import { AddEventCard, EventCard } from '../../src/components/EventCard';
import { PageHead, Screen } from '../../src/components/Screen';
import { Button, EmptyState, ErrorState, Loading, SectionHeader, StatCard, Txt } from '../../src/components/ui';
import { count, initials, money } from '../../src/lib/format';
import { useAuth } from '../../src/store/auth';
import { colors, radii, spacing } from '../../src/theme';

/**
 * My Events — the overview, and the only screen that greets you.
 *
 * Mirrors dashboard/partials/events.blade.php: greeting, four stat tiles, then
 * every celebration page you've made.
 */
export default function EventsScreen() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const { user } = useAuth();

  const summary = useQuery({ queryKey: ['dashboard', 'summary'], queryFn: dashboard.summary });
  const list = useQuery({ queryKey: ['dashboard', 'celebrations'], queryFn: () => dashboard.celebrations() });

  const remove = useMutation({
    mutationFn: (slug: string) => celebrations.destroy(slug),
    onSuccess: () => {
      // The stat tiles count events, so they go stale too.
      queryClient.invalidateQueries({ queryKey: ['dashboard'] });
    },
    onError: (e) => Alert.alert('Could not delete', e instanceof ApiError ? e.message : 'Please try again.'),
  });

  function confirmDelete(c: Celebration) {
    Alert.alert(
      'Delete this celebration?',
      `"${c.title}" and everything on it will be removed. This cannot be undone.`,
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Delete', style: 'destructive', onPress: () => remove.mutate(c.slug) },
      ],
    );
  }

  const refreshing = summary.isFetching || list.isFetching;
  const refresh = () => {
    summary.refetch();
    list.refetch();
  };

  if (list.isLoading && summary.isLoading) return <Loading label="Loading your celebrations…" />;

  if (list.isError && !list.data) {
    return <ErrorState message={(list.error as ApiError)?.message ?? 'Could not load your events.'} onRetry={refresh} />;
  }

  const s = summary.data;
  const items = list.data?.data ?? [];

  return (
    <Screen refreshing={refreshing} onRefresh={refresh}>
      <PageHead
        eyebrow={s?.greeting}
        title={`Welcome back, ${s?.user.first_name ?? user?.first_name ?? 'friend'}`}
        sub="Here's what's happening with your celebrations today."
        action={<AvatarLink name={s?.user.name ?? user?.name} photo={user?.profile_photo} />}
      />

      {s ? (
        <View style={{ gap: spacing.md, marginBottom: spacing.xl }}>
          <View style={{ flexDirection: 'row', gap: spacing.md }}>
            <StatCard
              icon="calendar-star"
              label="Total events"
              value={count(s.stats.total)}
              sub={`${s.stats.published} live`}
            />
            <StatCard icon="eye-outline" label="Page views" value={count(s.stats.views)} sub="across all pages" />
          </View>
          <View style={{ flexDirection: 'row', gap: spacing.md }}>
            <StatCard
              icon="message-outline"
              label="Messages"
              value={count(s.stats.messages)}
              sub="wishes received"
            />
            <StatCard
              icon="wallet-outline"
              label="Wallet balance"
              value={
                s.wallet.has_local_wallet
                  ? money(s.wallet.local, s.wallet.symbol)
                  : money(s.wallet.global, '$')
              }
              sub={s.wallet.has_local_wallet ? `Global ${money(s.wallet.global, '$')}` : 'available balance'}
              accent={colors.ok}
            />
          </View>
        </View>
      ) : null}

      <SectionHeader title="My events" subtitle="Every celebration page you've created" />

      <AddEventCard onPress={() => router.push('/modal/create-celebration')} />

      {items.length === 0 ? (
        <EmptyState
          icon="calendar-star"
          title="No celebrations yet"
          body="Your event pages will appear here. Create your first one — it only takes a minute and it's completely free."
          action={
            <Button
              title="Create your first celebration"
              icon="plus"
              onPress={() => router.push('/modal/create-celebration')}
            />
          }
        />
      ) : (
        items.map((c) => <EventCard key={c.id} celebration={c} onDelete={confirmDelete} />)
      )}
    </Screen>
  );
}

/** Tapping the avatar opens the profile — the rail's account row on the web. */
function AvatarLink({ name, photo }: { name?: string | null; photo?: string | null }) {
  return (
    <Link href="/profile" asChild>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Your profile"
        style={{
          width: 42,
          height: 42,
          borderRadius: radii.pill,
          backgroundColor: colors.primaryLight,
          alignItems: 'center',
          justifyContent: 'center',
          overflow: 'hidden',
        }}
      >
        {photo ? (
          <Image source={{ uri: photo }} style={{ width: '100%', height: '100%' }} contentFit="cover" />
        ) : (
          <Txt variant="bodyStrong" color={colors.primary}>
            {initials(name)}
          </Txt>
        )}
      </Pressable>
    </Link>
  );
}
