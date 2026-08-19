# CelebrateMi — mobile

The native app. React Native via Expo (SDK 57), talking to the Laravel app in the
parent directory over a JSON API.

It is a native client, not a wrapper: no WebViews, no Blade. Every screen is a
mobile translation of its web counterpart, and the colours come from the very
same `resources/brand.json` the web build reads.

---

## Getting it running

```bash
# 1. From the repo root — the API the app talks to
php artisan serve --host=0.0.0.0 --port=8000

# 2. In here
cp .env.example .env      # then set EXPO_PUBLIC_API_URL for how you're running
npm install

# 3. Build and install the app on a device/emulator (first time only).
#    NOT Expo Go — see the next section for why.
npx expo run:android

# thereafter, just start the bundler and open that app
npm start
```

`EXPO_PUBLIC_API_URL` is the one thing you must get right. `localhost` means the
*device*, not your machine:

| Running on        | Use                                       |
| ----------------- | ----------------------------------------- |
| iOS simulator     | `http://127.0.0.1:8000`                   |
| Android emulator  | `http://10.0.2.2:8000`                    |
| Physical device   | `http://<your-LAN-IP>:8000`               |
| Deployed          | `https://your-domain`                     |

Leave off `/api/v1` — [src/api/config.ts](src/api/config.ts) appends it.

## Expo Go will not run this app — use a development build

If you see **"Project is incompatible with this version of Expo Go"**, nothing is
wrong with your setup. Two separate reasons, and the second one is permanent:

1. **Expo Go for SDK 57 is not on the App Store or Play Store yet** — that build
   is still waiting on store approval, so the "latest" Expo Go you can install is
   for an older SDK. Only `eas go` (iOS device) or Expo CLI (Android, simulators)
   can put the SDK 57 build on a device today.

2. **Even on the right Expo Go, card payments cannot work.** Expo Go hosts your
   project under its own `exp://` scheme, so it cannot own `celebratemi://`. The
   whole checkout round trip depends on Laravel's `/payments/bridge` redirecting
   to `celebratemi://payment/return` — under Expo Go that redirect goes nowhere
   and `useCheckout` never gets its reference back. The camera and recorder are
   the same story.

So build it properly. One-time cost, then it behaves like any other app:

```bash
# plug in a device with USB debugging on, or start an emulator, then:
npx expo run:android        # or: npx expo run:ios   (needs macOS + Xcode)
```

That compiles a dev build with `expo-dev-client` and installs it. After the first
build you just run `npm start` and it connects to that app instead of Expo Go.
Rebuild only when native dependencies change.

**Budget disk space.** The first Android build downloads a Gradle distribution
and the native toolchain — allow ~8–10 GB free. It fails in confusing ways when
the drive is nearly full.

---

## How it's laid out

```
app/                        expo-router routes; the file tree IS the navigation
  _layout.tsx               providers + the auth gate
  (auth)/                   login, register, forgot-password
  (tabs)/                   the dashboard: events, upcoming, activity, wallet, discover
  celebration/[slug].tsx    the celebration page and its five tabs
  modal/                    the sheets that stand in for the web's modals
  payment/return.tsx        cold-start fallback for a gateway deep link
  bank.tsx, profile.tsx

src/
  api/         config, axios client, typed endpoints, response types
  components/  shared UI, plus celebration/ for that page's tabs
  lib/         formatting, the checkout flow
  store/       who is signed in
  theme/       design tokens (tokens.ts is GENERATED)
  ../scripts/sync-brand.mjs
```

### Colours

Never write a hex literal in a screen. `src/theme/tokens.ts` is generated from
`../resources/brand.json` — the file `tailwind.config.js` and `config/brand.php`
already share — so one edit there recolours web and mobile together:

```bash
# after editing resources/brand.json
npm run sync-brand
```

`src/theme/index.ts` maps those tokens onto the same semantic names the web CSS
uses (`primary`, `ink`, `muted`, `line`, `surface`), so a screen reads like its
Blade counterpart.

---

## Things worth knowing before you change anything

**The rail became a tab bar.** The web has six rail items; five is the ceiling for
a bottom bar, so Bank Account — a setting rather than a destination — moved under
Wallet, which was the only screen linking to it anyway.

**Money is never converted on this side.** The API returns every amount already in
the viewer's currency (`display_target`, `display_price`, `totals.raised`), because
that conversion is the server's job and duplicating it would be a second place to
get it wrong. The app only formats.

**Card payments are a three-step round trip.** Neither Paystack nor Stripe will
redirect to a `celebratemi://` URL, and Stripe only substitutes
`{CHECKOUT_SESSION_ID}` into an `http(s)` success URL. So:

1. The app calls `/fund`, `/pay` or `/contribute/pay` and gets a gateway URL.
2. The user pays in an in-app browser. The gateway redirects to Laravel's
   `/payments/bridge`, which 302s to `celebratemi://payment/return?…`.
3. The app calls the matching `/verify`, which is what actually checks with the
   gateway and moves the money.

`useCheckout` in [src/lib/payments.ts](src/lib/payments.ts) runs all three. It
calls verify **even when the browser reports `cancel` or `dismiss`**, because those
also fire when somebody paid and then closed the sheet by hand — skipping the
check there would lose a real payment. Verify is idempotent and answers
`unconfirmed` when nothing was paid.

The bridge deliberately verifies nothing and credits nothing: it has no session
and anyone can hit the URL.

**Guests are first-class.** A celebration page, posting to the wall, and paying by
card all work with no account, matching the web. That is why those routes carry no
`auth:sanctum` and why `UseSanctumGuard` middleware exists — without it
`$request->user()` would resolve against the session guard and always be null, so
an owner would never see the Settings tab.

**Anonymous wishes keep no link back to the account.** `user_id` is left null
rather than stored-and-hidden.

---

## Talking to the API

`routes/api.php` in the parent app, under `/api/v1`, Sanctum bearer tokens. Every
call the app makes lives in [src/api/endpoints.ts](src/api/endpoints.ts) — screens
never build a URL themselves, so the route list has exactly one mirror here.
Response types in [src/api/types.ts](src/api/types.ts) mirror
`app/Http/Resources/*.php` field for field; change one, change the other.

The token is held in `expo-secure-store` and mirrored in memory, since reading the
keychain in front of every request would put a round trip on all traffic. A 401
anywhere clears it and drops you at sign-in.

---

## Not built

- The marketing pages (home, features, pricing, how-it-works, stories). A native
  app normally replaces those with onboarding rather than porting them.
- The admin panel — deliberately web-only.
- Push notifications: `expo-notifications` is installed and permissioned, but
  nothing registers a token yet; the server has no device-token table.
- Reels. The wall plays video wishes inline; the swipeable reels viewer is not
  built.

## Checks

```bash
npm run typecheck                  # tsc --noEmit
npx expo export --platform android # full Metro bundle
```
