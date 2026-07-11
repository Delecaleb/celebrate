<x-emails.layout subject="{{ $daysUntil === 1 ? '🚨 TOMORROW' : "T-{$daysUntil}" }}: {{ $celebration->title }} is almost here!">

    {{-- Countdown hero --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 28px;">
        <tr>
            @if ($daysUntil === 7)
            @php $bgFrom = '#7C3AED'; $bgTo = '#A855F7'; $label = '1 WEEK TO GO'; @endphp
            @elseif ($daysUntil === 3)
            @php $bgFrom = '#EC4899'; $bgTo = '#F472B6'; $label = '3 DAYS TO GO'; @endphp
            @else
            @php $bgFrom = '#EF4444'; $bgTo = '#F97316'; $label = "IT'S TOMORROW!"; @endphp
            @endif
            <td style="background: linear-gradient(135deg, {{ $bgFrom }}, {{ $bgTo }}); border-radius: 12px; padding: 28px; text-align: center;">
                <p style="margin: 0 0 6px 0; font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.75); text-transform: uppercase; letter-spacing: 2px;">{{ $label }}</p>
                @if ($daysUntil > 1)
                <p style="margin: 0; font-size: 64px; font-weight: 900; color: #ffffff; line-height: 1; text-shadow: 0 2px 8px rgba(0,0,0,0.2);">{{ $daysUntil }}</p>
                <p style="margin: 0; font-size: 20px; font-weight: 700; color: rgba(255,255,255,0.9);">DAYS</p>
                @else
                <p style="margin: 0; font-size: 48px; line-height: 1;">🎉</p>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        {{ $celebration->title }} is {{ $daysUntil === 1 ? 'tomorrow' : "in {$daysUntil} days" }}!
    </p>
    <p style="margin: 0 0 28px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        Hi {{ $firstName }}, your celebration is almost here. Here's where things stand and what to do next.
    </p>

    {{-- Stats snapshot --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 28px;">
        <tr>
            <td style="width: 25%; padding: 0 6px 0 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                       style="background-color: #F5F3FF; border-radius: 8px; padding: 14px 10px; text-align: center;">
                    <tr><td>
                        <p style="margin: 0 0 2px 0; font-size: 20px;">🎁</p>
                        <p style="margin: 0; font-size: 18px; font-weight: 800; color: #7C3AED;">{{ $giftCount }}</p>
                        <p style="margin: 0; font-size: 10px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.5px;">Gifts</p>
                    </td></tr>
                </table>
            </td>
            <td style="width: 25%; padding: 0 4px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                       style="background-color: #F0FDF4; border-radius: 8px; padding: 14px 10px; text-align: center;">
                    <tr><td>
                        <p style="margin: 0 0 2px 0; font-size: 20px;">💌</p>
                        <p style="margin: 0; font-size: 18px; font-weight: 800; color: #059669;">{{ $wishCount }}</p>
                        <p style="margin: 0; font-size: 10px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.5px;">Wishes</p>
                    </td></tr>
                </table>
            </td>
            <td style="width: 25%; padding: 0 4px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                       style="background-color: #FFF7ED; border-radius: 8px; padding: 14px 10px; text-align: center;">
                    <tr><td>
                        <p style="margin: 0 0 2px 0; font-size: 20px;">👀</p>
                        <p style="margin: 0; font-size: 18px; font-weight: 800; color: #D97706;">{{ $viewCount }}</p>
                        <p style="margin: 0; font-size: 10px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.5px;">Views</p>
                    </td></tr>
                </table>
            </td>
            <td style="width: 25%; padding: 0 0 0 6px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                       style="background-color: #FFF1F2; border-radius: 8px; padding: 14px 10px; text-align: center;">
                    <tr><td>
                        <p style="margin: 0 0 2px 0; font-size: 20px;">💰</p>
                        <p style="margin: 0; font-size: 18px; font-weight: 800; color: #E11D48;">${{ number_format($totalGifted, 0) }}</p>
                        <p style="margin: 0; font-size: 10px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.5px;">Gifted</p>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Last-minute tips based on days --}}
    @if ($daysUntil === 7)
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #F5F3FF; border-left: 4px solid #7C3AED; border-radius: 0 8px 8px 0; padding: 16px 20px; margin-bottom: 28px;">
        <tr><td>
            <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 700; color: #7C3AED;">7-Day Action List</p>
            <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #4B5563; line-height: 1.8;">
                <li>Send your celebration link to everyone who should attend</li>
                <li>Finalize your wish list items</li>
                <li>Upload a cover photo if you haven't already</li>
                <li>Enable the gifting feature and set gift goals</li>
            </ul>
        </td></tr>
    </table>
    @elseif ($daysUntil === 3)
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #FDF2F8; border-left: 4px solid #EC4899; border-radius: 0 8px 8px 0; padding: 16px 20px; margin-bottom: 28px;">
        <tr><td>
            <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 700; color: #EC4899;">3-Day Final Countdown</p>
            <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #4B5563; line-height: 1.8;">
                <li>Send a final reminder to any guests who haven't visited</li>
                <li>Check your wallet and available gift balance</li>
                <li>Confirm the venue and event details are correct</li>
                <li>Add a personal message to your celebration page</li>
            </ul>
        </td></tr>
    </table>
    @else
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #FFF1F2; border-left: 4px solid #EF4444; border-radius: 0 8px 8px 0; padding: 16px 20px; margin-bottom: 28px;">
        <tr><td>
            <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 700; color: #EF4444;">Last Minute Checklist</p>
            <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #4B5563; line-height: 1.8;">
                <li>Check all wishes received — respond to your favourites</li>
                <li>Screenshot or save any messages you want to keep</li>
                <li>Your wallet balance is ready to use</li>
                <li>Enjoy every moment — you've earned this! 🎉</li>
            </ul>
        </td></tr>
    </table>
    @endif

    {{-- CTA --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <a href="{{ route('celebrations.show', $celebration->slug) }}"
                   style="display:inline-block; background: linear-gradient(135deg, #7C3AED, #A855F7); color:#ffffff; font-size:15px; font-weight:700; padding:14px 36px; border-radius:8px; text-decoration:none;">
                    View My Celebration
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 20px 0 0 0; font-size: 14px; color: #9CA3AF; text-align: center;">
        Can't wait to celebrate with you! 🥳
    </p>

</x-emails.layout>
