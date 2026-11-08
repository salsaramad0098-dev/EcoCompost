<?php
require_once __DIR__.'/config/app.php';
wajib_login();
if(($_SESSION['role']??'')!=='admin'){http_response_code(403);exit('Halaman ini khusus Admin.');}

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['act']??'')==='generate'){
    $kapasitas=(float)($_POST['kapasitas']??0);
    $petugasId=(int)($_POST['petugas_id']??0);
    $tanggal=str_replace('T',' ',$_POST['tanggal']??'');
    $cek=$pdo->prepare("SELECT id,nama FROM users WHERE id=? AND role='petugas'");
    $cek->execute([$petugasId]); $petugas=$cek->fetch();
    if($kapasitas<=0 || !$petugas || $tanggal===''){
        flash('error','Pilih petugas, kapasitas armada, dan tanggal pengangkutan yang valid.');
        go('penjadwalan.php');
    }
    $items=$pdo->query("
        SELECT m.*,l.nama_lokasi,l.jarak_depot_km
        FROM monitoring m JOIN lokasi l ON l.id=m.lokasi_id
        JOIN (SELECT lokasi_id,MAX(id) id FROM monitoring GROUP BY lokasi_id) x ON x.id=m.id
        WHERE NOT EXISTS (
            SELECT 1 FROM penjadwalan p
            WHERE p.monitoring_id=m.id AND p.status IN ('Dijadwalkan','Dalam Perjalanan')
        )
        ORDER BY FIELD(m.status_kebusukan,'Sudah Membusuk','Hampir Membusuk','Normal'),
                 m.skor_kebusukan DESC,l.jarak_depot_km ASC
    ")->fetchAll();

    $pilihan=[]; $total=0.0;
    foreach($items as $item){
        $berat=(float)$item['berat'];
        if($berat>0 && $berat<=$kapasitas && $total+$berat<=$kapasitas){
            $pilihan[]=$item; $total+=$berat;
        }
    }
    if(!$pilihan){flash('error','Tidak ada lokasi yang memenuhi kapasitas kendaraan atau semua data terbaru sudah dijadwalkan.');go('penjadwalan.php');}

    $pdo->beginTransaction();
    try{
        $ins=$pdo->prepare("INSERT INTO penjadwalan
            (monitoring_id,petugas_id,tanggal_angkut,petugas,prioritas,urutan_rute,kapasitas_armada,status)
            VALUES(?,?,?,?,?,?,?,'Dijadwalkan')");
        foreach($pilihan as $i=>$item){
            $prioritas=$item['status_kebusukan']==='Sudah Membusuk'?'Tinggi':($item['status_kebusukan']==='Hampir Membusuk'?'Sedang':'Rendah');
            $jam=date('Y-m-d H:i:s',strtotime($tanggal)+($i*20*60));
            $ins->execute([$item['id'],$petugasId,$jam,$petugas['nama'],$prioritas,$i+1,$kapasitas]);
        }
        $pdo->commit();
        flash('success','Jadwal otomatis berhasil dibuat untuk '.count($pilihan).' lokasi. Total muatan '.number_format($total,1,',','.').' kg. Sistem mengurutkan kondisi, skor, dan jarak depot.');
    }catch(Throwable $e){
        $pdo->rollBack();
        flash('error','Gagal membuat jadwal. Pastikan migrasi database sudah dijalankan. '.$e->getMessage());
    }
    go('penjadwalan.php');
}

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['act']??'')==='status'){
    $id=(int)($_POST['id']??0); $status=$_POST['status']??'';
    if(in_array($status,['Dijadwalkan','Dalam Perjalanan','Selesai','Dibatalkan'],true)){
        $q=$pdo->prepare("UPDATE penjadwalan SET status=? WHERE id=?");
        $q->execute([$status,$id]); flash('success','Status jadwal diperbarui.');
    }
    go('penjadwalan.php');
}

