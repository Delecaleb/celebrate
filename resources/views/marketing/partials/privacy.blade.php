{{--
    Privacy policy.

    Specific to what this app actually collects and who it actually shares with
    — Paystack, Stripe, the mail provider, the host. Company details come from
    config('seo.legal').

    A starting point drafted by the team, not legal advice. Have it reviewed
    against the NDPR before launch.
--}}

@php $legal = config('seo.legal'); @endphp

<section class="page-head">
    <div class="pattern pattern-dots pattern-fade"></div>

    <div class="wrap sec-inner">
        <h1 class="h-display">Privacy <span class="t-accent">policy</span></h1>
        <p class="lead">
            What we collect, why we need it, and who else ever sees it. Short version: your
            celebration is yours, and we have never sold anybody's data.
        </p>
        <p class="legal-date">Effective {{ $legal['effective'] }}</p>
    </div>
</section>

<section class="sec">
    <div class="wrap legal">

        <h2>1. Who is responsible</h2>
        <p>
            {{ $legal['entity'] }}@if ($legal['rc_number']) (RC {{ $legal['rc_number'] }})@endif,
            of {{ $legal['address'] }}, is the data controller for CelebrateMi. Questions,
            requests and complaints go to
            <a href="mailto:{{ $legal['privacy_email'] }}">{{ $legal['privacy_email'] }}</a>.
        </p>

        <h2>2. What we collect</h2>
        <ul>
            <li>
                <strong>Your account</strong> — name, email address, password (hashed, never
                readable by us), and optionally a phone number, photo, country and date of birth.
            </li>
            <li>
                <strong>Your celebration</strong> — the page title, the celebrant's name, dates,
                photos and videos you upload, and the wishes your guests leave.
            </li>
            <li>
                <strong>Guests</strong> — the name and email a guest gives when they send a gift,
                so we can send them a receipt. A guest does not need an account.
            </li>
            <li>
                <strong>Payouts</strong> — your bank name, account number and the account holder
                name your bank returns when we verify it.
            </li>
            <li>
                <strong>Technical</strong> — IP address, browser, and approximate country from
                your IP (we use ipinfo.io for this) so we can show prices in a sensible currency.
            </li>
        </ul>
        <p>
            <strong>We never see or store card numbers.</strong> Card details are entered on
            Paystack's or Stripe's own pages and never touch our servers.
        </p>

        <h2>3. Why we use it</h2>
        <ul>
            <li>To run your celebration page and show it to the people you share it with — this is us performing our contract with you.</li>
            <li>To take gifts, credit your wallet and pay you out — contract, and our legal duty to keep financial records.</li>
            <li>To email you about your own celebrations: gifts received, reminders, countdowns. You can turn these off in your settings.</li>
            <li>To keep the service safe — spotting fraud and abuse — which is our legitimate interest.</li>
            <li>To meet anti-money-laundering and tax obligations where they apply to us.</li>
        </ul>

        <h2>4. Who else sees it</h2>
        <ul>
            <li><strong>Paystack</strong> and <strong>Stripe</strong> — to take payments and verify your bank account.</li>
            <li><strong>Our email provider</strong> — to deliver the emails listed above.</li>
            <li><strong>Our hosting provider</strong> — which stores the data on our behalf.</li>
            <li><strong>Anyone with your page link</strong> — a public page is public. You choose whether a page is public or unlisted, and whether gift amounts are shown.</li>
            <li><strong>Authorities</strong> — only where we are legally required to, and we will tell you unless we are forbidden from doing so.</li>
        </ul>
        <p>We do not sell personal data, and we do not share it for anyone else's advertising.</p>

        <h2>5. Cookies</h2>
        <p>
            We use a session cookie to keep you signed in and a security cookie to protect forms
            from cross-site attacks. Both are strictly necessary — we do not run advertising or
            third-party tracking cookies.
        </p>

        <h2>6. How long we keep it</h2>
        <p>
            Celebration pages are designed to last: we keep them until you delete them or close
            your account. Financial records are kept for as long as the law requires, which is
            currently six years in Nigeria. Deleting your account removes your pages and personal
            details; anonymised transaction records may remain where we must keep them.
        </p>

        <h2>7. Your rights</h2>
        <p>
            Under the Nigeria Data Protection Act — and the GDPR if you are in the UK or EU — you
            can ask us to show you the data we hold, correct it, delete it, hand it over in a
            portable form, or stop a particular use of it. Email
            <a href="mailto:{{ $legal['privacy_email'] }}">{{ $legal['privacy_email'] }}</a> and
            we will respond within 30 days. You can also complain to the Nigeria Data Protection
            Commission, or your local supervisory authority.
        </p>

        <h2>8. Children</h2>
        <p>
            You must be 18 to hold an account. A page may of course celebrate a child — a naming
            ceremony, a birthday — and where it does, the adult who created it is responsible for
            what is published about them. Tell us if you believe a child's data is on the site
            without a parent's agreement and we will remove it.
        </p>

        <h2>9. Security</h2>
        <p>
            Traffic is encrypted in transit, passwords are hashed, payout accounts are verified
            with the bank before we will pay into them, and access to production data is limited
            to the people who need it. No system is perfect: if a breach ever affects your data,
            we will tell you and the regulator within 72 hours of finding out.
        </p>

        <h2>10. Where your data lives</h2>
        <p>
            Our servers and our providers may store data outside Nigeria. Where they do, we rely
            on the safeguards in the Nigeria Data Protection Act and, where relevant, standard
            contractual clauses.
        </p>

        <h2>11. Changes</h2>
        <p>
            If we change this policy in a way that affects you, we will email you before it takes
            effect. The date at the top always tells you which version you are reading.
        </p>

        <p class="legal-foot">
            See also our <a href="{{ route('terms') }}" data-nav>terms of service</a>.
        </p>
    </div>
</section>
