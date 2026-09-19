<x-emails.layout subject="Two days to go — let's get {{ $celebration->title }} ready!">

    {{-- Hero --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 28px;">
        <tr>
            <td style="background: linear-gradient(135deg, #7C3AED, #A855F7); border-radius: 12px; padding: 28px; text-align: center;">
                <p style="margin: 0 0 6px 0; font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.78); text-transform: uppercase; letter-spacing: 2px;">Two days to go</p>
                <p style="margin: 0; font-size: 64px; font-weight: 900; color: #ffffff; line-height: 1;">2</p>
                <p style="margin: 0; font-size: 20px; font-weight: 700; color: rgba(255,255,255,0.92);">DAYS</p>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        Two days, {{ $firstName }}! 🎉
    </p>
    <p style="margin: 0 0 28px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        <strong>{{ $celebration->title }}</strong>@if ($dateLabel) is on {{ $dateLabel }}@endif — and everything is still
        easy to change. Here is the two-minute version of getting it perfect.
    </p>

    {{-- 1. Editing the page --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 12px; padding: 22px 24px; margin-bottom: 20px;">
        <tr>
            <td>
                <p style="margin: 0 0 12px 0; font-size: 16px; font-weight: 800; color: #5B21B6;">✏️ Want to change something?</p>
                <p style="margin: 0 0 6px 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>1.</strong> Open your page with the button below.
                </p>
                <p style="margin: 0 0 6px 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>2.</strong> You land on the <strong>Settings</strong> tab — it is the first one.
                </p>
                <p style="margin: 0 0 6px 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>3.</strong> Add or remove <strong>cover photos</strong> at the top, then edit the title, who it is
                    for, the date and the short description.
                </p>
                <p style="margin: 0 0 6px 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>4.</strong> Further down, pick a <strong>theme</strong> and your own background and text colours.
                </p>
                <p style="margin: 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>5.</strong> Press <strong>Save page details</strong>. Guests see the change straight away — no need to resend anything.
                </p>
            </td>
        </tr>
    </table>

    {{-- 2. Sharing --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #FFF7ED; border: 1px solid #FED7AA; border-radius: 12px; padding: 22px 24px; margin-bottom: 20px;">
        <tr>
            <td>
                <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: 800; color: #B45309;">📣 Now get the link out</p>
                <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    This is your page. Anyone with it can write a wish or send a gift — no account, no app:
                </p>

                <p style="margin: 0 0 14px 0; padding: 12px 14px; background: #ffffff; border: 1px dashed #FDBA74; border-radius: 8px; font-size: 14px; font-weight: 700; color: #7C3AED; word-break: break-all;">
                    {{ $shareUrl }}
                </p>

                <p style="margin: 0 0 6px 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>Where it works best:</strong> your WhatsApp status, the family group, your Instagram story,
                    and a reply to anyone who asks what you want.
                </p>
                <p style="margin: 0 0 6px 0; font-size: 14px; color: #374151; line-height: 1.7;">
                    <strong>Copy this if it helps:</strong>
                </p>
                <p style="margin: 0; padding: 12px 14px; background: #ffffff; border-radius: 8px; font-size: 14px; color: #4B5563; font-style: italic; line-height: 1.6;">
                    "{{ $celebration->celebrant_name ?? 'We' }}@if ($dateLabel) — it's happening {{ $dateLabel }}@endif! Instead of a card,
                    leave a message here 👉 {{ $shareUrl }}"
                </p>
            </td>
        </tr>
    </table>

    {{-- 3. Anything still missing --}}
    @if (count($toDo))
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
               style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; padding: 22px 24px; margin-bottom: 28px;">
            <tr>
                <td>
                    <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: 800; color: #B91C1C;">⏳ Still worth doing</p>
                    @foreach ($toDo as $item)
                        <p style="margin: 0 0 8px 0; font-size: 14px; color: #374151; line-height: 1.7;">• {{ $item }}</p>
                    @endforeach
                </td>
            </tr>
        </table>
    @else
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
               style="background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 12px; padding: 18px 24px; margin-bottom: 28px;">
            <tr>
                <td>
                    <p style="margin: 0; font-size: 15px; font-weight: 700; color: #059669;">✅ Your page is ready. All that is left is telling people.</p>
                </td>
            </tr>
        </table>
    @endif

    {{-- Where things stand --}}
    <p style="margin: 0 0 20px 0; font-size: 14px; color: #6B7280; line-height: 1.7; text-align: center;">
        So far: <strong style="color:#111827">{{ $wishCount }}</strong> {{ Str::plural('wish', $wishCount) }},
        <strong style="color:#111827">{{ $giftCount }}</strong> {{ Str::plural('gift', $giftCount) }} and
        <strong style="color:#111827">{{ $registryCount }}</strong> registry {{ Str::plural('item', $registryCount) }}.
    </p>

    {{-- CTA --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <a href="{{ $shareUrl }}"
                   style="display:inline-block; background: linear-gradient(135deg, #7C3AED, #A855F7); color:#ffffff; font-size:15px; font-weight:700; padding:14px 36px; border-radius:8px; text-decoration:none;">
                    Open my page
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 20px 0 0 0; font-size: 13px; color: #9CA3AF; text-align: center; line-height: 1.6;">
        Two days. It is going to be lovely.
    </p>

</x-emails.layout>
