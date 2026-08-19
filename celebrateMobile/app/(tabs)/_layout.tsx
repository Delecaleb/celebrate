import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useQuery } from '@tanstack/react-query';
import { Tabs } from 'expo-router';
import type { ColorValue } from 'react-native';

import { dashboard } from '../../src/api/endpoints';
import { colors, radii } from '../../src/theme';

type IconName = React.ComponentProps<typeof MaterialCommunityIcons>['name'];

/**
 * The rail, as a tab bar.
 *
 * The web has six items in a fixed left rail; five is the practical ceiling for
 * a bottom bar, so Bank Account — the one item that is a setting rather than a
 * destination — moves under Wallet, which is the only screen that links to it.
 * The icons are the same mdi glyphs the rail uses.
 */
export default function TabsLayout() {
  // Drives the badge on Activity. Cheap, and shared with the dashboard summary
  // through the query cache.
  const { data } = useQuery({
    queryKey: ['dashboard', 'summary'],
    queryFn: dashboard.summary,
    staleTime: 30_000,
  });

  const unread = data?.unread_count ?? 0;

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.muted2,
        tabBarStyle: {
          backgroundColor: colors.surface,
          borderTopColor: colors.line,
          borderTopWidth: 1,
          height: 88,
          paddingTop: 8,
        },
        tabBarLabelStyle: { fontSize: 11, fontWeight: '700' },
        tabBarBadgeStyle: {
          backgroundColor: colors.primary,
          color: colors.white,
          fontSize: 10,
          fontWeight: '800',
          borderRadius: radii.pill,
        },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'Events',
          tabBarIcon: ({ color, size }) => <Icon name="calendar-star" color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="upcoming"
        options={{
          title: 'Upcoming',
          tabBarIcon: ({ color, size }) => <Icon name="calendar-clock" color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="activity"
        options={{
          title: 'Activity',
          tabBarBadge: unread > 0 ? (unread > 99 ? '99+' : unread) : undefined,
          tabBarIcon: ({ color, size }) => <Icon name="bell-outline" color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="wallet"
        options={{
          title: 'Wallet',
          tabBarIcon: ({ color, size }) => <Icon name="wallet-outline" color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="discover"
        options={{
          title: 'Discover',
          tabBarIcon: ({ color, size }) => <Icon name="compass-outline" color={color} size={size} />,
        }}
      />
    </Tabs>
  );
}

/**
 * tabBarIcon hands back a ColorValue, not a plain string, so the prop is typed
 * to match rather than cast at each of the five call sites.
 */
function Icon({ name, color, size }: { name: IconName; color: ColorValue; size: number }) {
  return <MaterialCommunityIcons name={name} color={color} size={size ?? 24} />;
}
