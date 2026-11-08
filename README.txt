ECOMPOST - VERSI PEMBARUAN
Sistem Monitoring Kerusakan Sampah Organik untuk Efisiensi Penjadwalan Rantai Pasok Kompos

INSTALASI / PEMBARUAN DI XAMPP
1. Cadangkan folder C:\\xampp\\htdocs\\kompostase dan database sebelum mengganti file.
2. Ekstrak folder kompostase dari ZIP ke C:\\xampp\\htdocs\\kompostase.
3. Pastikan Apache dan MySQL aktif.
4. Periksa config/koneksi.php (default XAMPP: root tanpa password).
5. Jika database lama sudah digunakan, login sebagai Admin lalu buka:
   http://localhost/kompostase/admin/migrasi.php
   Halaman migrasi memeriksa kolom role dan petugas_id serta memperbarui pilihan status.
6. Setelah migrasi berhasil, HAPUS admin/migrasi.php dari server.
7. Untuk instalasi database baru, import database/kompostase.sql.
8. Buka setup.php hanya untuk membuat admin awal jika belum ada. Hapus setup.php setelah digunakan.
9. Login melalui http://localhost/kompostase/login.php.

FITUR DALAM PEMBARUAN
- Login berbasis role Admin dan Petugas.
- Admin: dashboard, monitoring, klasifikasi, penjadwalan otomatis, rantai pasok, laporan, manajemen pengguna.
- Petugas: dashboard tugas pribadi dan pembaruan status Dijadwalkan -> Dalam Perjalanan -> Selesai.
- Penjadwalan otomatis berbasis urutan kondisi, skor, jarak depot, dan batas kapasitas.
- API penerimaan data sensor ESP32.

PENTING TENTANG PENJADWALAN
Penjadwalan yang tersedia adalah heuristik prioritas + jarak depot dengan batas kapasitas kendaraan. Ini belum merupakan optimasi VRP/TSP global. Waktu antar lokasi pada versi ini dialokasikan berjarak 20 menit sebagai perkiraan awal; sesuaikan dengan data waktu tempuh aktual untuk penelitian.

DATA SENSOR
API: POST http://IP-KOMPUTER/kompostase/api/terima_sensor.php
Header: Content-Type: application/json
Header: Authorization: Bearer ISI_DENGAN_API_KEY_YANG_SAMA_DENGAN_CONFIG
Body JSON:
{"lokasi_id":1,"suhu":31.5,"kelembapan":68,"gas":420,"berat":12.5}
Gas merupakan indeks relatif 0-1000, bukan ppm mentah. MQ-135 perlu pemanasan, kalibrasi, dan validasi; jangan menyebut hasil sebagai ppm tanpa kalibrasi yang tepat.

KLASIFIKASI
Formula prototipe saat ini:
skor suhu = clamp((suhu-20)*4,0,100)
skor kelembapan = clamp(kelembapan,0,100)
skor gas = clamp(gas/10,0,100)
skor total = suhu*30% + kelembapan*30% + gas*40%
0-39 Normal; 40-69 Hampir Membusuk; 70-100 Sudah Membusuk.
Ambang dan bobot harus diuji serta dikalibrasi menggunakan pengamatan sampah organik nyata.

KEAMANAN
- Ganti SENSOR_API_KEY pada config/app.php dengan kunci acak yang unik.
- Ubah password admin awal.
- Jangan biarkan setup.php atau admin/migrasi.php tetap tersedia setelah digunakan.
- Gunakan HTTPS dan pembatasan jaringan untuk penerapan di luar komputer lokal.

DATA ILUSTRASI
Nama lokasi dan jarak depot pada SQL bawaan merupakan data contoh. Ganti dengan lokasi dan hasil pengukuran lapangan yang sebenarnya.
