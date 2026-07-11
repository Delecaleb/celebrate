<x-emails.layout subject="How {{ config('app.name') }} works — your quick guide">

    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        Getting started is easy, {{ $firstName }} 👋
    </p>
    <p style="margin: 0 0 32px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        Here's a simple step-by-step guide to help you get the most out of {{ config('app.name') }}.
    </p>

    {{-- Steps --}}
    @foreach ([
        ['1', '#7C3AED', 'Create a Celebration Page', 'Head to your dashboard and click "Create Celebration". Pick a type (birthday, wedding, graduation, anniversary, or custom), add the date, venue, and a cover photo.'],
        ['2', '#A855F7', 'Customize Your Page', 'Choose a theme, set colors, add intro video, background music, and pick from stunning templates to make it truly yours.'],
        ['3', '#EC4899', 'Invite Your Guests', 'Share your unique celebration link via WhatsApp, email, or social media. Guests can leave wishes, react, upload photos and videos.'],
        ['4', '#F59E0B', 'Receive Gifts & Wishes', 'Enable the gifting feature to let guests send platform gifts or fund your wish list. All contributions go straight to your wallet.'],
        ['5', '#10B981', 'Relive the Moments', 'After your celebration, your page becomes a permanent memory archive — photos, wishes, messages and all.'],
    ] as [$step, $color, $title, $desc])
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 24px;">
        <tr>
            <td style="vertical-align: top; width: 44px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background-color: {{ $color }}; display: inline-block; text-align: center; line-height: 36px; font-size: 15px; font-weight: 700; color: #ffffff;">
                    {{ $step }}
                </div>
            </td>
            <td style="vertical-align: top; padding-left: 14px;">
                <p style="margin: 0 0 4px 0; font-size: 15px; font-weight: 700; color: #111827;">{{ $title }}</p>
                <p style="margin: 0; font-size: 13px; color: #6B7280; line-height: 1.6;">{{ $desc }}</p>
            </td>
        </tr>
    </table>
    @endforeach

    <hr style="border: none; border-top: 1px solid #E5E7EB; margin: 28px 0;">

    {{-- Tips box --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #F5F3FF; border-left: 4px solid #7C3AED; border-radius: 0 8px 8px 0; padding: 16px 20px; margin-bottom: 28px;">
        <tr>
            <td>
                <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: 700; color: #7C3AED;">Pro Tips</p>
                <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #4B5563; line-height: 1.7;">
                    <li>Set your celebration as <strong>public</strong> so guests can find it without a direct link.</li>
                    <li>Add a <strong>wish list</strong> so guests know exactly what you'd love to receive.</li>
                    <li>Enable <strong>guest posts</strong> to let attendees share their own photos and videos.</li>
                    <li>Use the <strong>countdown timer</strong> to build excitement before your event.</li>
                </ul>
            </td>
        </tr>
    </table>

    {{-- CTA --}}
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <a href="{{ route('dashboard') }}"
                   style="display:inline-block; background: linear-gradient(135deg, #7C3AED, #A855F7); color:#ffffff; font-size:15px; font-weight:700; padding:14px 36px; border-radius:8px; text-decoration:none;">
                    Go to My Dashboard
                </a>
            </td>
        </tr>
    </table>

</x-emails.layout>
