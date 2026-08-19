import { useQuery } from '@tanstack/react-query';

import { ApiError } from '../../src/api/client';
import { dashboard } from '../../src/api/endpoints';
import { EventCard } from '../../src/components/EventCard';
import { PageHead, Screen } from '../../src/components/Screen';
import { EmptyState, ErrorState, Loading } from '../../src/components/ui';

/**
 * Discover — public celebrations belonging to other people.
 */
export default function DiscoverScreen() {
  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['dashboard', 'discover'],
    queryFn: () => dashboard.discover(),
  });

  if (isLoading) return <Loading />;

  if (isError && !data) {
    return <ErrorState message={(error as ApiError)?.message ?? 'Could not load Discover.'} onRetry={refetch} />;
  }

  const items = data?.data ?? [];

  return (
    <Screen refreshing={isFetching} onRefresh={refetch}>
      <PageHead title="Discover" sub="Public celebrations from the rest of the community — send a wish or a gift." />

      {items.length === 0 ? (
        <EmptyState
          icon="compass-outline"
          title="Nothing to discover yet"
          body="When other people publish public celebration pages, they'll appear here."
        />
      ) : (
        items.map((c) => <EventCard key={c.id} celebration={c} showOwner />)
      )}
    </Screen>
  );
}
