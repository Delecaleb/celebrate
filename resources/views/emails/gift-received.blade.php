<x-emails.layout subject="You received a gift! 🎁">

    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        A gift just landed, {{ $ownerFirstName }}! 🎁
    </p>
    <p style="margin: 0 0 28px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        Someone sent you a gift for <strong>{{ $celebration->title }}</strong>. Here are the details:
    </p>

    {{-- Gift card --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background: linear-gradient(135deg, #F5F3FF, #FDF4FF); border: 1px solid #DDD6FE; border-radius: 12px; padding: 24px; margin-bottom: 28px;">
        <tr>
            <td>
                <p style="margin: 0 0 4px 0; font-size: 28px; text-align: center;">🎁</p>
                <p style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700; color: #7C3AED; text-align: center;">
                    {{ $gift->platformGift->gift_name ?? 'Gift' }}
                </p>

                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280; width: 110px;">From</td>
                        <td style="padding: 6px 0; font-size: 13px; font-weight: 600; color: #111827;">
                            {{ $gift->is_anonymous ? 'Anonymous' : $gift->sender_name }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280;">Amount</td>
                        <td style="padding: 6px 0; font-size: 13px; font-weight: 700; color: #7C3AED;">
                            ${{ number_format($gift->amount, 2) }} USD
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280;">Celebration</td>
                        <td style="padding: 6px 0; font-size: 13px; font-weight: 600; color: #111827;">{{ $celebration->title }}</td>
                    </tr>
                    @if ($gift->event_date ?? $celebration->event_date)
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280;">Event Date</td>
                        <td style="padding: 6px 0; font-size: 13px; color: #111827;">
                            {{ $celebration->event_date?->format('F j, Y') }}
                        </td>
                    </tr>
                    @endif
                    @if ($gift->message)
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280; vertical-align: top;">Message</td>
                        <td style="padding: 6px 0; font-size: 13px; color: #374151; font-style: italic; line-height: 1.5;">
                            "{{ $gift->message }}"
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Total gifts summary --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 16px 20px; margin-bottom: 28px;">
        <tr>
            <td>
                <p style="margin: 0 0 4px 0; font-size: 13px; color: #6B7280;">Total gifts received for this celebration</p>
                <p style="margin: 0; font-size: 24px; font-weight: 800; color: #059669;">
                    ${{ number_format($totalReceived, 2) }} USD
                </p>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #6B7280;">
                    from {{ $giftCount }} {{ Str::plural('gift', $giftCount) }}
                </p>
            </td>
        </tr>
    </table>

    {{-- CTA --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <a href="{{ route('celebrations.show', $celebration->slug) }}"
                   style="display:inline-block; background: linear-gradient(135deg, #7C3AED, #A855F7); color:#ffffff; font-size:15px; font-weight:700; padding:14px 36px; border-radius:8px; text-decoration:none;">
                    View Celebration Page
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 20px 0 0 0; font-size: 13px; color: #9CA3AF; text-align: center; line-height: 1.6;">
        All gifts are credited to your wallet on {{ config('app.name') }}.
    </p>

</x-emails.layout>
