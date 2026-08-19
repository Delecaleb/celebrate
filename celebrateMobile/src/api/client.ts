import axios, { AxiosError, AxiosRequestConfig } from 'axios';
import * as SecureStore from 'expo-secure-store';

import { API_BASE } from './config';

const TOKEN_KEY = 'celebratemi.token';

/*
 * The token is held in memory as well as in SecureStore.
 *
 * Every request needs it, and SecureStore is async — reading it per request
 * would put a keychain round-trip in front of all traffic. The module keeps the
 * live copy and SecureStore is only touched when it changes or on cold start.
 */
let authToken: string | null = null;

/** Called when the API rejects our token, so the UI can bounce to sign-in. */
let onUnauthenticated: (() => void) | null = null;

export function setUnauthenticatedHandler(fn: (() => void) | null) {
  onUnauthenticated = fn;
}

export async function loadToken(): Promise<string | null> {
  if (authToken) return authToken;

  try {
    authToken = await SecureStore.getItemAsync(TOKEN_KEY);
  } catch {
    // A corrupt or unavailable keychain entry just means "not signed in".
    authToken = null;
  }

  return authToken;
}

export async function saveToken(token: string): Promise<void> {
  authToken = token;
  await SecureStore.setItemAsync(TOKEN_KEY, token);
}

export async function clearToken(): Promise<void> {
  authToken = null;
  await SecureStore.deleteItemAsync(TOKEN_KEY).catch(() => {});
}

export const http = axios.create({
  baseURL: API_BASE,
  timeout: 20000,
  headers: {
    // Without this Laravel answers auth failures with a redirect to the login
    // *page* instead of a 401, and validation errors with HTML.
    Accept: 'application/json',
  },
});

http.interceptors.request.use((config) => {
  if (authToken) {
    config.headers.Authorization = `Bearer ${authToken}`;
  }

  return config;
});

/**
 * A failed request, already reduced to something a screen can render.
 */
export class ApiError extends Error {
  status: number;
  /** Laravel's field → messages map, when the failure was a 422. */
  errors: Record<string, string[]>;
  code?: string;

  constructor(message: string, status: number, errors: Record<string, string[]> = {}, code?: string) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
    this.code = code;
  }

  /** The first message for a field, for inline form errors. */
  fieldError(field: string): string | undefined {
    return this.errors[field]?.[0];
  }
}

http.interceptors.response.use(
  (response) => response,
  (error: AxiosError<any>) => {
    // No response at all: DNS, timeout, wrong API_URL, server not running.
    if (!error.response) {
      return Promise.reject(
        new ApiError(
          'Cannot reach CelebrateMi. Check your connection and that the server is running.',
          0,
        ),
      );
    }

    const { status, data } = error.response;

    if (status === 401) {
      // Drop the dead token before handing control back, so the next screen
      // does not immediately retry with it.
      void clearToken();
      onUnauthenticated?.();
    }

    const message =
      data?.message ||
      (status === 403 ? 'You do not have permission to do that.' : null) ||
      (status === 404 ? 'That could not be found.' : null) ||
      (status >= 500 ? 'Something went wrong on our end. Please try again.' : null) ||
      'Request failed.';

    return Promise.reject(new ApiError(message, status, data?.errors ?? {}, data?.code));
  },
);

/** GET returning the unwrapped body. */
export async function get<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
  const { data } = await http.get<T>(url, config);
  return data;
}

export async function post<T>(url: string, body?: unknown, config?: AxiosRequestConfig): Promise<T> {
  const { data } = await http.post<T>(url, body, config);
  return data;
}

export async function put<T>(url: string, body?: unknown): Promise<T> {
  const { data } = await http.put<T>(url, body);
  return data;
}

export async function patch<T>(url: string, body?: unknown): Promise<T> {
  const { data } = await http.patch<T>(url, body);
  return data;
}

/** DELETE, optionally with a body — account deletion has to send a password. */
export async function del<T>(url: string, body?: unknown): Promise<T> {
  const { data } = await http.delete<T>(url, body === undefined ? undefined : { data: body });
  return data;
}

/**
 * POST multipart/form-data.
 *
 * React Native's FormData takes {uri, name, type} for a file rather than a Blob,
 * so uploads cannot go through the plain JSON helpers.
 */
export async function postForm<T>(url: string, form: FormData): Promise<T> {
  const { data } = await http.post<T>(url, form, {
    headers: { 'Content-Type': 'multipart/form-data' },
    // Images and video take much longer than a JSON call.
    timeout: 120000,
  });

  return data;
}

/** Turn a picked asset into the shape RN's FormData expects. */
export function fileField(uri: string, fallbackName: string, mimeType?: string) {
  const name = uri.split('/').pop() || fallbackName;
  const ext = name.includes('.') ? name.split('.').pop()!.toLowerCase() : 'jpg';

  const guessed =
    mimeType ??
    (['mp4', 'mov', 'webm'].includes(ext)
      ? `video/${ext === 'mov' ? 'quicktime' : ext}`
      : `image/${ext === 'jpg' ? 'jpeg' : ext}`);

  return { uri, name, type: guessed } as unknown as Blob;
}
