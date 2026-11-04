<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Alamat Email - {{ config('app.name', 'SCP SMKN 1 Bantul') }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 24px; color: #1e293b;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="background-color: #1e3a8a; padding: 20px 24px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 700;">{{ config('app.name', 'SCP - SMKN 1 Bantul') }}</h1>
            <p style="color: #93c5fd; margin: 4px 0 0 0; font-size: 13px;">Sistem Catatan Pembelajaran</p>
        </div>
        <div style="padding: 28px 24px;">
            <h2 style="font-size: 18px; margin-top: 0; color: #0f172a;">Verifikasi Kepemilikan Email</h2>
            <p style="font-size: 14px; line-height: 1.6; color: #475569;">
                Kami menerima permintaan pendaftaran / pembaruan alamat email pada akun SCP Anda. Masukkan 6 digit kode verifikasi berikut:
            </p>
            <div style="text-align: center; margin: 28px 0;">
                <span style="display: inline-block; font-size: 32px; font-weight: 800; letter-spacing: 6px; padding: 12px 24px; background: #f1f5f9; color: #1e3a8a; border-radius: 8px; border: 1px dashed #cbd5e1;">
                    {{ $code }}
                </span>
            </div>
            <p style="font-size: 13px; line-height: 1.5; color: #64748b; margin-bottom: 8px;">
                • Kode ini berlaku selama <strong>15 menit</strong> sejak dikirimkan.<br>
                • Batas percobaan verifikasi kode maksimal <strong>5 kali</strong>.<br>
                • Jika Anda tidak melakukan pendaftaran email ini, abaikan pesan ini.
            </p>
        </div>
        <div style="background-color: #f8fafc; padding: 14px 24px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 12px; color: #94a3b8; margin: 0;">&copy; {{ date('Y') }} {{ config('app.name', 'SCP') }} - SMKN 1 Bantul. Hak cipta dilindungi.</p>
        </div>
    </div>
</body>
</html>
