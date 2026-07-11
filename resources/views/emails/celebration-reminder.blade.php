<x-emails.layout subject="Reminder: {{ $celebration->title }} is in {{ $daysUntil }} {{ Str::plural('day', $daysUntil) }}!">

    {{-- Hero countdown badge --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background: linear-gradient(135deg, #7C3AED, #A855F7); border-radius: 10px; padding: 20px; text-align: center; margin-bottom: 28px;">
        <tr>
            <td>
                <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 600; color: #DDD6FE; text-transform: uppercase; letter-spacing: 1px;">Coming Up</p>
                <p style="margin: 0; font-size: 52px; font-weight: 900; color: #ffffff; line-height: 1;">{{ $daysUntil }}</p>
                <p style="margin: 0; font-size: 16px; font-weight: 600; color: #EDE9FE;">{{ Str::upper(Str::plural('day', $daysUntil)) }}</p>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        Don't forget — {{ $celebration->title }}! 🎉
    </p>
    <p style="margin: 0 0 28px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        Hi {{ $recipientName }}, this is a friendly reminder that
        <strong>{{ $celebration->title }}</strong> is coming up in <strong>{{ $daysUntil }} {{ Str::plural('day', $daysUntil) }}</strong>.
    </p>

    {{-- Event details card --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 10px; padding: 20px 24px; margin-bottom: 28px;">
        <tr>
            <td>
                <p style="margin: 0 0 14px 0; font-size: 13px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.6px;">Event Details</p>
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="width: 110px; padding: 5px 0; font-size: 13px; color: #9CA3AF;">Type</td>
                        <td style="padding: 5px 0; font-size: 13px; font-weight: 600; color: #111827;">
                            {{ ucfirst(str_replace('_', ' ', $celebration->celebration_type)) }}
                        </td>
                    </tr>
                    @if ($celebration->event_date)
                    <tr>
                        <td style="width: 110px; padding: 5px 0; font-size: 13px; color: #9CA3AF;">Date</td>
                        <td style="padding: 5px 0; font-size: 13px; font-weight: 600; color: #111827;">
                            {{ $celebration->event_date->format('l, F j, Y') }}
                        </td>
                    </tr>
                    @endif
                    @if ($celebration->venue)
                    <tr>
                        <td style="width: 110px; padding: 5px 0; font-size: 13px; color: #9CA3AF;">Venue</td>
                        <td style="padding: 5px 0; font-size: 13px; font-weight: 600; color: #111827;">{{ $celebration->venue }}</td>
                    </tr>
                    @endif
                    @if ($celebration->celebrant_name)
                    <tr>
                        <td style="width: 110px; padding: 5px 0; font-size: 13px; color: #9CA3AF;">Celebrant</td>
                        <td style="padding: 5px 0; font-size: 13px; font-weight: 600; color: #111827;">{{ $celebration->celebrant_name }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Checklist for owner vs guest --}}
    @if ($isOwner)
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #FFFBEB; border-left: 4px solid #F59E0B; border-radius: 0 8px 8px 0; padding: 16px 20px; margin-bottom: 28px;">
        <tr>
            <td>
                <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 700; color: #B45309;">Pre-event checklist</p>
                <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #6B7280; line-height: 1.8;">
                    <li>Share your celebration link with guests</li>
                    <li>Enable wishes and gifting if you haven't already</li>
                    <li>Add a cover photo and intro video</li>
                    <li>Check your wallet balance for received gifts</li>
                    <li>Respond to any guest wishes</li>
                </ul>
            </td>
        </tr>
    </table>
    @endif

    {{-- CTA --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <a href="{{ route('celebrations.show', $celebration->slug) }}"
                   style="display:inline-block; background: linear-gradient(135deg, #7C3AED, #A855F7); color:#ffffff; font-size:15px; font-weight:700; padding:14px 36px; border-radius:8px; text-decoration:none;">
                    {{ $isOwner ? 'View My Celebration' : 'View Celebration Page' }}
                </a>
            </td>
        </tr>
    </table>

    @if (!$isOwner)
    <p style="margin: 20px 0 0 0; font-size: 13px; color: #9CA3AF; text-align: center;">
        Want to send a gift or wish? Visit the celebration page above.
    </p>
    @endif

</x-emails.layout>
