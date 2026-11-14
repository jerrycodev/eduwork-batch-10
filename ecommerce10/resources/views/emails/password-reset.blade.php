<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Reset Password - {{ $appName }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; color:#1f2937; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f3f4f6; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="padding:28px 36px; background-color:#4f46e5;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="middle" style="width:42px; height:42px; border-radius:12px; background-color:#ffffff; color:#4f46e5; text-align:center; font-size:25px; font-weight:bold;">{{ mb_substr($appName, 0, 1) }}</td>
                                    <td valign="middle" style="padding-left:12px; color:#ffffff; font-size:20px; font-weight:700;">{{ $appName }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 36px 32px;">
                            <p style="margin:0 0 12px; color:#6b7280; font-size:13px; font-weight:700; letter-spacing:1px; text-transform:uppercase;">Keamanan akun</p>
                            <h1 style="margin:0 0 20px; color:#111827; font-size:26px; line-height:1.3;">Atur ulang password Anda</h1>
                            <p style="margin:0 0 16px; color:#374151; font-size:15px; line-height:1.7;">Halo {{ $userName }},</p>
                            <p style="margin:0 0 26px; color:#4b5563; font-size:15px; line-height:1.7;">Kami menerima permintaan untuk mengatur ulang password akun {{ $appName }} Anda. Klik tombol di bawah untuk membuat password baru.</p>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 26px;">
                                <tr>
                                    <td align="center" style="border-radius:9px; background-color:#4f46e5;">
                                        <a href="{{ $resetUrl }}" target="_blank" style="display:inline-block; padding:14px 24px; border:1px solid #4f46e5; border-radius:9px; color:#ffffff; font-size:15px; font-weight:700; text-decoration:none;">Atur Ulang Password</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 10px; color:#6b7280; font-size:13px; line-height:1.7;">Tautan ini berlaku selama <strong style="color:#374151;">{{ $expireMinutes }} menit</strong>. Jika tombol tidak berfungsi, salin dan buka tautan berikut di browser Anda:</p>
                            <p style="margin:0 0 26px; overflow-wrap:anywhere; color:#4f46e5; font-size:13px; line-height:1.7;"><a href="{{ $resetUrl }}" style="color:#4f46e5;">{{ $resetUrl }}</a></p>
                            <div style="padding:16px; border-radius:10px; background-color:#f9fafb;">
                                <p style="margin:0; color:#6b7280; font-size:13px; line-height:1.7;">Jika Anda tidak meminta pengaturan ulang password, abaikan email ini. Password Anda tetap aman dan tidak akan berubah.</p>
                            </div>
                            <p style="margin:28px 0 0; color:#4b5563; font-size:14px; line-height:1.7;">Salam,<br><strong style="color:#111827;">Tim {{ $appName }}</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 36px; border-top:1px solid #e5e7eb; background-color:#fafafa;">
                            <p style="margin:0; color:#9ca3af; font-size:12px; line-height:1.6; text-align:center;">Email otomatis dari {{ $appName }}. Mohon jangan membalas email ini.</p>
                            <p style="margin:6px 0 0; color:#9ca3af; font-size:12px; text-align:center;">&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