$latest=$pdo->query("SELECT m.*,l.nama_lokasi,l.jarak_depot_km
 FROM monitoring m JOIN lokasi l ON l.id=m.lokasi_id
 JOIN (SELECT lokasi_id,MAX(id) id FROM monitoring GROUP BY lokasi_id) x ON x.id=m.id
 WHERE NOT EXISTS (SELECT 1 FROM penjadwalan p WHERE p.monitoring_id=m.id AND p.status IN ('Dijadwalkan','Dalam Perjalanan'))
 ORDER BY FIELD(m.status_kebusukan,'Sudah Membusuk','Hampir Membusuk','Normal'),m.skor_kebusukan DESC,l.jarak_depot_km ASC")->fetchAll();

$plans=$pdo->query("SELECT p.*,m.berat,m.status_kebusukan,l.nama_lokasi,l.jarak_depot_km,u.nama nama_akun
 FROM penjadwalan p JOIN monitoring m ON m.id=p.monitoring_id
 JOIN lokasi l ON l.id=m.lokasi_id LEFT JOIN users u ON u.id=p.petugas_id
 ORDER BY p.tanggal_angkut DESC,p.urutan_rute ASC")->fetchAll();

$petugas=$pdo->query("SELECT id,nama FROM users WHERE role='petugas' ORDER BY nama")->fetchAll();
$pageTitle='Penjadwalan Otomatis';$active='jadwal';require __DIR__.'/includes/header.php';
?>
<div class="section-head"><div><h2>Mesin penjadwalan otomatis</h2><p>Sistem menyusun urutan lokasi berdasarkan kondisi pembusukan, skor, jarak depot, dan batas kapasitas armada.</p></div><span class="tag">AUTO SCHEDULING</span></div>
<div class="content-grid">
<section class="panel">
<div class="panel-head"><div><h2>Generate jadwal pengangkutan</h2><p>Admin menentukan kapasitas kendaraan, petugas, dan waktu mulai. Urutan lokasi dihitung sistem.</p></div></div>
<form method="post" class="form">
<input type="hidden" name="act" value="generate">
<label>Kapasitas kendaraan (kg)</label><input type="number" name="kapasitas" min="1" step="0.1" placeholder="Contoh: 150" required>
<label>Petugas lapangan</label><select name="petugas_id" required><option value="">-- Pilih petugas --</option><?php foreach($petugas as $pt):?><option value="<?=$pt['id']?>"><?=e($pt['nama'])?></option><?php endforeach?></select>
<label>Tanggal dan waktu mulai</label><input type="datetime-local" name="tanggal" min="<?=date('Y-m-d\TH:i')?>" required>
<button class="btn">Buat jadwal otomatis →</button>
</form>
<div class="note"><b>Aturan sistem:</b> lokasi dengan status membusuk didahulukan, kemudian hampir membusuk dan normal. Skor pembusukan dan jarak depot menjadi faktor pengurutan. Berat total tidak boleh melebihi kapasitas kendaraan. Ini heuristik prioritas, bukan klaim optimasi rute global.</div>
</section>
<section class="panel">
<div class="panel-head"><div><h2>Antrian lokasi</h2><p>Lokasi yang belum masuk jadwal aktif.</p></div></div>
<?php foreach($latest as $i=>$r):?><div class="route-row"><span class="num"><?=$i+1?></span><div class="route-info"><b><?=e($r['nama_lokasi'])?></b><small><?=e($r['status_kebusukan'])?> · <?=kg($r['berat'])?> · <?=number_format((float)$r['jarak_depot_km'],1)?> km</small></div><?=badge($r['prioritas']??($r['status_kebusukan']==='Sudah Membusuk'?'Tinggi':($r['status_kebusukan']==='Hampir Membusuk'?'Sedang':'Rendah')))?></div><?php endforeach?>
<?php if(!$latest):?><div class="empty">Belum ada lokasi yang menunggu jadwal. Pastikan data sensor sudah masuk.</div><?php endif?>
</section></div>
<section class="panel"><div class="panel-head"><div><h2>Jadwal dan rute yang dihasilkan</h2><p>Admin dapat memantau serta memperbarui status pengangkutan.</p></div></div>
<div class="table-wrap"><table><thead><tr><th>Waktu</th><th>Urutan</th><th>Lokasi</th><th>Berat</th><th>Prioritas</th><th>Petugas</th><th>Status</th><th>Perbarui</th></tr></thead><tbody>
<?php foreach($plans as $p):?><tr><td><?=e($p['tanggal_angkut'])?></td><td><?=$p['urutan_rute']?></td><td><b><?=e($p['nama_lokasi'])?></b><small><?=number_format((float)$p['jarak_depot_km'],1)?> km dari depot</small></td><td><?=kg($p['berat'])?></td><td><?=badge($p['prioritas'])?></td><td><?=e($p['nama_akun']??$p['petugas']??'Belum ditugaskan')?></td><td><?=badge($p['status'])?></td><td><form method="post" class="inline"><input type="hidden" name="act" value="status"><input type="hidden" name="id" value="<?=$p['id']?>"><select name="status"><?php foreach(['Dijadwalkan','Dalam Perjalanan','Selesai','Dibatalkan'] as $st):?><option value="<?=e($st)?>" <?=$p['status']===$st?'selected':''?>><?=e($st)?></option><?php endforeach?></select><button class="btn small">Simpan</button></form></td></tr><?php endforeach?>
<?php if(!$plans):?><tr><td colspan="8" class="empty">Belum ada jadwal pengangkutan.</td></tr><?php endif?>
</tbody></table></div></section>
<?php require __DIR__.'/includes/footer.php';?>
