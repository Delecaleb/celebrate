<x-emails.layout subject="Your celebrant list is in">

    <p style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #111827;">
        Your celebrant list is in 📋
    </p>
    <p style="margin: 0 0 28px 0; font-size: 15px; color: #6B7280; line-height: 1.6;">
        We finished going through the file you uploaded. Here is what happened to it.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
           style="background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px;">
        <tr>
            <td>
                <p style="margin: 0; font-size: 24px; font-weight: 800; color: #059669;">{{ $processed }}</p>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: #6B7280;">
                    {{ Str::plural('celebrant', $processed) }} added. We will remind you before each one.
                </p>
            </td>
        </tr>
    </table>

    @if (count($errors) > 0)
        {{-- Named individually: "12 rows failed" is not something anyone can act on. --}}
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
               style="background-color: #FFF7ED; border: 1px solid #FED7AA; border-radius: 8px; padding: 16px 20px; margin-bottom: 28px;">
            <tr>
                <td>
                    <p style="margin: 0 0 10px 0; font-size: 13px; font-weight: 700; color: #9A3412;">
                        {{ count($errors) }} {{ Str::plural('row', count($errors)) }} skipped
                    </p>
                    @foreach (array_slice($errors, 0, 20) as $error)
                        <p style="margin: 0 0 5px 0; font-size: 13px; color: #7C2D12; line-height: 1.5;">{{ $error }}</p>
                    @endforeach
                    @if (count($errors) > 20)
                        <p style="margin: 8px 0 0 0; font-size: 12px; color: #9A3412;">
                            …and {{ count($errors) - 20 }} more. Fix these and upload the file again — the ones
                            already added will not be duplicated.
                        </p>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <a href="{{ route('dashboard.upcoming') }}"
                   style="display:inline-block; background: linear-gradient(135deg, #7C3AED, #A855F7); color:#ffffff; font-size:15px; font-weight:700; padding:14px 36px; border-radius:8px; text-decoration:none;">
                    See who is coming up
                </a>
            </td>
        </tr>
    </table>

</x-emails.layout>
