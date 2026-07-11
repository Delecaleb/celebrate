<x-emails.layout subject="Welcome to {{ config('app.name') }}!">

    {{-- Greeting --}}
    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        Welcome, {{ $firstName }}! 🎉
    </p>
    <p style="margin: 0 0 28px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        Your account is ready. Let's make every celebration unforgettable.
    </p>

    {{-- Divider --}}
    <hr style="border: none; border-top: 1px solid #E5E7EB; margin: 0 0 28px 0;">

    {{-- What you can do --}}
    <p style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #374151;">
        Here's what you can do on {{ config('app.name') }}:
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 28px;">
        @foreach ([
            ['🎂', 'Create Celebrations', 'Build beautiful birthday, wedding, graduation & anniversary pages.'],
            ['🎁', 'Send & Receive Gifts', 'Send platform gifts or fund celebration wish lists.'],
            ['📣', 'Invite Guests', 'Share your celebration link and collect wishes, media & reactions.'],
            ['💌', 'Stay Notified', "We'll keep you updated on every gift, wish, and milestone."],
        ] as [$icon, $title, $desc])
        <tr>
            <td style="padding: 10px 0; vertical-align: top; width: 40px; font-size: 22px;">{{ $icon }}</td>
            <td style="padding: 10px 0 10px 12px; vertical-align: top;">
                <p style="margin: 0 0 2px 0; font-size: 14px; font-weight: 600; color: #111827;">{{ $title }}</p>
                <p style="margin: 0; font-size: 13px; color: #6B7280; line-height: 1.5;">{{ $desc }}</p>
            </td>
        </tr>
        @endforeach
    </table>

    {{-- CTA --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <a href="{{ route('celebrations.create') }}"
                   style="display:inline-block; background: linear-gradient(135deg, #7C3AED, #A855F7); color:#ffffff; font-size:15px; font-weight:700; padding:14px 36px; border-radius:8px; text-decoration:none; letter-spacing:0.3px;">
                    Create Your First Celebration
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 28px 0 0 0; font-size: 14px; color: #9CA3AF; text-align: center; line-height: 1.6;">
        Questions? Just reply to this email — we'd love to help.
    </p>

</x-emails.layout>
