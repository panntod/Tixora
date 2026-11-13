<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/EventHelper.php' : __DIR__ . '/../../../api/helpers/EventHelper.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EventHelper::requireDraft($db, $owner);
EventHelper::ensureSchema($db);

/** Angka dari input "75.000" / "75000" -> int; null bila kosong. */
$toInt = static function (string $s): ?int {
    $d = preg_replace('/\D+/', '', $s) ?? '';
    return $d === '' ? null : (int) $d;
};
$validDate = static fn(string $s): ?string => preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) && strtotime($s) !== false ? $s : null;

// Data awal: dari database, atau satu kartu "Umum" kosong untuk acara baru.
$rows = [];
$st = $db->prepare("SELECT nama_kategori, harga, kuota, mulai_dijual, berakhir_dijual
                    FROM ticket_categories WHERE event_id = :id ORDER BY created_at ASC, nama_kategori ASC");
$st->execute(['id' => $event['id']]);
foreach ($st->fetchAll() as $r) {
    $rows[] = [
        'nama' => (string) $r['nama_kategori'],
        'harga' => (int) $r['harga'] > 0 ? number_format((int) $r['harga'], 0, ',', '.') : '0',
        'kuota' => (string) $r['kuota'],
        'mulai' => (string) ($r['mulai_dijual'] ?? ''),
        'akhir' => (string) ($r['berakhir_dijual'] ?? ''),
    ];
}
if (!$rows) {
    $rows[] = ['nama' => 'Umum', 'harga' => '', 'kuota' => '', 'mulai' => '', 'akhir' => ''];
}

$errors = [];   // [indeks][kolom] => pesan
$topError = '';
$eventEnd = date('Y-m-d', (int) strtotime((string) $event['tanggal_selesai']));
// Draf yang belum mengisi waktu (mulai = selesai) belum punya batas akhir penjualan yang bermakna.
$eventDatesSet = $event['tanggal_mulai'] !== $event['tanggal_selesai'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = ($_POST['aksi'] ?? '') === 'draf' ? 'draf' : 'lanjut';
    if (!ViewHelper::checkCsrf()) {
        $topError = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        $rows = [];
        $parsed = [];
        $names = [];
        foreach ((array) ($_POST['kategori'] ?? []) as $i => $in) {
            $in = (array) $in;
            $row = [
                'nama' => trim((string) ($in['nama'] ?? '')),
                'harga' => trim((string) ($in['harga'] ?? '')),
                'kuota' => trim((string) ($in['kuota'] ?? '')),
                'mulai' => trim((string) ($in['mulai'] ?? '')),
                'akhir' => trim((string) ($in['akhir'] ?? '')),
            ];
            $rows[$i] = $row;
            $e = [];

            if ($row['nama'] === '') {
                $e['nama'] = 'Nama kategori wajib diisi.';
            } elseif (mb_strlen($row['nama']) > 100) {
                $e['nama'] = 'Nama kategori maksimal 100 karakter.';
            } elseif (isset($names[mb_strtolower($row['nama'])])) {
                $e['nama'] = 'Nama kategori sudah dipakai.';
            }
            $names[mb_strtolower($row['nama'])] = true;

            $harga = $toInt($row['harga']) ?? 0;
            if ($harga > 99999999) {
                $e['harga'] = 'Harga terlalu besar.';
            }
            $kuota = $toInt($row['kuota']);
            if ($kuota === null || $kuota < 1) {
                $e['kuota'] = 'Kuota minimal 1.';
            } elseif ($kuota > 100000) {
                $e['kuota'] = 'Kuota terlalu besar.';
            }

            $mulai = $row['mulai'] !== '' ? $validDate($row['mulai']) : null;
            $akhir = $row['akhir'] !== '' ? $validDate($row['akhir']) : null;
            if ($row['mulai'] !== '' && $mulai === null) {
                $e['mulai'] = 'Tanggal tidak valid.';
            }
            if ($row['akhir'] !== '' && $akhir === null) {
                $e['akhir'] = 'Tanggal tidak valid.';
            } elseif ($mulai && $akhir && $akhir < $mulai) {
                $e['akhir'] = 'Tanggal berakhir harus setelah mulai dijual.';
            } elseif ($akhir && $eventDatesSet && $akhir > $eventEnd) {
                $e['akhir'] = 'Penjualan harus berakhir sebelum acara selesai.';
            }

            if ($e) {
                $errors[$i] = $e;
            }
            $parsed[] = ['nama' => $row['nama'], 'harga' => $harga, 'kuota' => (int) $kuota, 'mulai' => $mulai, 'akhir' => $akhir];
        }

        if (!$rows) {
            $topError = 'Tambahkan minimal satu kategori tiket.';
            $rows[] = ['nama' => '', 'harga' => '', 'kuota' => '', 'mulai' => '', 'akhir' => ''];
        } elseif ($errors) {
            $n = array_sum(array_map('count', $errors));
            $topError = "Lengkapi {$n} kolom wajib sebelum melanjutkan.";
        } else {
            try {
                $db->beginTransaction();
                $db->prepare("DELETE FROM ticket_categories WHERE event_id = :id")->execute(['id' => $event['id']]);
                $ins = $db->prepare("
                    INSERT INTO ticket_categories (event_id, nama_kategori, kuota, wajib_ktm, harga, mulai_dijual, berakhir_dijual, created_at)
                    VALUES (:event_id, :nama, :kuota, FALSE, :harga, :mulai, :akhir, CURRENT_TIMESTAMP + CAST(:urut AS INTEGER) * INTERVAL '1 millisecond')");
                foreach ($parsed as $n => $p) {
                    $ins->execute([
                        'event_id' => $event['id'], 'nama' => $p['nama'], 'kuota' => $p['kuota'], 'harga' => $p['harga'],
                        'mulai' => $p['mulai'], 'akhir' => $p['akhir'], 'urut' => $n,
                    ]);
                }
                $db->prepare("UPDATE events SET kuota = :kuota, updated_at = CURRENT_TIMESTAMP WHERE id = :id")
                    ->execute(['kuota' => array_sum(array_column($parsed, 'kuota')), 'id' => $event['id']]);
                $db->commit();
                EventHelper::redirect($aksi === 'draf'
                    ? BASE_URL . '/event-owner/acara-saya?draf=1'
                    : EventHelper::stepUrl(3, (string) $event['id']));
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('[TIXORA] buat-acara langkah 2: ' . $e->getMessage());
                $topError = 'Kategori gagal disimpan karena kesalahan server. Silakan coba lagi.';
            }
        }
    }
}

$h = [EventHelper::class, 'h'];
$totalKuota = 0;
foreach ($rows as $r) {
    $totalKuota += $toInt($r['kuota']) ?? 0;
}

/** Satu kartu kategori. $i = indeks field; $c = nilai; $e = error per kolom. */
$kartu = static function (string $i, array $c, array $e) use ($h): void {
    $f = static fn(string $k): string => isset($e[$k]) ? ' input-error' : '';
    $m = static function (string $k) use ($e, $h): void {
        if (isset($e[$k])) {
            echo '<p class="help help-error">' . $h($e[$k]) . '</p>';
        }
    };
    ?>
                    <div class="card kategori-card">
                        <div class="row-between" style="margin-bottom:12px">
                            <b style="font-size:16px" class="kategori-judul">Kategori <?= $h($c['nama'] !== '' ? $c['nama'] : 'baru') ?></b>
                            <button type="button" class="button ghost-button small-button hapus-kategori">Hapus</button>
                        </div>
                        <div class="two-fields">
                            <div>
                                <label class="field-label">Nama kategori</label>
                                <input type="text" name="kategori[<?= $h($i) ?>][nama]" class="input nama-input<?= $f('nama') ?>" maxlength="100" value="<?= $h($c['nama']) ?>">
                                <?php $m('nama'); ?>
                            </div>
                            <div>
                                <label class="field-label">Harga (Rp)</label>
                                <input type="text" inputmode="numeric" name="kategori[<?= $h($i) ?>][harga]" class="input harga-input<?= $f('harga') ?>" placeholder="0 = gratis" value="<?= $h($c['harga']) ?>">
                                <?php $m('harga'); ?>
                            </div>
                            <div>
                                <label class="field-label">Kuota</label>
                                <input type="text" inputmode="numeric" name="kategori[<?= $h($i) ?>][kuota]" class="input kuota-input<?= $f('kuota') ?>" value="<?= $h($c['kuota']) ?>">
                                <?php $m('kuota'); ?>
                            </div>
                        </div>
                        <div class="two-fields" style="margin-top:16px">
                            <div>
                                <label class="field-label">Mulai dijual</label>
                                <input type="date" name="kategori[<?= $h($i) ?>][mulai]" class="input<?= $f('mulai') ?>" value="<?= $h($c['mulai']) ?>">
                                <?php $m('mulai'); ?>
                            </div>
                            <div>
                                <label class="field-label">Berakhir</label>
                                <input type="date" name="kategori[<?= $h($i) ?>][akhir]" class="input<?= $f('akhir') ?>" value="<?= $h($c['akhir']) ?>">
                                <?php $m('akhir'); ?>
                            </div>
                        </div>
                    </div>
    <?php
};

EventHelper::renderTop($db, $owner, 2, $event);
?>
                <?php if ($topError): ?>
                    <div class="alert alert-error" style="width:880px;margin-bottom:16px"><?= $h($topError) ?></div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <?= ViewHelper::csrfField() ?>
                    <input type="hidden" name="id" value="<?= $h($event['id']) ?>">
                    <button type="submit" name="aksi" value="lanjut" style="display:none" tabindex="-1" aria-hidden="true"></button>

                    <div class="stack gap-24" style="width:880px">
                        <div class="alert alert-info">Kapasitas total acara dihitung dari jumlah kuota seluruh kategori:
                            <b id="kapasitas-total"><?= number_format($totalKuota, 0, ',', '.') ?> kursi</b>.
                        </div>

                        <div class="stack gap-24" id="kategori-list" data-next="<?= max(100, (int) max(array_map('intval', array_keys($rows))) + 1) ?>">
                            <?php foreach ($rows as $i => $c): $kartu((string) $i, $c, $errors[$i] ?? []); endforeach; ?>
                        </div>

                        <div><button type="button" class="button secondary-button" id="tambah-kategori">+ Tambah kategori</button></div>
                    </div>

                    <div class="wizard-footer" style="width:880px;box-sizing:border-box;margin-top:24px">
                        <button type="submit" name="aksi" value="draf" class="button ghost-button">Simpan draf</button>
                        <div class="row">
                            <a href="<?= EventHelper::stepUrl(1, (string) $event['id']) ?>" class="button secondary-button">Kembali</a>
                            <button type="submit" name="aksi" value="lanjut" class="button primary-button">Lanjut: Rundown</button>
                        </div>
                    </div>
                </form>

                <template id="kategori-tpl"><?php $kartu('__I__', ['nama' => '', 'harga' => '', 'kuota' => '', 'mulai' => '', 'akhir' => ''], []); ?></template>

                <script>
                    (function () {
                        var list = document.getElementById('kategori-list');
                        var total = document.getElementById('kapasitas-total');
                        var next = parseInt(list.dataset.next, 10);
                        var digits = function (v) { return parseInt(String(v).replace(/\D/g, ''), 10) || 0; };

                        function refresh() {
                            var sum = 0;
                            list.querySelectorAll('.kuota-input').forEach(function (i) { sum += digits(i.value); });
                            total.textContent = sum.toLocaleString('id-ID') + ' kursi';
                            var only = list.children.length <= 1;
                            list.querySelectorAll('.hapus-kategori').forEach(function (b) { b.style.visibility = only ? 'hidden' : 'visible'; });
                        }
                        list.addEventListener('input', function (e) {
                            if (e.target.classList.contains('kuota-input')) refresh();
                            if (e.target.classList.contains('nama-input')) {
                                var t = e.target.closest('.kategori-card').querySelector('.kategori-judul');
                                t.textContent = 'Kategori ' + (e.target.value.trim() || 'baru');
                            }
                        });
                        list.addEventListener('focusout', function (e) {
                            if (e.target.classList.contains('harga-input')) {
                                var d = e.target.value.replace(/\D/g, '');
                                e.target.value = d === '' ? '' : parseInt(d, 10).toLocaleString('id-ID');
                            }
                        });
                        list.addEventListener('click', function (e) {
                            var b = e.target.closest('.hapus-kategori');
                            if (b && list.children.length > 1) { b.closest('.kategori-card').remove(); refresh(); }
                        });
                        document.getElementById('tambah-kategori').addEventListener('click', function () {
                            list.insertAdjacentHTML('beforeend', document.getElementById('kategori-tpl').innerHTML.replace(/__I__/g, next++));
                            refresh();
                        });
                        refresh();
                    })();
                </script>
<?php EventHelper::renderBottom(); ?>