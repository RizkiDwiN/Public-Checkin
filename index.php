<?php
$host = 'localhost';
$user = 'tanaya'; 
$pass = 'Sulanjana11@!';    
$db   = 'antrean'; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Jangan menampilkan pesan $e->getMessage() di production karena bisa membocorkan info server
     header('Content-Type: application/json');
     echo json_encode(['status' => 'error', 'message' => 'Koneksi database bermasalah.']);
     exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in Mandiri Pasien</title>
    <!-- Menggunakan Bootstrap 5.3 sesuai standar interface minimalis -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-checkin { border-radius: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .btn-primary { border-radius: 12px; padding: 12px; font-weight: 600; }
        .form-control { border-radius: 12px; padding: 12px; }
    </style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card card-checkin p-4 bg-white w-100" style="max-width: 450px;">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary">Silakan Check-in</h4>
                <p class="text-muted small">Konfirmasikan kehadiran Anda di ruang tunggu</p>
            </div>
            
            <div id="alertSuccess" class="alert alert-success d-none" role="alert">
                ✓ Berhasil Check-in! Silakan duduk, petugas kami akan segera memanggil Anda.
            </div>

            <form id="formCheckin">
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-semibold">Nama Lengkap Pasien</label>
                    <input type="text" id="nama" class="form-control" placeholder="Contoh: Budi Santoso" required>
                </div>
                <div class="mb-4">
                    <label class="form-label text-secondary small fw-semibold">Tujuan</label>
                    <select class="form-control" id="tujuan" required>
                        <option>=== [Pilih Tujuan] ===</option>
                        <option value="Terapi Okupasi">Terapi Okupasi</option>
                        <option value="Terapi Fisioterapi">Terapi Fisioterapi</option>
                        <option value="Terapi Wicara">Terapi Wicara</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Nyatakan Saya Hadir</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('formCheckin').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = document.getElementById('formCheckin');
            const alertSuccess = document.getElementById('alertSuccess');
            const nama = document.getElementById('nama').value;
            const tujuan = document.getElementById('tujuan').value;

            // Validasi tambahan agar pilihan "=== [Pilih Tujuan] ===" tidak lolos dikirim
            if (tujuan.includes('===')) {
                alert('Silakan pilih tujuan pelayanan Anda terlebih dahulu.');
                return;
            }

            // Mengirim data ke backend secara background (AJAX)
            fetch('simpan_checkin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `nama=${encodeURIComponent(nama)}&tujuan=${encodeURIComponent(tujuan)}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    // 1. Sembunyikan form input dan munculkan pesan sukses
                    form.classList.add('d-none');
                    alertSuccess.classList.remove('d-none');
                    
                    // 2. Set timer: Setelah 4 detik (4000ms), kembalikan ke form awal
                    setTimeout(function() {
                        // Kosongkan semua isi form input/select sebelumnya
                        form.reset(); 
                        
                        // Sembunyikan kembali pesan sukses, dan munculkan form kosongnya
                        alertSuccess.classList.add('d-none');
                        form.classList.remove('d-none');
                    }, 4000); // 4000 milidetik = 4 detik. Anda bisa ubah sesuai kebutuhan.
                } else {
                    alert('Gagal check-in, silakan coba lagi atau hubungi FO.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan jaringan.');
            });
        });
    </script>
</body>
</html>