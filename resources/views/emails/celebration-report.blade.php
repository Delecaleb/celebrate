<x-emails.layout subject="How {{ $celebration->title }} went">

    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        @if ($isUpdate)
            More came in, {{ $firstName }} 🎁
        @else
            That's a wrap, {{ $firstName }} 🎉
        @endif
    </p>
    <p style="margin: 0 0 28px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        @if ($isUpdate)
            Since your last report, more people found <strong>{{ $celebration->title }}</strong>.
            Here is where everything stands now.
        @else
            Here is how <strong>{{ $celebration->title }}</strong> went — everything the page
            collected, and everyone who showed up for you.
        @endif
    </p>

    {{-- The four numbers, views among them: how many people saw the page is
         what tells a celebrant whether sharing it worked. --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 28px;">
        <tr>
            <td style="width: 50%; padding: 0 6px 12px 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                       style="background: linear-gradient(135deg, #F5F3FF, #EDE9FE); border-radius: 10px; padding: 18px 16px; text-align: center;">
                    <tr><td>
                        <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 600; color: #7C3AED; text-transform: uppercase; letter-spacing: 0.8px;">Received</p>
                        <p style="margin: 0; font-size: 21px; font-weight: 800; color: #4C1D95;">{{ $total }}</p>
                    </td></tr>
                </table>
            </td>
            <td style="width: 50%; padding: 0 0 12px 6px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                       style="background: linear-gradient(135deg, #FFF7ED, #FFEDD5); border-radius: 10px; padding: 18px 16px; text-align: center;">
                    <tr><td>
                        <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 600; color: #B45309; text-transform: uppercase; letter-spacing: 0.8px;">Page views</p>
                        <p style="margin: 0; font-size: 21px; font-weight: 800; color: #92400E;">{{ number_format($viewCount) }}</p>
                    </td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="width: 50%; padding: 0 6px 0 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                       style="background: linear-gradient(135deg, #F0FDF4, #DCFCE7); border-radius: 10px; padding: 18px 16px; text-align: center;">
                    <tr><td>
                        <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 600; color: #059669; text-transform: uppercase; letter-spacing: 0.8px;">Gifts</p>
                        <p style="margin: 0; font-size: 21px; font-weight: 800; color: #065F46;">{{ number_format($giftCount) }}</p>
                    </td></tr>
                </table>
            </td>
            <td style="width: 50%; padding: 0 0 0 6px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                       style="background: linear-gradient(135deg, #FDF2F8, #FCE7F3); border-radius: 10px; padding: 18px 16px; text-align: center;">
                    <tr><td>
                        <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 600; color: #BE185D; text-transform: uppercase; letter-spacing: 0.8px;">Wishes</p>
                        <p style="margin: 0; font-size: 21px; font-weight: 800; color: #9D174D;">{{ number_format($wishCount) }}</p>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($towardsCount > 0)
        <p style="margin: -12px 0 24px 0; font-size: 13px; color: #6B7280;">
            Including {{ $towardsCount }} {{ Str::plural('contribution', $towardsCount) }} towards your registry.
        </p>
    @endif

    {{-- What was actually sent --}}
    @if ($gifts->isNotEmpty())
        <p style="margin: 0 0 10px 0; font-size: 15px; font-weight: 700; color: #111827;">What people sent</p>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
               style="border: 1px solid #E5E7EB; border-radius: 10px; margin-bottom: 28px;">
            @foreach ($gifts as $gift)
                <tr>
                    <td style="padding: 11px 16px; font-size: 14px; color: #374151; {{ ! $loop->last ? 'border-bottom: 1px solid #F3F4F6;' : '' }}">
                        {{ $gift['name'] }}
                    </td>
                    <td style="padding: 11px 16px; font-size: 14px; font-weight: 700; color: #7C3AED; text-align: right; {{ ! $loop->last ? 'border-bottom: 1px solid #F3F4F6;' : '' }}">
                        × {{ $gift['qty'] }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- Who to thank --}}
    @if ($givers->isNotEmpty())
        <p style="margin: 0 0 10px 0; font-size: 15px; font-weight: 700; color: #111827;">People to thank</p>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
               style="border: 1px solid #E5E7EB; border-radius: 10px; margin-bottom: 28px;">
            @foreach ($givers as $giver)
                <tr>
                    <td style="padding: 11px 16px; font-size: 14px; color: #374151; {{ ! $loop->last ? 'border-bottom: 1px solid #F3F4F6;' : '' }}">
                        {{ $giver['name'] }}
                        @if ($giver['count'] > 1)
                            <span style="color: #9CA3AF;">· {{ $giver['count'] }} times</span>
                        @endif
                    </td>
                    <td style="padding: 11px 16px; font-size: 14px; font-weight: 700; color: #059669; text-align: right; {{ ! $loop->last ? 'border-bottom: 1px solid #F3F4F6;' : '' }}">
                        {{ $giver['total'] }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- What to do next --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 12px; padding: 20px 24px; margin-bottom: 28px;">
        <tr>
            <td>
                <p style="margin: 0 0 8px 0; font-size: 15px; font-weight: 800; color: #5B21B6;">Before you close the page</p>
                <p style="margin: 0 0 6px 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>Say thank you</strong> — open the page and reply to the wishes. People see replies.
                </p>
                <p style="margin: 0 0 6px 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>Keep the memories</strong> — the Photobook tab lays every wish and photo out as one keepsake.
                </p>
                <p style="margin: 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>Move your money</strong> — whatever came in is in your wallet, ready to withdraw to your bank.
                </p>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <a href="{{ route('celebrations.show', $celebration->slug) }}"
                   style="display:inline-block; background: linear-gradient(135deg, #7C3AED, #A855F7); color:#ffffff; font-size:15px; font-weight:700; padding:14px 36px; border-radius:8px; text-decoration:none;">
                    Open my page
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 20px 0 0 0; font-size: 13px; color: #9CA3AF; text-align: center; line-height: 1.6;">
        Your page stays up for good — anything that arrives later still lands on it.
    </p>

</x-emails.layout>
