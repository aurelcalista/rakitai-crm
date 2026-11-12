<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Login Portal PMB UCIC</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background-color:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background-color:#004b99;padding:24px 28px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:18px;font-weight:700;letter-spacing:0.3px;">
                                Portal PMB UCIC
                            </h1>
                            <p style="margin:6px 0 0;color:#dbeafe;font-size:12px;">
                                Universitas Catur Insan Cendekia
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
                                Halo <strong>{{ $recipientName }}</strong>,
                            </p>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#475569;">
                                Gunakan kode OTP berikut untuk masuk ke akun Portal PMB UCIC Anda:
                            </p>

                            <div style="margin:0 0 20px;padding:18px;background-color:#eff6ff;border:1px dashed #93c5fd;border-radius:12px;text-align:center;">
                                <span style="display:inline-block;font-size:30px;font-weight:800;letter-spacing:8px;color:#004b99;font-family:'Courier New',monospace;">
                                    {{ $otpCode }}
                                </span>
                            </div>

                            <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#475569;">
                                Kode ini berlaku selama <strong>{{ $expiresInMinutes }} menit</strong> dan hanya dapat digunakan satu kali.
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">
                                Demi keamanan akun Anda, jangan bagikan kode OTP ini kepada siapa pun termasuk pihak yang mengaku dari UCIC. Jika Anda tidak merasa melakukan permintaan login ini, abaikan email ini.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8fafc;padding:16px 28px;border-top:1px solid #e2e8f0;text-align:center;">
                            <p style="margin:0;font-size:11px;color:#94a3b8;">
                                &copy; {{ date('Y') }} Universitas Catur Insan Cendekia. Email ini dikirim secara otomatis.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

