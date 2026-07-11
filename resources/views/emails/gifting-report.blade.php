<x-emails.layout subject="Your {{ $periodLabel }} Gifting Report">

    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        Your {{ $periodLabel }} Gift Report 📊
    </p>
    <p style="margin: 0 0 28px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        Hi {{ $firstName }}, here's a summary of all gifting activity across your celebrations
        for <strong>{{ $periodLabel }}</strong>.
    </p>

    {{-- Summary stats --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 28px;">
        <tr>
            <td style="width: 33%; padding: 0 8px 0 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                       style="background: linear-gradient(135deg, #F5F3FF, #EDE9FE); border-radius: 10px; padding: 20px 16px; text-align: center;">
                    <tr><td>
                        <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 600; color: #7C3AED; text-transform: uppercase; letter-spacing: 0.8px;">Total Received</p>
                        <p style="margin: 0; font-size: 22px; font-weight: 800; color: #4C1D95;">${{ number_format($totalReceived, 2) }}</p>
                    </td></tr>
                </table>
            </td>
            <td style="width: 33%; padding: 0 4px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                       style="background: linear-gradient(135deg, #F0FDF4, #DCFCE7); border-radius: 10px; padding: 20px 16px; text-align: center;">
                    <tr><td>
                        <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 600; color: #059669; text-transform: uppercase; letter-spacing: 0.8px;">Gifts Received</p>
                        <p style="margin: 0; font-size: 22px; font-weight: 800; color: #065F46;">{{ $totalGiftCount }}</p>
                    </td></tr>
                </table>
            </td>
            <td style="width: 33%; padding: 0 0 0 8px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                       style="background: linear-gradient(135deg, #FFF7ED, #FFEDD5); border-radius: 10px; padding: 20px 16px; text-align: center;">
                    <tr><td>
                        <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 600; color: #D97706; text-transform: uppercase; letter-spacing: 0.8px;">Celebrations</p>
                        <p style="margin: 0; font-size: 22px; font-weight: 800; color: #92400E;">{{ $celebrationCount }}</p>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Breakdown by celebration --}}
    @if ($celebrationBreakdown->isNotEmpty())
    <p style="margin: 0 0 14px 0; font-size: 15px; font-weight: 600; color: #374151;">Breakdown by Celebration</p>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="border: 1px solid #E5E7EB; border-radius: 8px; overflow: hidden; margin-bottom: 28px;">
        <tr style="background-color: #F9FAFB;">
            <td style="padding: 10px 16px; font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">Celebration</td>
            <td style="padding: 10px 16px; font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; text-align: center;">Gifts</td>
            <td style="padding: 10px 16px; font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Total</td>
        </tr>
        @foreach ($celebrationBreakdown as $row)
        <tr style="border-top: 1px solid #E5E7EB;">
            <td style="padding: 12px 16px; font-size: 13px; color: #111827; font-weight: 500;">
                {{ $row['title'] }}
                <span style="font-size: 11px; color: #9CA3AF; display: block; margin-top: 2px;">{{ ucfirst($row['type']) }}</span>
            </td>
            <td style="padding: 12px 16px; font-size: 13px; color: #374151; text-align: center;">{{ $row['count'] }}</td>
            <td style="padding: 12px 16px; font-size: 13px; font-weight: 700; color: #7C3AED; text-align: right;">${{ number_format($row['total'], 2) }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    {{-- Gifters shoutout --}}
    @if ($topGifters->isNotEmpty())
    <p style="margin: 0 0 14px 0; font-size: 15px; font-weight: 600; color: #374151;">Top Gifters This Period ❤️</p>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 28px;">
        @foreach ($topGifters as $gifter)
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #F3F4F6;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="width: 32px; height: 32px; background: linear-gradient(135deg, #7C3AED, #A855F7); border-radius: 50%; text-align: center; line-height: 32px; font-size: 12px; font-weight: 700; color: #fff;">
                            {{ strtoupper(substr($gifter['name'], 0, 1)) }}
                        </td>
                        <td style="padding-left: 12px; font-size: 13px; font-weight: 600; color: #111827;">{{ $gifter['name'] }}</td>
                        <td style="text-align: right; font-size: 13px; font-weight: 700; color: #7C3AED;">${{ number_format($gifter['total'], 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        @endforeach
    </table>
    @endif

    {{-- CTA --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <a href="{{ route('dashboard') }}"
                   style="display:inline-block; background: linear-gradient(135deg, #7C3AED, #A855F7); color:#ffffff; font-size:15px; font-weight:700; padding:14px 36px; border-radius:8px; text-decoration:none;">
                    View Full Dashboard
                </a>
            </td>
        </tr>
    </table>

</x-emails.layout>
