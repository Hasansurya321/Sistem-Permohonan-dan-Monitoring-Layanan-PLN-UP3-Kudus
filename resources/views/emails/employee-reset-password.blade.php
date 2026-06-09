<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password Pegawai</title>
</head>
<body style="font-family: Inter, sans-serif; color: #0F172A; background: #F8FAFC; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px; background: #FFFFFF; border-radius: 18px; box-shadow: 0 18px 50px rgba(9, 60, 93, 0.08);">
        <h1 style="font-size: 24px; margin-bottom: 12px; color: #093C5D;">Reset Password Pegawai</h1>
        <p>Halo {{ $name }},</p>
        <p>Kami menerima permintaan reset password untuk akun pegawai Anda.</p>
        <p>Untuk mengatur ulang password, silakan klik tombol di bawah ini. Link akan kedaluwarsa dalam 60 menit.</p>
        <div style="margin: 24px 0; text-align: center;">
            <a href="{{ $url }}" style="display: inline-block; padding: 14px 24px; background: #093C5D; color: #FFFFFF; border-radius: 12px; text-decoration: none; font-weight: 700;">Reset Password</a>
        </div>
        <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
        <p style="color: #64748B; font-size: 14px;">Terima kasih,<br>Tim PLN UP3 Kudus</p>
    </div>
</body>
</html>
