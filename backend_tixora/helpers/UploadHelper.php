<?php
declare(strict_types=1);

/**
 * UploadHelper untuk menangani unggahan berkas fisik (Banner Event / Poster) 
 * secara aman menggunakan PHP Native (PHP 8.x+)
 */
class UploadHelper {
    private string $uploadDir;
    private array $allowedMimeTypes;
    private int $maxFileSize;

    public function __construct(
        string $uploadDir = __DIR__ . '/../public/uploads/', 
        int $maxFileSize = 2 * 1024 * 1024 // Batasan maksimal 2 MB
    ) {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->maxFileSize = $maxFileSize;
        
        // Jenis MIME yang diizinkan untuk gambar event/banner
        $this->allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        // Pastikan direktori penyimpanan ada
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Memproses unggahan satu berkas fisik
     * 
     * @param array $file Berasal dari $_FILES['nama_input']
     * @return string Mengembalikan nama file unik yang berhasil disimpan
     * @throws Exception Jika terjadi kesalahan validasi atau proses simpan
     */
    public function uploadFile(array $file): string {
        // 1. Validasi keberadaan error pada PHP File Upload
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Parameter berkas unggahan tidak valid.');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('Tidak ada berkas yang diunggah.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('Ukuran berkas melebihi batas maksimal yang diizinkan server.');
            default:
                throw new Exception('Terjadi kesalahan yang tidak diketahui saat mengunggah berkas.');
        }

        // 2. Validasi ukuran fisik berkas
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('Ukuran berkas terlalu besar. Maksimal 2MB.');
        }

        // 3. Validasi tipe MIME asli menggunakan Fileinfo (Mencegah pemalsuan ekstensi)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new Exception('Tipe berkas tidak diizinkan. Hanya format JPEG, PNG, dan WebP yang diperbolehkan.');
        }

        // 4. Generate nama file unik yang aman (Clean Unique Filename)
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeFilename = sprintf('banner_%s_%s.%s', date('YmdHis'), bin2hex(random_bytes(4)), strtolower($extension));
        $destination = $this->uploadDir . $safeFilename;

        // 5. Pindahkan berkas dari direktori temporary ke direktori tujuan permanen
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Gagal memindahkan berkas unggahan ke server.');
        }

        return $safeFilename;
    }
}