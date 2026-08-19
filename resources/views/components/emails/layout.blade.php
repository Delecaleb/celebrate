<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? config('app.name') }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #F3F4F6; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        table { border-collapse: collapse; }
        img { border: 0; display: block; }
        a { color: #7C3AED; text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .email-wrapper { width: 100% !important; }
            .email-body { padding: 24px 16px !important; }
        }
    </style>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#F3F4F6; padding: 32px 16px;">
    <tr>
        <td align="center">
            <table class="email-wrapper" width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px; width:100%;">

                {{-- Header --}}
                <tr>
                    <td style="background: linear-gradient(135deg, #7C3AED 0%, #A855F7 100%); border-radius: 12px 12px 0 0; padding: 28px 40px; text-align: center;">
                        <span style="font-size: 28px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">
                            🎉 {{ config('app.name', 'Celebrate') }}
                        </span>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td class="email-body" style="background-color: #ffffff; padding: 40px 48px;">
                        {{ $slot }}
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background-color: #F9FAFB; border-radius: 0 0 12px 12px; padding: 24px 48px; text-align: center; border-top: 1px solid #E5E7EB;">
                        <p style="margin: 0 0 8px 0; font-size: 13px; color: #6B7280;">
                            You're receiving this because you have an account on
                            <a href="{{ config('app.url') }}" style="color:#7C3AED; font-weight:600;">{{ config('app.name') }}</a>.
                        </p>
                        <p style="margin: 0; font-size: 12px; color: #9CA3AF;">
                            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
