/**
 * Every call the app makes, in one place.
 *
 * Screens never build URLs themselves — they call one of these, so the route
 * list in routes/api.php has exactly one mirror on this side.
 */

import { del, fileField, get, patch, post, postForm, put } from './client';
import type {
  AuthResponse,
  BankAccount,
  Celebration,
  CelebrationPage,
  CheckoutIntent,
  Comment,
  DashboardSummary,
  Meta,
  Notification,
  Paginated,
  Reply,
  User,
  VerifyResult,
  WalletBalances,
  WalletTransaction,
  Wish,
  Withdrawal,
  Wrapped,
} from './types';

// ── Auth ─────────────────────────────────────────────────────────────────────

export const auth = {
  register: (body: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    device_name?: string;
  }) => post<AuthResponse>('/auth/register', body),

  login: (body: { email: string; password: string; device_name?: string }) =>
    post<AuthResponse>('/auth/login', body),

  logout: () => post<{ message: string }>('/auth/logout'),

  me: () => get<Wrapped<User>>('/auth/me'),

  updateProfile: (body: Partial<Pick<User, 'first_name' | 'last_name' | 'username' | 'email' | 'phone' | 'bio' | 'country' | 'state' | 'city'>>) =>
    patch<Wrapped<User>>('/auth/profile', body),

  updateAvatar: (uri: string) => {
    const form = new FormData();
    form.append('photo', fileField(uri, 'avatar.jpg'));
    return postForm<Wrapped<User>>('/auth/avatar', form);
  },

  updatePassword: (body: { current_password: string; password: string; password_confirmation: string }) =>
    put<{ message: string }>('/auth/password', body),

  forgotPassword: (email: string) => post<{ message: string }>('/auth/forgot-password', { email }),

  resendVerification: () => post<{ message: string }>('/auth/email/resend-verification'),

  deleteAccount: (password: string) => del<{ message: string }>('/auth/account', { password }),
};

// ── Dashboard ────────────────────────────────────────────────────────────────

export const dashboard = {
  summary: () => get<DashboardSummary>('/dashboard/summary'),
  celebrations: (page = 1) => get<Paginated<Celebration>>(`/dashboard/celebrations?page=${page}`),
  upcoming: () => get<Paginated<Celebration>>('/dashboard/upcoming'),
  activity: (page = 1) => get<Paginated<Notification>>(`/dashboard/activity?page=${page}`),
  markActivityRead: () => post<{ marked: number; unread_count: number }>('/dashboard/activity/read'),
  discover: (page = 1) => get<Paginated<Celebration>>(`/dashboard/discover?page=${page}`),
};

// ── Celebrations ─────────────────────────────────────────────────────────────

export const celebrations = {
  page: (slug: string) => get<CelebrationPage>(`/celebrations/${slug}`),

  create: (body: {
    celebrantName: string;
    eventType: string;
    startDate: string;
    endDate?: string | null;
    eventTitle?: string | null;
  }) => post<Wrapped<Celebration>>('/celebrations', body),

  update: (
    slug: string,
    body: {
      title: string;
      celebrant_name: string;
      celebration_type: string;
      description?: string | null;
      venue?: string | null;
      event_date?: string | null;
      start_date?: string | null;
      end_date?: string | null;
      is_public: boolean;
      status: string;
    },
  ) => put<Wrapped<Celebration>>(`/celebrations/${slug}`, body),

  destroy: (slug: string) => del<{ message: string }>(`/celebrations/${slug}`),

  /** Replaces the whole set — up to four, as on the web editor. */
  updateCovers: (slug: string, uris: string[]) => {
    const form = new FormData();
    uris.forEach((uri, i) => form.append('cover_photos[]', fileField(uri, `cover-${i}.jpg`)));
    return postForm<Wrapped<Celebration>>(`/celebrations/${slug}/cover-photo`, form);
  },

  setFrame: (slug: string, frameId: number | null) =>
    post<{ frame: Wrapped<unknown> | null }>(`/celebrations/${slug}/frame`, { frame_id: frameId }),

  setSlug: (slug: string, next: string) =>
    post<{ slug: string; url: string }>(`/celebrations/${slug}/slug`, { slug: next }),

  checkSlug: (slug: string, candidate: string) =>
    get<{ available: boolean; reason: string | null }>(
      `/celebrations/${slug}/slug/check?slug=${encodeURIComponent(candidate)}`,
    ),

  applyTemplate: (slug: string, body: { template_id: number; custom_bg?: string | null; custom_text?: string | null }) =>
    post<unknown>(`/celebrations/${slug}/template`, body),

  resetTemplate: (slug: string) => del<unknown>(`/celebrations/${slug}/template`),
};

// ── The wall ─────────────────────────────────────────────────────────────────

