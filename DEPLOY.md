# Deploying CelebrateMi

Everything that has to be true on the server for the app to work, in the order
you need it. Written for a single Ubuntu box with nginx, PHP 8.3+ and MySQL;
adapt paths for anything else.

---

## 1. First deploy

```bash
git clone <repo> /var/www/celebratemi && cd /var/www/celebratemi

cp .env.production.example .env      # then fill in every CHANGE-ME
php artisan key:generate --force

composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force
php artisan db:seed --force          # templates, frames, and the 30 gifts
php artisan storage:link             # without this every uploaded photo 404s

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 2. Every deploy after that

```bash
php artisan down --render=errors::503

git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

php artisan queue:restart            # workers run old code until told otherwise
php artisan up
```

> `config:cache` means `.env` is no longer read at runtime. Any change to `.env`
> needs `php artisan config:cache` again, or it will not take effect.

---

## 3. The two processes that must always be running

Neither is optional. Without cron no email is ever delivered — password resets
included — and nothing is reconciled or reminded. The queue worker handles
everything else that runs in the background.

**Queue worker** — `/etc/supervisor/conf.d/celebratemi-worker.conf`:

```ini
[program:celebratemi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/celebratemi/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/celebratemi/storage/logs/worker.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start celebratemi-worker:*
```

**Scheduler** — one crontab line as `www-data`:

```cron
* * * * * cd /var/www/celebratemi && php artisan schedule:run >> /dev/null 2>&1
```

That one line drives:

| Command | When | What breaks without it |
|---|---|---|
| `payments:reconcile` | every 10 min | payments left pending by a missed webhook are never settled |
| `mail:celebration-reminders` | 08:00 daily | guests are not reminded |
| `mail:celebration-countdowns` | 09:00 daily | owners are not reminded |
| `mail:event-reports` | daily 11:00 | celebrants never hear how their day went |
| `emails:send` | every minute | **nothing is emailed at all** — see §9 |

---

## 4. Payment webhooks — register these

The browser returning from a payment is a courtesy; the webhook is the only
notice we can rely on. Register both and confirm a test delivery reaches a 200.

| Gateway | URL | Event |
|---|---|---|
| Paystack | `https://celebratemi.com/webhooks/paystack` | `charge.success` |
| Stripe | `https://celebratemi.com/webhooks/stripe` | `checkout.session.completed` |

Stripe's endpoint has its own signing secret — copy it into
`STRIPE_WEBHOOK_SECRET`. Paystack signs with the secret key you already have.

Check they are working:

```bash
php artisan payments:reconcile --dry-run     # should list nothing on a healthy day
tail -f storage/logs/laravel.log | grep -i webhook
```

---

## 5. nginx

```nginx
server {
    listen 80;
    server_name celebratemi.com www.celebratemi.com;
    return 301 https://celebratemi.com$request_uri;   # one canonical host
}

server {
    listen 443 ssl http2;
    server_name www.celebratemi.com;
    return 301 https://celebratemi.com$request_uri;
    # certificates as below
}

server {
    listen 443 ssl http2;
    server_name celebratemi.com;
    root /var/www/celebratemi/public;

    ssl_certificate     /etc/letsencrypt/live/celebratemi.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/celebratemi.com/privkey.pem;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header X-Frame-Options "SAMEORIGIN" always;

    index index.php;
    charset utf-8;

    client_max_body_size 25M;          # video wishes are capped at 20M

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

`APP_URL` must match the host that survives these redirects, or canonical tags
will point at a URL that immediately redirects.

---

## 6. Backups

Money records live here, so this is not optional either.

```cron
# 02:00 daily, keep 30 days
0 2 * * * mysqldump -u backup -p'…' celebrate | gzip > /var/backups/celebrate-$(date +\%F).sql.gz
5 2 * * * find /var/backups -name 'celebrate-*.sql.gz' -mtime +30 -delete
```

Also back up `storage/app/public` — that is every photo anyone uploaded.
**Restore-test it once before launch.** A backup nobody has restored is a guess.

---

## 7. Before the first real payment

- [ ] `APP_DEBUG=false` and `APP_ENV=production` confirmed on the live box
- [ ] `php artisan about` shows the right database, cache and queue drivers
- [ ] A password reset email actually arrives
- [ ] A test gift of the smallest allowed amount completes, and appears in the wallet
- [ ] The same test gift's webhook is visible in the gateway dashboard as a 200
- [ ] Closing the tab mid-payment still credits, within ten minutes, via `payments:reconcile`
- [ ] A withdrawal request appears in the admin panel and marks completed correctly
- [ ] `/terms` and `/privacy` have no CHANGE-ME left in them
- [ ] `https://celebratemi.com/sitemap.xml` and `/robots.txt` both load
- [ ] Search Console and Bing verified, sitemap submitted

---

## 8. The admin panel

Admins live in their own `admins` table behind their own guard — a customer
account can never sign in here, whatever it holds. There is no sign-up, so the
first account is made from the server shell:

```bash
php artisan admin:create                       # prompts; the first one is always a super admin
php artisan admin:create --list                # the permission catalogue
php artisan admin:create ada@celebratemi.com --name="Ada" --permissions=payments.view,users.view
php artisan admin:create ada@celebratemi.com --super
php artisan admin:create ada@celebratemi.com --suspend
```

After that, super admins add staff from **Staff** in the panel and tick exactly
what each person may reach. Everything is refused at the route, not merely
hidden from the sidebar.

| Permission | What it opens |
|---|---|
| `users.view` | The user list, their celebrations and balances |
| `users.impersonate` | Sign in as a customer to see what they see |
| `users.suspend` | Block or restore a customer account |
| `events.view` | Every celebration and its registry |
| `payments.view` | Every gift, contribution and top-up, at any status |
| `payments.revalidate` | Re-check a pending payment with the gateway |
| `withdrawals.view` / `withdrawals.process` | See payouts / approve or reject them |
| `gifts.manage` | The gift catalogue — add gifts, upload artwork, set prices, publish or pause |
| `frames.manage` | Photo frames |
| `settings.manage` | Gateway keys, mail, ipinfo, and the currency list |
| `admins.manage` | Add staff and set their access (super admins always) |

Sign in at `https://celebratemi.com/admin/login` — five failed attempts per
email and IP locks that pair out for five minutes, and every sign-in, failure
and impersonation is written to the log.

**Impersonation.** `users.impersonate` lets an admin open a customer's account
exactly as its owner sees it. While that is happening: a red banner sits across
every page, withdrawals, wallet funding and bank-account changes are refused by
middleware, and both the start and the stop are written to that customer's
activity log with the admin named. Signing out of the panel ends the borrowed
session too.

The last active super admin cannot be demoted, suspended or deleted — by the
panel or the console.

**Audit log** (`/admin/audit`, super admins only). Every impersonation, staff
change, withdrawal decision and payment re-check is recorded with the admin's
name, what they did, and the IP it came from. It is deliberately not a
grantable permission: the log records whoever is reading it, so it stays with
whoever runs the platform.

Entries are append-only and survive the admin account being deleted — the email
is copied onto the row. Customers never see them; an admin opening an account
is a matter for the platform's owner, not something to put in the customer's
own activity feed.

## 9. Mail — the outbox

Nothing is sent inline. Every email is written to the `email_queues` table the
moment something decides to send it — subject and body already rendered — and
posted by `emails:send`, which the scheduler runs every minute. So the only
process mail depends on is the cron entry in §3 that the app already needs.

```bash
php artisan emails:send            # send what is waiting, now
php artisan emails:send --limit=200
php artisan emails:send --id=42    # one specific email, ignoring its schedule
```

**Admin → Outbox** lists every email with its status, and opening one shows the
rendered message exactly as the recipient sees it, plus the server's own reply
if it was refused. That is the answer to "I never got my receipt": you can see
whether it was written, whether it went, and what went wrong.

A refused email is retried after 1, then 10, then 60 minutes, and gives up
after three attempts. Failed ones stay in the table until somebody retries them
by hand — a bad address does not fix itself, and deleting the evidence helps
nobody. **Hold** stops one that should never have been written.

Two things worth knowing:

- The body is rendered when the email is *queued*, not when it is sent. A
  template that cannot render therefore fails in front of whoever caused it,
  and the stored copy is exactly what was delivered rather than a guess. The
  flip side is that fixing a template does not change mail already queued.
- Admin → Settings → Email tests the credentials by sending immediately, which
  proves the mailbox works and nothing about whether mail is moving. If the
  outbox is stalled, that page says so in red — believe the red.

## 10. Credentials and currencies from the panel

`settings.manage` opens **Settings** and **Currencies**. Anything set there
wins over `.env`; anything left blank falls through to `.env` and then to the
framework default. So `.env` is still the boot floor — the panel is how you
rotate a key at 2am without a deploy.

- **Settings → Payments / Email / Location.** Each field shows where its
  current value came from: *panel*, *env*, or *unset*. Secrets are encrypted
  with `APP_KEY` and are never rendered back — you see `sk_l••••••••4f2c`, and
  an empty secret box on save means *leave it alone*, not *erase it*. To
  actually remove one, tick **Clear** beside it and the key reverts to `.env`.
  **Test** on each group makes a real call: Paystack and Stripe authenticate,
  mail sends to the signed-in admin, ipinfo resolves an address.
- **Currencies.** Adding one is not cosmetic. It gives every gift a price
  field in that currency on the gift form, gives signups from its countries a
  wallet in it, and adds its fallback rate to the conversion table. *Countries*
  is what the signup IP lookup matches — codes or names, comma separated
  (`gh, ghana`). Leave a gift's field blank and its base price is converted
  instead.
- The base currency (`base` in `config/currency.php`, USD) cannot be
  deactivated or deleted; every stored price is denominated in it. A currency any account or gift price
  references is switched **off** rather than deleted, so nothing already priced
  or held in it becomes unreadable.

The overlay is cached for an hour and cleared on every write, so a change is
live immediately. Changing a field writes to the audit log by **name only** —
never the value.

Because these live in the database, `php artisan config:cache` does not freeze
them, and a value rotated in the panel does not need a redeploy. `APP_KEY` does
still matter: change it and every stored secret becomes unreadable and has to
be re-entered.

## 11. Known manual step

Withdrawals are **bookkeeping only**. `Admin → Withdrawals → Approve` marks a
payout complete; it does not move money. Someone has to make the bank transfer
by hand and then click approve. If that is not what you want, the Paystack
Transfer API is the piece to build next — it needs a Paystack business account
with transfers enabled.
