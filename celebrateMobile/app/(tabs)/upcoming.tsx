import { useQuery } from '@tanstack/react-query';
import { useRouter } from 'expo-router';

import { ApiError } from '../../src/api/client';
import { dashboard } from '../../src/api/endpoints';
import { EventCard } from '../../src/components/EventCard';
import { PageHead, Screen } from '../../src/components/Screen';
import { Button, EmptyState, ErrorState, Loading } from '../../src/components/ui';

/**
 * Upcoming — published pages whose date hasn't passed yet, soonest first.
 */
export default function UpcomingScreen() {
  const router = useRouter();
  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['dashboard', 'upcoming'],
    queryFn: dashboard.upcoming,
  });

  if (isLoading) return <Loading />;

  if (isError && !data) {
    return <ErrorState message={(error as ApiError)?.message ?? 'Could not load upcoming events.'} onRetry={refetch} />;
  }

  const items = data?.data ?? [];

  return (
    <Screen refreshing={isFetching} onRefresh={refetch}>
      <PageHead title="Upcoming" sub="Your published celebrations that haven't happened yet." />

      {items.length === 0 ? (
        <EmptyState
          icon="calendar-clock"
          title="Nothing coming up"
          body="Once you publish a celebration with a future date, it will show up here so you can keep an eye on it."
          action={<Button title="Create a celebration" icon="plus" onPress={() => router.push('/modal/create-celebration')} />}
        />
      ) : (
        items.map((c) => <EventCard key={c.id} celebration={c} />)
      )}
    </Screen>
  );
}
