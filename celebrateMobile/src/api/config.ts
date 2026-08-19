import Constants from 'expo-constants';
import { Platform } from 'react-native';

/**
 * Where the Laravel app lives.
 *
 * Order of preference:
 *   1. EXPO_PUBLIC_API_URL — set this in .env for a real device or a deployment.
 *   2. app.json → extra.apiUrl
 *   3. A per-platform localhost guess, below.
 *
 * The guess matters because "localhost" means the device, not your machine:
 * the Android emulator reaches the host through 10.0.2.2, while the iOS
 * simulator shares the host's loopback. A physical phone can reach neither —
 * use your LAN IP via EXPO_PUBLIC_API_URL.
 */
const fallback = Platform.select({
  android: 'http://10.0.2.2:8000',
  ios: 'http://127.0.0.1:8000',
  default: 'http://127.0.0.1:8000',
});

const configured =
  process.env.EXPO_PUBLIC_API_URL ??
  (Constants.expoConfig?.extra?.apiUrl as string | undefined);

export const API_ORIGIN = (configured || fallback).replace(/\/+$/, '');

export const API_BASE = `${API_ORIGIN}/api/v1`;

/** The scheme PaymentBridgeController redirects back to. Matches app.json. */
export const APP_SCHEME = 'celebratemi';

/** Where a gateway hands control back to us. */
export const PAYMENT_RETURN_URL = `${APP_SCHEME}://payment/return`;
