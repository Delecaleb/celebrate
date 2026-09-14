<x-emails.layout subject="You received a gift! 🎁">

    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        A gift just landed, {{ $ownerFirstName }}! 🎁
    </p>
    <p style="margin: 0 0 28px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        @if (count($lines) > 1)
            Someone sent you {{ count($lines) }} gifts for <strong>{{ $celebration->title }}</strong>, all in one go:
        @else
            Someone sent you a gift for <strong>{{ $celebration->title }}</strong>. Here are the details:
        @endif
    </p>

    {{-- Gift card --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background: linear-gradient(135deg, #F5F3FF, #FDF4FF); border: 1px solid #DDD6FE; border-radius: 12px; padding: 24px; margin-bottom: 28px;">
        <tr>
            <td>
                <p style="margin: 0 0 4px 0; font-size: 28px; text-align: center;">🎁</p>

                {{-- One row per gift. A single gift still reads as one line,
                     so nothing changes for the common case. --}}
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 14px;">
                    @foreach ($lines as $line)
                        <tr>
                            <td style="padding: 5px 0; font-size: 15px; font-weight: 700; color: #7C3AED;">{{ $line['label'] }}</td>
                            <td style="padding: 5px 0; font-size: 14px; font-weight: 700; color: #111827; text-align: right; white-space: nowrap;">{{ $line['amount'] }}</td>
                        </tr>
                    @endforeach
                    @if (count($lines) > 1)
                        <tr>
                            <td style="padding: 9px 0 0; border-top: 1px solid #DDD6FE; font-size: 13px; color: #6B7280;">Total</td>
                            <td style="padding: 9px 0 0; border-top: 1px solid #DDD6FE; font-size: 15px; font-weight: 800; color: #7C3AED; text-align: right; white-space: nowrap;">{{ $basketTotal }}</td>
                        </tr>
                    @endif
                </table>

                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280; width: 110px;">From</td>
                        <td style="padding: 6px 0; font-size: 13px; font-weight: 600; color: #111827;">
                            {{ $gift->is_anonymous ? 'Anonymous' : $gift->sender_name }}
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
                <p style="margin: 0 0 4px 0; font-size: 13px; color: #6B7280;">Total received for this celebration</p>
                {{-- Already converted into the celebrant's currency and labelled
                     with it — never a raw sum of mixed currencies. --}}
                <p style="margin: 0; font-size: 24px; font-weight: 800; color: #059669;">
                    {{ $totalReceived }}
                </p>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #6B7280;">
                    from {{ $giftCount }} {{ Str::plural('gift', $giftCount) }}@if ($contributionCount > 0) and {{ $contributionCount }} registry {{ Str::plural('contribution', $contributionCount) }}@endif
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
