import { Redirect } from 'expo-router';

import { useAuth } from '../src/store/auth';

/**
 * The entry route. AuthGate in _layout already keeps the session and the visible
 * route in step; this just decides where a cold start lands.
 */
export default function Index() {
  const { isSignedIn } = useAuth();

  return <Redirect href={isSignedIn ? '/(tabs)' : '/(auth)/login'} />;
}
