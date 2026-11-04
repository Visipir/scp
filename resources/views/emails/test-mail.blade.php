<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Uji Coba Pengaturan Email - {{ config('app.name', 'SCP SMKN 1 Bantul') }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 24px; color: #1e293b;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="background-color: #059669; padding: 20px 24px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 700;">Konfigurasi Email Berhasil</h1>
            <p style="color: #a7f3d0; margin: 4px 0 0 0; font-size: 13px;">{{ config('app.name', 'SCP - SMKN 1 Bantul') }}</p>
        </div>
        <div style="padding: 28px 24px;">
            <h2 style="font-size: 18px; margin-top: 0; color: #0f172a;">Halo, {{ $userName }}</h2>
            <p style="font-size: 14px; line-height: 1.6; color: #475569;">
                Ini adalah email uji coba untuk memverifikasi bahwa pengaturan SMTP server pada sistem SCP telah terhubung dengan benar dan siap digunakan untuk pengiriman notifikasi serta reset kata sandi.
            </p>
            <p style="font-size: 13px; line-height: 1.5; color: #64748b;">
                Waktu Pengujian: <strong>{{ now('Asia/Jakarta')->translatedFormat('d F Y, H:i:s') }} WIB</strong>
            </p>
        </div>
        <div style="background-color: #f8fafc; padding: 14px 24px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 12px; color: #94a3b8; margin: 0;">&copy; {{ date('Y') }} {{ config('app.name', 'SCP') }} - SMKN 1 Bantul.</p>
        </div>
    </div>
</body>
</html>
