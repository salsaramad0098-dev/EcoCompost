<?php
require_once __DIR__.'/config/app.php';wajib_login();
$loc=$pdo->query("SELECT l.*,m.berat,m.status_kebusukan,m.skor_kebusukan,m.waktu_pantau FROM lokasi l LEFT JOIN monitoring m ON m.id=(SELECT MAX(mm.id) FROM monitoring mm WHERE mm.lokasi_id=l.id) ORDER BY l.nama_lokasi")->fetchAll();
$tot=(float)$pdo->query("SELECT COALESCE(SUM(berat),0) FROM (SELECT m.berat FROM monitoring m JOIN (SELECT lokasi_id,MAX(id) id FROM monitoring GROUP BY lokasi_id) x ON x.id=m.id) a")->fetchColumn();
$scheduled=(float)$pdo->query("SELECT COALESCE(SUM(m.berat),0) FROM penjadwalan p JOIN monitoring m ON m.id=p.monitoring_id WHERE p.status='Dijadwalkan'")->fetchColumn();
$pageTitle='Manajemen Rantai Pasok';$active='rantai';require __DIR__.'/includes/header.php';?>
<div class="section-head"><div><h2>Alur rantai pasok sampah organik</h2><p>Memantau perpindahan material dari sumber sampah hingga fasilitas kompos.</p></div></div>
<div class="flow">
<div class="flow-step"><span>01</span><b>Sumber sampah</b><small>Pasar · sekolah · permukiman</small></div><div class="flow-arrow">→</div>
<div class="flow-step"><span>02</span><b>Monitoring IoT</b><small>Suhu · kelembapan · gas</small></div><div class="flow-arrow">→</div>
<div class="flow-step"><span>03</span><b>Pengumpulan</b><small>Prioritas dan jadwal angkut</small></div><div class="flow-arrow">→</div>
<div class="flow-step"><span>04</span><b>Pengolahan kompos</b><small>Penerimaan dan proses kompos</small></div>
</div>
<div class="stats three"><div class="stat"><span>Volume pantauan terbaru</span><b><?=number_format($tot,1,',','.')?> <small>kg</small></b><small>Jumlah berat terbaru setiap titik</small></div><div class="stat"><span>Volume jadwal aktif</span><b><?=number_format($scheduled,1,',','.')?> <small>kg</small></b><small>Berat yang direncanakan untuk diangkut</small></div><div class="stat"><span>Titik sumber</span><b><?=count($loc)?></b><small>Lokasi yang terdaftar di sistem</small></div></div>
<section class="panel"><div class="panel-head"><div><h2>Status titik sumber sampah</h2><p>Daftar lokasi dan kondisi monitoring terbaru.</p></div></div><div class="table-wrap"><table><thead><tr><th>Lokasi sumber</th><th>Alamat</th><th>Jarak depot</th><th>Berat terbaru</th><th>Kondisi</th><th>Waktu pantau</th></tr></thead><tbody><?php foreach($loc as $l):?><tr><td><b><?=e($l['nama_lokasi'])?></b></td><td><?=e($l['alamat'])?></td><td><?=number_format((float)$l['jarak_depot_km'],1)?> km</td><td><?=$l['berat']===null?'—':kg($l['berat'])?></td><td><?=$l['status_kebusukan']?badge($l['status_kebusukan']):'Belum dipantau'?></td><td><?=e($l['waktu_pantau']??'—')?></td></tr><?php endforeach?></tbody></table></div></section>
<div class="note"><b>Catatan:</b> halaman ini memvisualisasikan alur dan status sumber. Untuk pelacakan aktual kendaraan, perlu ditambahkan GPS armada dan pencatatan penerimaan berat di fasilitas kompos.</div>
<?php require __DIR__.'/includes/footer.php';?>