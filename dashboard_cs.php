<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Notifikasi CS</title>
    
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d6efd">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f3f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .dashboard-panel { border-radius: 35px; background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 30px; }
        .table { vertical-align: middle; }
        .bg-new-patient { animation: flashGreen 2s ease-in-out; }
        @keyframes flashGreen {
            0% { background-color: #d1e7dd; }
            100% { background-color: transparent; }
        }
        .live-clock { font-size: 1.1rem; font-weight: 600; color: #495057; background: #e9ecef; padding: 8px 18px; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="container-fluid py-4 px-4">
        <div class="d-flex justify-content-between align-items-center mb-4 px-2">
            <div>
                <h3 class="fw-bold mb-0 text-dark">Check-In Notifier Dashboard</h3>
                <p class="text-muted small mb-0">Memantau kehadiran pasien di area ruang tunggu secara real-time</p>
            </div>
            <div id="jamDigital" class="live-clock shadow-sm">Memuat waktu...</div>
        </div>

        <div class="d-flex justify-content-end align-items-center mb-3 px-2">
            <button id="btnInstallApp" class="btn btn-outline-primary btn-sm me-2 fw-semibold rounded-pill px-3 d-none">
                📥 Install Aplikasi Dashboard
            </button>
            <button id="btnAudio" class="btn btn-warning btn-sm me-3 fw-semibold rounded-pill px-3" onclick="aktifkanAudio()">
                🔊 Aktifkan Suara Notifikasi
            </button>
            <span class="badge bg-primary px-3 py-2 rounded-pill">Status: Monitoring Aktif</span>
        </div>

        <div class="dashboard-panel">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 px-4 text-secondary small" style="width: 20%">Waktu Hadir</th>
                            <th class="py-3 text-secondary small" style="width: 40%">Nama Pasien</th>
                            <th class="py-3 text-secondary small" style="width: 25%">Tujuan</th>
                            <th class="py-3 text-center text-secondary small" style="width: 15%">Status</th>
                        </tr>
                    </thead>
                    <tbody id="tabelAntrean">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">Belum ada pasien yang check-in hari ini.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <audio id="notifSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-600.wav" preload="auto"></audio>

    <script>
        let totalPasienLama = 0;
        let audioDiizinkan = false;
        let tanggalHariIni = ""; 

        // JAM DIGITAL REAL-TIME
        function updateJamDigital() {
            const sekarang = new Date();
            const daftarHari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
            const daftarBulan = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            
            const hari = daftarHari[sekarang.getDay()];
            const tanggal = String(sekarang.getDate()).padStart(2, '0');
            const bulan = daftarBulan[sekarang.getMonth()];
            const tahun = sekarang.getFullYear();
            
            const jam = String(sekarang.getHours()).padStart(2, '0');
            const menit = String(sekarang.getMinutes()).padStart(2, '0');
            const detik = String(sekarang.getSeconds()).padStart(2, '0');
            
            document.getElementById('jamDigital').innerHTML = `📅 ${hari}, ${tanggal} ${bulan} ${tahun} | 🕒 ${jam}:${menit}:${detik}`;
            
            const bulanAngka = String(sekarang.getMonth() + 1).padStart(2, '0');
            const tanggalFormat = `${tanggal}-${bulanAngka}-${tahun}`;
            
            if (tanggalHariIni !== "" && tanggalHariIni !== tanggalFormat) {
                totalPasienLama = 0; 
                cekAntreanBaru();    
            }
            tanggalHariIni = tanggalFormat;
        }
        setInterval(updateJamDigital, 1000);
        updateJamDigital();

        // AUDIO BYPASS
        function aktifkanAudio() {
            const sound = document.getElementById('notifSound');
            sound.play().then(() => {
                sound.pause();
                sound.currentTime = 0;
                audioDiizinkan = true;
                const btn = document.getElementById('btnAudio');
                btn.className = "btn btn-success btn-sm me-3 fw-semibold rounded-pill px-3";
                btn.innerHTML = "✅ Suara Aktif";
                btn.disabled = true;
            }).catch(err => console.log(err));
        }

        // FETCH DATA REAL-TIME via AJAX (Terhubung ke ambil_antrean.php dengan database PDO)
        function cekAntreanBaru() {
            fetch(`ambil_antrean.php?tanggal=${tanggalHariIni}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('tabelAntrean');
                
                // Menangani jika response mengembalikan error JSON dari PDO catch
                if (data.status === 'error') {
                    console.error('Database Error:', data.message);
                    return;
                }
                
                if(data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-5">Belum ada pasien yang check-in hari ini (${tanggalHariIni}).</td></tr>`;
                    totalPasienLama = 0;
                    return;
                }

                // Bunyikan audio jika ada penambahan record baru di database
                if (data.length > totalPasienLama && totalPasienLama !== 0) {
                    if (audioDiizinkan) {
                        document.getElementById('notifSound').play().catch(e => console.log(e));
                    }
                }

                let html = '';
                data.forEach((pasien, index) => {
                    // Karena SQL query ORDER BY id DESC, data terbaru otomatis di indeks 0 (paling atas)
                    const isNew = (data.length > totalPasienLama && index === 0 && totalPasienLama !== 0) ? 'bg-new-patient' : '';
                    
                    // Konversi label status dinamis dari database
                    const statusLabel = pasien.status === 'Baru' ? 'Hadir di Lokasi' : pasien.status;

                    html += `
                        <tr class="${isNew}">
                            <td class="py-3 px-4 fw-bold text-primary">${pasien.tanggal} | ${pasien.waktu}</td>
                            <td class="py-3 fw-semibold text-dark">${pasien.nama}</td>
                            <td class="py-3 text-muted">${pasien.tujuan}</td>
                            <td class="py-3 text-center"><span class="badge bg-success rounded-pill px-3 py-1">${statusLabel}</span></td>
                        </tr>
                    `;
                });

                tbody.innerHTML = html;
                totalPasienLama = data.length; 
            })
            .catch(error => console.error('Gagal mengambil data antrean:', error));
        }
        
        // Polling setiap 3 detik untuk mengecek data baru secara background (Real-time Feel)
        setInterval(cekAntreanBaru, 3000);
        cekAntreanBaru();


        // ==========================================
        // SCRIPT REGISTRASI PWA & TOMBOL INSTALASI
        // ==========================================
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
            .then(() => console.log("Service Worker terdaftar dengan aman!"))
            .catch(err => console.log("Gagal daftar Service Worker:", err));
        }

        let deferredPrompt;
        const btnInstallApp = document.getElementById('btnInstallApp');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            btnInstallApp.classList.remove('d-none');
        });

        btnInstallApp.addEventListener('click', () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User menerima instalasi aplikasi');
                        btnInstallApp.classList.add('d-none');
                    }
                    deferredPrompt = null;
                });
            }
        });

        window.addEventListener('appinstalled', () => {
            console.log('Aplikasi sukses terpasang di desktop!');
            btnInstallApp.classList.add('d-none');
        });
    </script>
</body>
</html>