import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { StyleSheet, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { dashboard } from '../../src/api/endpoints';
import type { Notification } from '../../src/api/types';
import { PageHead, Screen } from '../../src/components/Screen';
import { Button, EmptyState, ErrorState, Loading, Txt } from '../../src/components/ui';
import { relative } from '../../src/lib/format';
import { colors, radii, spacing } from '../../src/theme';

/**
 * Activity — the notification list, with the same "mark all read" action the web
 * rail badge depends on.
 */
export default function ActivityScreen() {
  const queryClient = useQueryClient();

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['dashboard', 'activity'],
    queryFn: () => dashboard.activity(),
  });

  const markRead = useMutation({
    mutationFn: dashboard.markActivityRead,
    onSuccess: () => {
      // Refresh both the list and the summary the tab badge reads from.
      queryClient.invalidateQueries({ queryKey: ['dashboard', 'activity'] });
      queryClient.invalidateQueries({ queryKey: ['dashboard', 'summary'] });
    },
  });

  if (isLoading) return <Loading />;

  if (isError && !data) {
    return <ErrorState message={(error as ApiError)?.message ?? 'Could not load activity.'} onRetry={refetch} />;
  }

  const items = data?.data ?? [];
  const unread = data?.meta?.unread_count ?? 0;

  return (
    <Screen refreshing={isFetching} onRefresh={refetch}>
      <PageHead
        title="Activity"
        sub="Gifts, wishes and payouts as they happen."
        action={
          unread > 0 ? (
            <Button
              title="Mark all read"
              variant="outline"
              size="sm"
              loading={markRead.isPending}
              onPress={() => markRead.mutate()}
            />
          ) : undefined
        }
      />

      {items.length === 0 ? (
        <EmptyState
          icon="bell-outline"
          title="Nothing here yet"
          body="When someone sends a gift, posts a wish or your payout is processed, you'll hear about it here."
        />
      ) : (
        <View style={styles.list}>
          {items.map((n) => (
            <Row key={n.id} notification={n} />
          ))}
        </View>
      )}
    </Screen>
  );
}

function Row({ notification }: { notification: Notification }) {
  const unread = !notification.is_read;

  return (
    <View style={[styles.row, unread && { backgroundColor: colors.primaryFaint }]}>
      <View style={[styles.dot, { backgroundColor: unread ? colors.primary : colors.line }]}>
        <MaterialCommunityIcons
          name={iconFor(notification.type)}
          size={15}
          color={unread ? colors.white : colors.muted}
        />
      </View>

      <View style={{ flex: 1 }}>
        {notification.title ? <Txt variant="bodyStrong">{notification.title}</Txt> : null}
        {notification.message ? (
          <Txt variant="small" color={colors.muted} style={{ marginTop: 2, lineHeight: 19 }}>
            {notification.message}
          </Txt>
        ) : null}
        <Txt variant="small" color={colors.muted2} style={{ marginTop: 5, fontSize: 11 }}>
          {relative(notification.created_at, notification.created_at_human)}
        </Txt>
      </View>
    </View>
  );
}

/** Best-guess glyph from the notification type string. */
function iconFor(type: string | null): any {
  const t = (type ?? '').toLowerCase();

  if (t.includes('gift')) return 'gift-outline';
  if (t.includes('withdraw') || t.includes('payout')) return 'bank-transfer-out';
  if (t.includes('wish') || t.includes('comment')) return 'message-outline';
  if (t.includes('wallet') || t.includes('fund')) return 'wallet-outline';

  return 'bell-outline';
}

const styles = StyleSheet.create({
  list: {
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    overflow: 'hidden',
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
    padding: spacing.lg,
    borderBottomWidth: 1,
    borderBottomColor: colors.line2,
  },
  dot: {
    width: 30,
    height: 30,
    borderRadius: radii.pill,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
