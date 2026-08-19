import { useQueryClient } from '@tanstack/react-query';
import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

import { clearToken, loadToken, saveToken, setUnauthenticatedHandler } from '../api/client';
import { auth as authApi } from '../api/endpoints';
import type { User } from '../api/types';

type AuthState = {
  /** Undefined while we are still reading the keychain on cold start. */
  user: User | null | undefined;
  isReady: boolean;
  isSignedIn: boolean;
  signIn: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string, confirm: string) => Promise<void>;
  signOut: () => Promise<void>;
  refreshUser: () => Promise<void>;
};

const AuthContext = createContext<AuthState | null>(null);

/**
 * Holds who is signed in.
 *
 * The token itself lives in api/client (which every request needs); this only
 * tracks the resolved user and the "still checking" state the router gates on.
 */
export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null | undefined>(undefined);
  const [isReady, setIsReady] = useState(false);
  const queryClient = useQueryClient();

  const forget = useCallback(async () => {
    await clearToken();
    setUser(null);
    // Otherwise the next person to sign in briefly sees the last one's data.
    queryClient.clear();
  }, [queryClient]);

  // A 401 from anywhere means the token is dead: drop the session so the
  // router sends us to sign-in.
  useEffect(() => {
    setUnauthenticatedHandler(() => {
      setUser(null);
      queryClient.clear();
    });

    return () => setUnauthenticatedHandler(null);
  }, [queryClient]);

  // Cold start: if there is a stored token, confirm it still works.
  useEffect(() => {
    let cancelled = false;

    (async () => {
      const token = await loadToken();

      if (!token) {
        if (!cancelled) {
          setUser(null);
          setIsReady(true);
        }
        return;
      }

      try {
        const me = await authApi.me();
        if (!cancelled) setUser(me.data);
      } catch {
        // Expired or revoked — the interceptor has already cleared it.
        if (!cancelled) setUser(null);
      } finally {
        if (!cancelled) setIsReady(true);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, []);

  const signIn = useCallback(async (email: string, password: string) => {
    const res = await authApi.login({ email, password, device_name: 'mobile' });
    await saveToken(res.token);
    setUser(res.user.data);
  }, []);

  const register = useCallback(
    async (name: string, email: string, password: string, confirm: string) => {
      const res = await authApi.register({
        name,
        email,
        password,
        password_confirmation: confirm,
        device_name: 'mobile',
      });
      await saveToken(res.token);
      setUser(res.user.data);
    },
    [],
  );

  const signOut = useCallback(async () => {
    // Tell the server first so the token is revoked, but never block sign-out
    // on a network failure — the local session goes either way.
    try {
      await authApi.logout();
    } catch {
      /* ignore */
    }

    await forget();
  }, [forget]);

  const refreshUser = useCallback(async () => {
    try {
      const me = await authApi.me();
      setUser(me.data);
    } catch {
      /* the interceptor handles a dead token */
    }
  }, []);

  const value = useMemo<AuthState>(
    () => ({
      user,
      isReady,
      isSignedIn: !!user,
      signIn,
      register,
      signOut,
      refreshUser,
    }),
    [user, isReady, signIn, register, signOut, refreshUser],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);

  if (!ctx) {
    throw new Error('useAuth must be used inside <AuthProvider>.');
  }

  return ctx;
}
