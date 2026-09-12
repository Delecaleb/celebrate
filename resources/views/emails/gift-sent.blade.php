<x-emails.layout subject="Your gift is on its way 🎁">

    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        Thank you{{ $senderFirstName ? ', ' . $senderFirstName : '' }} 🎁
    </p>
    <p style="margin: 0 0 28px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        @if (count($lines) > 1)
            Your gifts to <strong>{{ $celebration->celebrant_name }}</strong> came through, and they are now
            on their celebration page for good. Here is your receipt.
        @else
            Your gift to <strong>{{ $celebration->celebrant_name }}</strong> came through, and it is now on
            their celebration page for good. Here is your receipt.
        @endif
    </p>

    {{-- Receipt --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background: linear-gradient(135deg, #F5F3FF, #FDF4FF); border: 1px solid #DDD6FE; border-radius: 12px; padding: 24px; margin-bottom: 28px;">
        <tr>
            <td>
                <p style="margin: 0 0 4px 0; font-size: 28px; text-align: center;">🎁</p>

                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 14px;">
                    @foreach ($lines as $line)
                        <tr>
                            <td style="padding: 5px 0; font-size: 15px; font-weight: 700; color: #7C3AED;">{{ $line['label'] }}</td>
                            <td style="padding: 5px 0; font-size: 14px; font-weight: 700; color: #111827; text-align: right; white-space: nowrap;">{{ $line['amount'] }}</td>
                        </tr>
                    @endforeach
                </table>

                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280; width: 110px;">To</td>
                        <td style="padding: 6px 0; font-size: 13px; font-weight: 600; color: #111827;">
                            {{ $celebration->celebrant_name }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280;">Total paid</td>
                        <td style="padding: 6px 0; font-size: 13px; font-weight: 700; color: #7C3AED;">
                            {{ $amount }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280;">Celebration</td>
                        <td style="padding: 6px 0; font-size: 13px; font-weight: 600; color: #111827;">{{ $celebration->title }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280;">Date</td>
                        <td style="padding: 6px 0; font-size: 13px; color: #111827;">
                            {{ $gift->created_at?->format('F j, Y') }}
                        </td>
                    </tr>
                    @if ($gift->message)
                    <tr>
                        <td style="padding: 6px 0; font-size: 13px; color: #6B7280; vertical-align: top;">Your message</td>
                        <td style="padding: 6px 0; font-size: 13px; color: #374151; font-style: italic; line-height: 1.5;">
                            "{{ $gift->message }}"
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- The reference, which is the whole point of a receipt --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 16px 20px; margin-bottom: 28px;">
        <tr>
            <td>
                <p style="margin: 0 0 4px 0; font-size: 13px; color: #6B7280;">Payment reference</p>
                <p style="margin: 0; font-size: 14px; font-weight: 700; color: #111827; font-family: ui-monospace, Menlo, Consolas, monospace; word-break: break-all;">
                    {{ $gift->transaction_reference }}
                </p>
                <p style="margin: 6px 0 0 0; font-size: 12px; color: #9CA3AF; line-height: 1.5;">
                    Keep this. If anything looks wrong with this payment, quote it to us and we can find it straight away.
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
                    See {{ $celebration->celebrant_name }}'s page
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 20px 0 0 0; font-size: 13px; color: #9CA3AF; text-align: center; line-height: 1.6;">
        You are getting this because you sent a gift on {{ config('app.name') }}. It is a receipt, not a
        subscription — there is nothing to unsubscribe from.
    </p>

</x-emails.layout>
