<?php
require_once __DIR__.'/config/app.php';wajib_login();if(($_SESSION['role']??'')==='petugas')go('petugas/dashboard.php');
$latest=$pdo->query("SELECT m.*,l.nama_lokasi FROM monitoring m JOIN lokasi l ON l.id=m.lokasi_id JOIN (SELECT lokasi_id,MAX(id) id FROM monitoring GROUP BY lokasi_id) x ON x.id=m.id ORDER BY m.skor_kebusukan DESC")->fetchAll();
$sum=$pdo->query("SELECT COALESCE(SUM(berat),0) berat,COUNT(DISTINCT lokasi_id) titik FROM (SELECT m.* FROM monitoring m JOIN (SELECT lokasi_id,MAX(id) id FROM monitoring GROUP BY lokasi_id) x ON x.id=m.id) a")->fetch();
$counts=['Normal'=>0,'Hampir Membusuk'=>0,'Sudah Membusuk'=>0];foreach($latest as $r)$counts[$r['status_kebusukan']]++;
$active=(int)$pdo->query("SELECT COUNT(*) FROM penjadwalan WHERE status='Dijadwalkan'")->fetchColumn();
$done=(int)$pdo->query("SELECT COUNT(*) FROM penjadwalan WHERE status='Selesai'")->fetchColumn();
$avg=$latest?round(array_sum(array_column($latest,'skor_kebusukan'))/count($latest)):0;
$pageTitle='Dashboard Utama';$active='dashboard';require __DIR__.'/includes/header.php';
?>
<section class="hero"><div><span class="hero-tag">SMART ORGANIC WASTE MANAGEMENT</span><h2>Monitoring cerdas, pengangkutan lebih terencana. 🌿</h2><p>Identifikasi tingkat kerusakan sampah organik dari sensor, tentukan prioritas, lalu susun jadwal pengangkutan menuju fasilitas kompos.</p><a class="btn white" href="<?=BASE_URL?>/penjadwalan.php">Lihat rekomendasi jadwal →</a></div><div class="hero-icon">♻️</div></section>
<div class="stats">
<div class="stat"><span>Berat sampah terbaru</span><b><?=number_format((float)$sum['berat'],1,',','.')?> <small>kg</small></b><small>Akumulasi pembacaan terakhir per lokasi</small></div>
<div class="stat"><span>Titik monitoring</span><b><?=$sum['titik']?></b><small>Lokasi yang mengirim data</small></div>
<div class="stat"><span>Rata-rata indeks</span><b><?=$avg?><small>/100</small></b><small>Skor pembusukan terbaru</small></div>
<div class="stat"><span>Jadwal aktif</span><b><?=$active?></b><small><?=$done?> pengangkutan selesai</small></div>
</div>
<div class="section-head"><div><h2>Kondisi sampah organik</h2><p>Klasifikasi dari data sensor terbaru per lokasi.</p></div><a class="link" href="<?=BASE_URL?>/klasifikasi.php">Detail klasifikasi →</a></div>
<div class="conditions">
<div class="condition c-normal"><span>✓</span><div><small>NORMAL · SKOR 0–39</small><b><?=$counts['Normal']?></b><em>Prioritas rendah</em></div></div>
<div class="condition c-warning"><span>!</span><div><small>HAMPIR MEMBUSUK · 40–69</small><b><?=$counts['Hampir Membusuk']?></b><em>Prioritas sedang</em></div></div>
<div class="condition c-danger"><span>↑</span><div><small>SUDAH MEMBUSUK · 70–100</small><b><?=$counts['Sudah Membusuk']?></b><em>Prioritas tinggi</em></div></div>
</div>
<div class="panel"><div class="panel-head"><div><h2>Lokasi prioritas pengangkutan</h2><p>Urutan berdasarkan skor pembusukan tertinggi.</p></div><span class="tag">DATA TERBARU</span></div><div class="table-wrap"><table><thead><tr><th>Lokasi</th><th>Berat</th><th>Suhu</th><th>Kelembapan</th><th>Indeks gas</th><th>Skor</th><th>Kondisi</th></tr></thead><tbody>
<?php foreach($latest as $r):?><tr><td><b><?=e($r['nama_lokasi'])?></b><small><?=e($r['waktu_pantau'])?></small></td><td><?=e($r['berat'])?> kg</td><td><?=e($r['suhu'])?>°C</td><td><?=e($r['kelembapan'])?>%</td><td><?=e($r['gas'])?></td><td><b><?=$r['skor_kebusukan']?>/100</b></td><td><?=badge($r['status_kebusukan'])?></td></tr><?php endforeach?>
<?php if(!$latest):?><tr><td colspan="7" class="empty">Belum ada data. Kirim data sensor melalui API untuk mulai monitoring.</td></tr><?php endif?>
</tbody></table></div></div>
<div class="note"><b>Catatan metodologi:</b> indeks pembusukan adalah estimasi berbobot dari suhu (30%), kelembapan (30%), dan indeks gas (40%). Ambang perlu dikalibrasi menggunakan pengamatan sampah organik pada lokasi penelitian.</div>
<?php require __DIR__.'/includes/footer.php';?>