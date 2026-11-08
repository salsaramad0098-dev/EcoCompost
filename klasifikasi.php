<?php
require_once __DIR__.'/config/app.php';wajib_login();
$rows=$pdo->query("SELECT m.*,l.nama_lokasi FROM monitoring m JOIN lokasi l ON l.id=m.lokasi_id JOIN (SELECT lokasi_id,MAX(id) id FROM monitoring GROUP BY lokasi_id) x ON x.id=m.id ORDER BY m.skor_kebusukan DESC")->fetchAll();
$pageTitle='Klasifikasi Pembusukan';$active='klasifikasi';require __DIR__.'/includes/header.php';?>
<div class="section-head"><div><h2>Hasil klasifikasi otomatis</h2><p>Sistem mengelompokkan kondisi sampah berdasarkan pembacaan sensor terbaru setiap lokasi.</p></div></div>
<div class="class-grid">
<div class="class-box c-normal"><h3>01 · NORMAL</h3><b>0–39</b><p>Belum menunjukkan indikasi pembusukan tinggi. Masuk jadwal pengumpulan rutin.</p></div>
<div class="class-box c-warning"><h3>02 · HAMPIR MEMBUSUK</h3><b>40–69</b><p>Mulai mengalami dekomposisi. Direkomendasikan masuk jadwal angkut terdekat.</p></div>
<div class="class-box c-danger"><h3>03 · SUDAH MEMBUSUK</h3><b>70–100</b><p>Indikasi pembusukan tinggi. Menjadi prioritas utama untuk pengangkutan.</p></div>
</div>
<section class="panel"><div class="panel-head"><div><h2>Klasifikasi per lokasi</h2><p>Skor dihitung otomatis saat data sensor diterima.</p></div></div><div class="table-wrap"><table><thead><tr><th>Lokasi</th><th>Waktu ukur</th><th>Suhu</th><th>Kelembapan</th><th>Gas</th><th>Skor</th><th>Status</th><th>Rekomendasi</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><b><?=e($r['nama_lokasi'])?></b></td><td><?=e($r['waktu_pantau'])?></td><td><?=e($r['suhu'])?>°C</td><td><?=e($r['kelembapan'])?>%</td><td><?=e($r['gas'])?></td><td><b><?=$r['skor_kebusukan']?></b></td><td><?=badge($r['status_kebusukan'])?></td><td><?= $r['status_kebusukan']==='Sudah Membusuk'?'Segera angkut':($r['status_kebusukan']==='Hampir Membusuk'?'Jadwalkan terdekat':'Pengumpulan rutin')?></td></tr><?php endforeach?><?php if(!$rows):?><tr><td colspan="8" class="empty">Belum ada data klasifikasi.</td></tr><?php endif?></tbody></table></div></section>
<div class="note"><b>Formula awal:</b> skor = (skor suhu × 30%) + (kelembapan × 30%) + (indeks gas × 40%). Skor suhu = (suhu − 20) × 4, dibatasi 0–100; indeks gas = gas/10, dibatasi 0–100. Ini parameter prototipe yang perlu divalidasi dan dikalibrasi.</div>
<?php require __DIR__.'/includes/footer.php';?>