export const comments = {
  list: (slug: string, page = 1) => get<Paginated<Comment>>(`/celebrations/${slug}/comments?page=${page}`),

  /**
   * Always multipart: a wish may carry an image or a video, and sending one
   * shape for every case keeps the call site simple.
   */
  create: (body: {
    celebration_id: number;
    comment?: string;
    anonymous?: boolean;
    guest_name?: string;
    imageUri?: string;
    videoUri?: string;
  }) => {
    const form = new FormData();
    form.append('celebration_id', String(body.celebration_id));
    if (body.comment) form.append('comment', body.comment);
    if (body.anonymous) form.append('anonymous', '1');
    if (body.guest_name) form.append('guest_name', body.guest_name);
    if (body.imageUri) form.append('image', fileField(body.imageUri, 'wish.jpg'));
    if (body.videoUri) form.append('video', fileField(body.videoUri, 'wish.mp4', 'video/mp4'));

    return postForm<Wrapped<Comment>>('/comments', form);
  },

  react: (commentId: number, reactionType = 'love') =>
    post<{ reacted: boolean; reaction_type: string | null; like_count: number }>(
      `/comments/${commentId}/react`,
      { reaction_type: reactionType },
    ),
};

// ── Registry ─────────────────────────────────────────────────────────────────

export const wishes = {
  create: (celebrationId: number, items: { name: string; amount?: string; description?: string; imageUri?: string }[]) => {
    const form = new FormData();
    form.append('celebration_id', String(celebrationId));

    items.forEach((item, i) => {
      form.append(`wishlist[${i}][name]`, item.name);
      if (item.amount) form.append(`wishlist[${i}][amount]`, item.amount);
      if (item.description) form.append(`wishlist[${i}][description]`, item.description);
      if (item.imageUri) form.append(`wishlist[${i}][image]`, fileField(item.imageUri, `wish-${i}.jpg`));
    });

    return postForm<Wrapped<Wish[]>>('/wishes', form);
  },

  destroy: (wishId: number) => del<{ message: string }>(`/wishes/${wishId}`),

  replies: (wishId: number) => get<{ data: Reply[] }>(`/wishes/${wishId}/replies`),

  reply: (wishId: number, message: string, guestName?: string) =>
    post<Reply>(`/wishes/${wishId}/replies`, { message, guest_name: guestName }),

  contributeFromWallet: (wishId: number, body: { amount: number; currency: string; message?: string }) =>
    post<{ message: string; wish: Wrapped<Wish> }>(`/wishes/${wishId}/contribute/wallet`, body),

  payToContribute: (
    wishId: number,
    body: { amount: number; currency: string; message?: string; guest_name?: string; guest_email?: string },
  ) => post<CheckoutIntent>(`/wishes/${wishId}/contribute/pay`, body),

  verifyContribution: (reference: string, sessionId?: string | null) =>
    post<VerifyResult>('/wishes/contribute/verify', { reference, session_id: sessionId }),
};

// ── Gifts ────────────────────────────────────────────────────────────────────

export const gifts = {
  sendFromWallet: (body: { platform_gift_id: number; celebration_id: number; message?: string }) =>
    post<{ message: string; new_balance: number; symbol: string }>('/gifts/send', body),

  pay: (body: {
    platform_gift_id: number;
    celebration_id: number;
    message?: string;
    guest_name?: string;
    guest_email?: string;
  }) => post<CheckoutIntent>('/gifts/pay', body),

  verify: (reference: string, sessionId?: string | null) =>
    post<VerifyResult>('/gifts/verify', { reference, session_id: sessionId }),
};

// ── Money ────────────────────────────────────────────────────────────────────

export const wallet = {
  balances: () => get<WalletBalances>('/wallet'),
  transactions: (page = 1) => get<Paginated<WalletTransaction>>(`/wallet/transactions?page=${page}`),
  fund: (body: { amount: number; wallet_type: 'local' | 'global' }) => post<CheckoutIntent>('/wallet/fund', body),
  verifyFunding: (reference: string, sessionId?: string | null) =>
    post<VerifyResult>('/wallet/fund/verify', { reference, session_id: sessionId }),
};

export const bankAccounts = {
  list: () => get<Wrapped<BankAccount[]>>('/bank-accounts'),
  create: (body: { bank_name: string; account_number: string; account_name: string }) =>
    post<Wrapped<BankAccount>>('/bank-accounts', body),
  update: (id: number, body: { bank_name: string; account_number: string; account_name: string }) =>
    put<Wrapped<BankAccount>>(`/bank-accounts/${id}`, body),
  destroy: (id: number) => del<{ message: string }>(`/bank-accounts/${id}`),
  setDefault: (id: number) => patch<Wrapped<BankAccount>>(`/bank-accounts/${id}/default`),
};

export const withdrawals = {
  list: (page = 1) => get<Paginated<Withdrawal>>(`/withdrawals?page=${page}`),
  create: (body: { amount: number; bank_account_id: number; wallet_type: 'local' | 'global' }) =>
    post<Wrapped<Withdrawal> & { message: string; wallet: { local: number; global: number } }>('/withdrawals', body),
};

export const meta = {
  get: () => get<Meta>('/meta'),
};
