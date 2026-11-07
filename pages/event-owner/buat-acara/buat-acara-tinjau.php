<?php
declare(strict_types=1);

// Wizard "Buat acara" kini satu halaman (4 langkah) di buat-acara/index.php.
// URL lama diarahkan ke langkah yang sesuai.
header('Location: ' . BASE_URL . '/event-owner/buat-acara#tinjau');
exit;
