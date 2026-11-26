<?php
declare(strict_types=1);

// Uji koneksi email (SMTP) TIXORA dari terminal:
//   php bin/test-email.php alamat-tujuan@gmail.com
// Di Docker:  docker compose exec app php bin/test-email.php alamat-tujuan@gmail.com
require_once __DIR__ . '/../api/helpers/MailHelper.php';

$to = $argv[1] ?? '';
if ($to === '') {
    fwrite(STDERR, "Pemakaian: php bin/test-email.php alamat-tujuan@email.com\n");
    exit(1);
}

echo "SMTP_HOST       : " . (MailHelper::env('SMTP_HOST') ?: '(kosong - email TIDAK akan terkirim)') . "\n";
echo "SMTP_PORT       : " . MailHelper::env('SMTP_PORT', '587') . "\n";
echo "SMTP_ENCRYPTION : " . MailHelper::env('SMTP_ENCRYPTION', 'tls') . "\n";
echo "SMTP_USERNAME   : " . (MailHelper::env('SMTP_USERNAME') ?: '(kosong)') . "\n";
echo "SMTP_PASSWORD   : " . (MailHelper::env('SMTP_PASSWORD') !== '' ? '(terisi)' : '(kosong)') . "\n\n";

if (!MailHelper::isConfigured()) {
    echo "GAGAL: SMTP belum dikonfigurasi. Isi SMTP_HOST, SMTP_USERNAME, SMTP_PASSWORD di file .env (salin dari .env.example).\n";
    exit(1);
}

$ok = MailHelper::sendPasswordReset($to, 'Penguji', 'https://contoh.tixora/atur-ulang-kata-sandi?token=uji-coba', 60);
if ($ok) {
    echo "BERHASIL: email uji dikirim ke {$to}. Periksa kotak masuk (dan folder spam).\n";
    exit(0);
}
echo "GAGAL: " . MailHelper::lastError() . "\n";
exit(1);