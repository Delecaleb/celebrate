<x-emails.layout subject="Someone contributed to your registry! 🎉">

    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        Your registry just grew, {{ $ownerFirstName }}! 🎉
    </p>
    <p style="margin: 0 0 28px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        Someone put money towards <strong>{{ $wish?->name ?? 'an item on your registry' }}</strong>
        for <strong>{{ $celebration->title }}</strong>. Here are the details:
    </p>

    {{-- Contribution card --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background: linear-gradient(135deg, #F5F3FF, #FDF4FF); border: 1px solid #DDD6FE; border-radius: 12px; padding: 24px; margin-bottom: 28px;">
        <tr>
            <td>
                <p style="margin: 0 0 4px 0; font-size: 28px; text-align: center;">💝</p>

                {{-- In the currency it was paid in. --}}
                <p style="margin: 0 0 16px 0; font-size: 26px; font-weight: 800; color: #7C3AED; text-align: center;">
                    {{ $amount }}
                </p>

                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280; width: 110px;">From</td>
                        <td style="padding: 6px 0; font-size: 13px; font-weight: 600; color: #111827;">{{ $from }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280;">Towards</td>
                        <td style="padding: 6px 0; font-size: 13px; font-weight: 600; color: #111827;">{{ $wish?->name ?? 'Registry item' }}</td>
                    </tr>
                    @if ($raised && $target)
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280;">Progress</td>
                        <td style="padding: 6px 0; font-size: 13px; color: #111827;">{{ $raised }} of {{ $target }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280;">Celebration</td>
                        <td style="padding: 6px 0; font-size: 13px; font-weight: 600; color: #111827;">{{ $celebration->title }}</td>
                    </tr>
                    @if ($contribution->message)
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280; vertical-align: top;">Message</td>
                        <td style="padding: 6px 0; font-size: 13px; color: #374151; font-style: italic; line-height: 1.5;">
                            "{{ $contribution->message }}"
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Total received, in the celebrant's own currency --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 16px 20px; margin-bottom: 28px;">
        <tr>
            <td>
                <p style="margin: 0 0 4px 0; font-size: 13px; color: #6B7280;">Total received for this celebration</p>
                <p style="margin: 0; font-size: 24px; font-weight: 800; color: #059669;">
                    {{ $totals['amount'] }}
                </p>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #6B7280;">
                    from {{ $totals['contributions'] }} registry {{ Str::plural('contribution', $totals['contributions']) }}@if ($totals['gifts'] > 0) and {{ $totals['gifts'] }} {{ Str::plural('gift', $totals['gifts']) }}@endif
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
        All contributions are credited to your wallet on {{ config('app.name') }}.
    </p>

</x-emails.layout>
