<x-emails.layout subject="It's today! {{ $celebration->title }}">

    {{-- Hero --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 28px;">
        <tr>
            <td style="background: linear-gradient(135deg, #EF4444, #F97316); border-radius: 12px; padding: 30px 28px; text-align: center;">
                <p style="margin: 0 0 8px 0; font-size: 48px; line-height: 1;">🎊</p>
                <p style="margin: 0; font-size: 34px; font-weight: 900; color: #ffffff; line-height: 1.1; letter-spacing: -0.5px;">IT'S TODAY!</p>
                <p style="margin: 8px 0 0 0; font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.85);">{{ $celebration->title }}</p>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        Happy {{ $celebration->celebrant_name ? $celebration->celebrant_name . ' day' : 'day' }}, {{ $firstName }}! 🥳
    </p>
    <p style="margin: 0 0 28px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        Your page is waiting and people are going to want somewhere to put all that love. Two minutes now
        and you can forget about it for the rest of the day.
    </p>

    {{-- Setup check --}}
    @if (count($toDo))
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
               style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; padding: 22px 24px; margin-bottom: 20px;">
            <tr>
                <td>
                    <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: 800; color: #B91C1C;">⚡ Two minutes, before the day runs away</p>
                    @foreach ($toDo as $item)
                        <p style="margin: 0 0 8px 0; font-size: 14px; color: #374151; line-height: 1.7;">• {{ $item }}</p>
                    @endforeach
                    <p style="margin: 10px 0 0 0; font-size: 13px; color: #6B7280; line-height: 1.6;">
                        All of it lives in the <strong>Settings</strong> tab of your page — the first tab you land on.
                    </p>
                </td>
            </tr>
        </table>
    @else
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
               style="background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 12px; padding: 20px 24px; margin-bottom: 20px;">
            <tr>
                <td>
                    <p style="margin: 0 0 6px 0; font-size: 16px; font-weight: 800; color: #059669;">✅ Everything is set</p>
                    <p style="margin: 0; font-size: 14px; color: #374151; line-height: 1.7;">
                        Page is live, photos are up, and your bank account is ready for whatever people send. Nothing to fix — go and enjoy it.
                    </p>
                </td>
            </tr>
        </table>
    @endif

    {{-- How today goes well --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 12px; padding: 22px 24px; margin-bottom: 20px;">
        <tr>
            <td>
                <p style="margin: 0 0 12px 0; font-size: 16px; font-weight: 800; color: #5B21B6;">🎯 What makes today busy</p>
                <p style="margin: 0 0 8px 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>This morning —</strong> put the link on your WhatsApp status and your story. Morning posts get the most replies.
                </p>
                <p style="margin: 0 0 8px 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>When people message you —</strong> reply with the link instead of "thank you". That is where most wishes come from.
                </p>
                <p style="margin: 0 0 8px 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>This evening —</strong> post it once more for everyone who missed it, and read the wall properly.
                </p>
                <p style="margin: 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>Any time —</strong> every wish and photo stays on your page for good, and becomes your photobook afterwards.
                </p>

                <p style="margin: 14px 0 0 0; padding: 12px 14px; background: #ffffff; border: 1px dashed #C4B5FD; border-radius: 8px; font-size: 14px; font-weight: 700; color: #7C3AED; word-break: break-all;">
                    {{ $shareUrl }}
                </p>
            </td>
        </tr>
    </table>

    {{-- Where things stand --}}
    <p style="margin: 0 0 20px 0; font-size: 14px; color: #6B7280; line-height: 1.7; text-align: center;">
        Already waiting for you: <strong style="color:#111827">{{ $wishCount }}</strong> {{ Str::plural('wish', $wishCount) }}
        and <strong style="color:#111827">{{ $giftCount }}</strong> {{ Str::plural('gift', $giftCount) }}.
    </p>

    {{-- CTA --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <a href="{{ $shareUrl }}"
                   style="display:inline-block; background: linear-gradient(135deg, #EF4444, #F97316); color:#ffffff; font-size:15px; font-weight:700; padding:14px 36px; border-radius:8px; text-decoration:none;">
                    Open my page
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 20px 0 0 0; font-size: 13px; color: #9CA3AF; text-align: center; line-height: 1.6;">
        Have a wonderful day. 🎈
    </p>

</x-emails.layout>
