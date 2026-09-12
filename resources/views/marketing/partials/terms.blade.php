{{--
    Terms of service.

    Written for this product specifically — money held in a wallet, gifts from
    guests who never sign up, pages that stay up for good. The company details
    come from config('seo.legal'); fill those in before launch.

    This is a starting point drafted by the team, not legal advice. Have a
    Nigerian lawyer review it before you take live payments.
--}}

@php $legal = config('seo.legal'); @endphp

<section class="page-head">
    <div class="pattern pattern-dots pattern-fade"></div>

    <div class="wrap sec-inner">
        <h1 class="h-display">Terms of <span class="t-accent">service</span></h1>
        <p class="lead">
            The agreement between you and {{ $legal['entity'] }} when you use CelebrateMi.
            Plain language, no surprises.
        </p>
        <p class="legal-date">Effective {{ $legal['effective'] }}</p>
    </div>
</section>

<section class="sec">
    <div class="wrap legal">

        <h2>1. Who we are</h2>
        <p>
            CelebrateMi is operated by {{ $legal['entity'] }}@if ($legal['rc_number']) (RC {{ $legal['rc_number'] }})@endif,
            of {{ $legal['address'] }}. In these terms "we", "us" and "CelebrateMi" mean that
            company, and "you" means the person using the service. Reach us any time at
            <a href="mailto:{{ $legal['email'] }}">{{ $legal['email'] }}</a>.
        </p>

        <h2>2. What CelebrateMi does</h2>
        <p>
            We give you a page for a celebration. People you share the link with can leave
            wishes, upload photos and videos, and send you cash gifts. The money they send is
            collected by our payment partners, held in your CelebrateMi wallet, and paid out to
            a bank account you nominate.
        </p>
        <p>
            We are not a bank and we do not offer banking, investment or credit services. Your
            wallet balance is money owed to you, held with our payment partners — it earns no
            interest and is not a deposit account.
        </p>

        <h2>3. Your account</h2>
        <ul>
            <li>You must be at least 18 to create an account and receive gifts.</li>
            <li>The details you give us — your name, your email, your bank account — must be true and yours.</li>
            <li>You are responsible for what happens under your account, so keep your password to yourself.</li>
            <li>Tell us immediately at <a href="mailto:{{ $legal['email'] }}">{{ $legal['email'] }}</a> if you think someone else has got in.</li>
        </ul>

        <h2>4. Your celebration page</h2>
        <p>
            You decide what goes on your page and you are responsible for it. Guests can post to
            it, and you can remove anything they post. Do not use a page to:
        </p>
        <ul>
            <li>impersonate someone, or celebrate a person who has asked you not to;</li>
            <li>raise money for something other than what the page says;</li>
            <li>post anything unlawful, hateful, sexual involving minors, or that infringes someone else's rights;</li>
            <li>publish another person's private information without their agreement.</li>
        </ul>
        <p>
            We can remove content or close a page that breaks these rules, and we will tell you
            why when we do.
        </p>

        <h2>5. Gifts, fees and your wallet</h2>
        <ul>
            <li><strong>Creating a page is free.</strong> There is no subscription and no card required to start.</li>
            <li>
                <strong>We take {{ $legal['gift_fee_percent'] }}% of cash gifts you receive.</strong>
                It is deducted as the gift arrives, so the balance you see is the balance you can
                withdraw. Our payment partners' processing charges are included in that figure.
            </li>
            <li>
                Gifts are paid in the currency your guest chose. Where a conversion is needed we
                use the rate in force at the time, which we show you before you withdraw.
            </li>
            <li>
                Withdrawals go to a bank account in your name that we have verified with your
                bank. We may hold a withdrawal while we check it, and we will tell you if we do.
            </li>
        </ul>

        <h2>6. Refunds, disputes and chargebacks</h2>
        <p>
            A gift is a gift: once it has been sent and credited, it is the celebrant's money and
            we cannot reverse it on request. If a guest believes a payment was fraudulent or made
            in error, they should contact us within 30 days at
            <a href="mailto:{{ $legal['email'] }}">{{ $legal['email'] }}</a> and we will
            investigate with our payment partner.
        </p>
        <p>
            If a card issuer reverses a payment after we have credited it, we may recover that
            amount from your wallet, or from you directly if the balance has already been
            withdrawn. Repeated chargebacks against a page can lead us to suspend payouts on it.
        </p>

        <h2>7. Your content stays yours</h2>
        <p>
            You keep ownership of everything you and your guests put on a page. You give us
            permission to host it, display it to whoever has the link, and reproduce it in the
            photobook we generate for you — nothing else. We do not sell it, and we do not use it
            in advertising without asking you first.
        </p>

        <h2>8. Pages stay up</h2>
        <p>
            A celebration page does not expire. We intend to keep it available for as long as we
            operate the service, which is the point of it. You can delete your page or your whole
            account at any time from your dashboard, and doing so removes it for everyone.
        </p>
        <p>
            If we ever have to close the service, we will give you at least 60 days' notice, a way
            to export everything on your pages, and the chance to withdraw any remaining balance.
        </p>

        <h2>9. When we can suspend or close an account</h2>
        <p>
            We may suspend or close an account that breaks these terms, is being used for fraud,
            or that we are required to act on by law or by our payment partners. Except where the
            law prevents us, we will tell you why, and any balance that is rightfully yours will
            be paid out.
        </p>

        <h2>10. What we are not responsible for</h2>
        <p>
            We provide CelebrateMi as it is. We do not promise the service will never be
            unavailable, and we are not responsible for what your guests write, for a bank's delay
            in processing a payout, or for losses that were not a foreseeable result of us
            breaking these terms. Nothing here limits our liability for fraud, death or personal
            injury caused by us, or anything else the law does not allow us to limit.
        </p>

        <h2>11. Changes</h2>
        <p>
            We may update these terms. If a change materially affects you — fees, payouts, how
            long pages stay up — we will email you at least 30 days before it takes effect. Using
            the service after that means you accept the new terms.
        </p>

        <h2>12. Law</h2>
        <p>
            These terms are governed by the laws of {{ $legal['jurisdiction'] }}, and its courts
            have jurisdiction over any dispute. We would much rather sort things out by email
            first: <a href="mailto:{{ $legal['email'] }}">{{ $legal['email'] }}</a>.
        </p>

        <p class="legal-foot">
            See also our <a href="{{ route('privacy') }}" data-nav>privacy policy</a> and
            <a href="{{ route('pricing') }}" data-nav>pricing</a>.
        </p>
    </div>
</section>
