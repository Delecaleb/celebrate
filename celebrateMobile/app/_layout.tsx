import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Stack, useRouter, useSegments } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useRef } from 'react';
import { ActivityIndicator, View } from 'react-native';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { ApiError } from '../src/api/client';
import { AuthProvider, useAuth } from '../src/store/auth';
import { colors } from '../src/theme';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // These screens show live figures — balances, view counts, unread badges —
      // so a short staleness window keeps them honest without refetching on
      // every tab press. The web dashboard makes the same call by telling its
      // router not to cache dashboard pages at all.
      staleTime: 30_000,
      retry: (failureCount, error) => {
        // Never retry a request the server refused on purpose.
        if (error instanceof ApiError && error.status >= 400 && error.status < 500) {
          return false;
        }

        return failureCount < 2;
      },
    },
  },
});

/**
 * Keeps the visible route and the session in step.
 *
 * Signed out inside the app → go to sign-in. Signed in while still on an auth
 * screen → go to the dashboard. Nothing renders until the stored token has been
 * checked, so a returning user never sees the login screen flash past.
 */
function AuthGate({ children }: { children: React.ReactNode }) {
  const { isReady, isSignedIn } = useAuth();
  const segments = useSegments();
  const router = useRouter();
  const lastAction = useRef<string | null>(null);

  useEffect(() => {
    if (!isReady) return;

    const group = segments[0];
    const inAuthGroup = group === '(auth)';
    // A celebration page is public — someone following a shared link must be
    // able to read it, and gift on it, without an account.
    const isPublic = group === 'celebration' || group === 'payment';

    if (!isSignedIn && !inAuthGroup && !isPublic) {
      if (lastAction.current !== 'out') {
        lastAction.current = 'out';
        router.replace('/(auth)/login');
      }
      return;
    }

    if (isSignedIn && inAuthGroup) {
      if (lastAction.current !== 'in') {
        lastAction.current = 'in';
        router.replace('/(tabs)');
      }
      return;
    }

    lastAction.current = null;
  }, [isReady, isSignedIn, segments, router]);

  if (!isReady) {
    return (
      <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.surface }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  return <>{children}</>;
}

export default function RootLayout() {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <QueryClientProvider client={queryClient}>
          <AuthProvider>
            <StatusBar style="dark" />
            <AuthGate>
              <Stack
                screenOptions={{
                  headerShown: false,
                  contentStyle: { backgroundColor: colors.surface },
                }}
              >
                <Stack.Screen name="(tabs)" />
                <Stack.Screen name="(auth)" />
                <Stack.Screen name="celebration/[slug]" />
                <Stack.Screen name="bank" options={{ headerShown: true, title: 'Bank account' }} />
                <Stack.Screen name="profile" options={{ headerShown: true, title: 'Profile' }} />
                {/* Sheets — the mobile stand-in for the web's modals. */}
                <Stack.Screen name="modal/create-celebration" options={{ presentation: 'modal' }} />
                <Stack.Screen name="modal/fund-wallet" options={{ presentation: 'modal' }} />
                <Stack.Screen name="modal/withdraw" options={{ presentation: 'modal' }} />
                <Stack.Screen name="modal/add-bank" options={{ presentation: 'modal' }} />
                <Stack.Screen name="modal/gift-plate" options={{ presentation: 'modal' }} />
                <Stack.Screen name="modal/contribute" options={{ presentation: 'modal' }} />
                <Stack.Screen name="modal/add-wishes" options={{ presentation: 'modal' }} />
                <Stack.Screen name="modal/recorder" options={{ presentation: 'fullScreenModal' }} />
                <Stack.Screen name="modal/photobook" options={{ presentation: 'modal' }} />
              </Stack>
            </AuthGate>
          </AuthProvider>
        </QueryClientProvider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
