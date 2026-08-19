/**
 * Response shapes.
 *
 * These mirror app/Http/Resources/*.php field for field. If you change one of
 * those, change the matching type here.
 */

/** Laravel wraps a single resource in `data`. */
export type Wrapped<T> = { data: T };

/** A paginated collection, as Laravel's ResourceCollection emits it. */
export type Paginated<T> = {
  data: T[];
  links?: { first?: string; last?: string; prev?: string | null; next?: string | null };
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    unread_count?: number;
  };
};

export type User = {
  id: number;
  uuid: string | null;
  first_name: string | null;
  last_name: string | null;
  name: string | null;
  username: string | null;
  email: string;
  phone: string | null;
  bio: string | null;
  profile_photo: string | null;
  country: string | null;
  state: string | null;
  city: string | null;
  account_type: string | null;
  currency: string | null;
  email_verified: boolean;
  created_at: string | null;
};

export type Celebration = {
  id: number;
  uuid: string | null;
  title: string;
  slug: string;
  celebration_type: string | null;
  celebrant_name: string | null;
  description: string | null;
  venue: string | null;
  event_date: string | null;
  start_date: string | null;
  end_date: string | null;
  display_date: string | null;
  status: string | null;
  is_public: boolean;
  published_at: string | null;
  allow_wishes: boolean;
  allow_gifts: boolean;
  allow_media_uploads: boolean;
  allow_guest_posts: boolean;
  cover_photos: string[];
  cover_photo: string | null;
  theme_color: string | null;
  font_style: string | null;
  custom_bg: string | null;
  custom_text: string | null;
  view_count: number;
  share_count: number;
  comment_count: number;
  gifts_count?: number;
  wishes_count?: number;
  owner?: User;
  web_url: string;
  created_at: string | null;
};

export type Wish = {
  id: number;
  name: string;
  description: string | null;
  wish_type: string | null;
  wish_image: string | null;
  wish_link: string | null;
  display_target: number;
  display_current: number;
  percent_funded: number;
  is_funded: boolean;
  base_currency: string | null;
  converted_currency: string | null;
  currency: string | null;
  priority_level: string | null;
  status: string | null;
  allow_partial_contribution: boolean;
  contribution_count: number;
  celebration_id: number;
  created_at: string | null;
};

export type Comment = {
  id: number;
  message: string | null;
  author: string;
  author_photo: string | null;
  is_guest: boolean;
  media_type: string | null;
  media_url: string | null;
  is_pinned: boolean;
  like_count: number;
  reply_count: number;
  created_at: string | null;
  created_at_human: string | null;
};

export type Reply = {
  id: number;
  message: string;
  author: string;
  created_at: string | null;
};

export type PlatformGift = {
  id: number;
  name: string;
  description: string | null;
  price_usd: number;
  display_price: number;
  image: string | null;
  link: string | null;
};

export type Supporter = {
  name: string;
  count: number;
  total: number;
  when: string | null;
};

export type Template = {
  id: number;
  name: string;
  slug: string | null;
  icon: string | null;
  description: string | null;
  page_bg: string | null;
  card_bg: string | null;
  text_primary: string | null;
  text_secondary: string | null;
  accent_color: string | null;
  photo_border_style: string | null;
  photo_border_color: string | null;
  photo_border_width: number;
  wishes_layout: string | null;
  sort_order: number;
};

export type Frame = {
  id: number;
  name: string;
  type: string | null;
  svg_content: string | null;
  preview_image: string | null;
};

export type BankAccount = {
  id: number;
  bank_name: string;
  account_number: string;
  masked_number: string;
  account_name: string;
  is_default: boolean;
  is_verified: boolean;
  created_at: string | null;
};

export type Withdrawal = {
  id: number;
  amount: number;
  currency: string;
  status: string;
  reference: string;
  note: string | null;
  wallet_type: string | null;
  bank_name: string;
  bank_account_name: string;
  bank_account_number: string;
  masked_number: string;
  processed_at: string | null;
  created_at: string | null;
};

export type WalletTransaction = {
  id: number;
  type: 'credit' | 'debit';
  amount: number;
  currency: string;
  status: string;
  description: string;
  reference: string | null;
  created_at: string | null;
};

export type Notification = {
  id: number;
  type: string | null;
  title: string | null;
  message: string | null;
  data: unknown;
  is_read: boolean;
  created_at: string | null;
  created_at_human: string | null;
};

export type WalletBalances = {
  currency: string;
  symbol: string;
  local: number;
  global: number;
};

export type DashboardSummary = {
  greeting: string;
  user: { first_name: string | null; name: string };
  stats: { total: number; views: number; messages: number; published: number };
  wallet: WalletBalances;
  unread_count: number;
};

export type Countdown = { days: number; hours: number; mins: number };

/** The whole celebration page, one key per tab. */
export type CelebrationPage = {
  celebration: Wrapped<Celebration>;
  is_owner: boolean;
  is_authenticated: boolean;
  currency: { code: string; symbol: string };
  totals: { raised: number; wishes: number; comments: number };
  wallet_balance: number;
  countdown: Countdown | null;
  comments: Wrapped<Comment[]>;
  wishes: Wrapped<Wish[]>;
  platform_gifts: Wrapped<PlatformGift[]>;
  supporters: Supporter[];
  template: Wrapped<Template> | null;
  frame: Wrapped<Frame> | null;
  templates: Wrapped<Template[]> | never[];
  frames: Wrapped<Frame[]> | never[];
};

export type AuthResponse = { token: string; user: Wrapped<User> };

/** What /fund and /pay hand back for the app to open. */
export type CheckoutIntent = {
  provider: 'stripe' | 'paystack';
  reference: string;
  authorization_url: string;
};

export type VerifyResult = {
  status: 'credited' | 'already_credited' | 'unconfirmed' | 'not_found';
  message?: string;
  wallet?: WalletBalances;
  wish?: Wrapped<Wish>;
};

export type Meta = {
  base_currency: string;
  currencies: { code: string; symbol: string; name: string }[];
  celebration_types: { value: string; label: string }[];
};
