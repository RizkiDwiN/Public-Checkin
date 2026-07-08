<?php

header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta');

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    $tujuan = $_POST['tujuan'] ?? '';
    
    if (!empty($nama) && !empty($tujuan) && !str_contains($tujuan, '===')) {
        
        // Membersihkan input dasar dari tag HTML berbahaya (XSS Protection)
        $nama_clean = htmlspecialchars(strip_tags($nama));
        $tujuan_clean = htmlspecialchars(strip_tags($tujuan));
        $waktu = date('H:i:s');
        $tanggal = date('Y-m-d'); 
        $status = 'Baru';
        
        try {
            // Prepared Statement PDO (Mencegah SQL Injection)
            $stmt = $pdo->prepare("INSERT INTO antrean (nama, tujuan, waktu, tanggal, status) VALUES (:nama, :tujuan, :waktu, :tanggal, :status)");
            
            $stmt->execute([
                ':nama' => $nama_clean,
                ':tujuan' => $tujuan_clean,
                ':waktu' => $waktu,
                ':tanggal' => $tanggal,
                ':status' => $status
            ]);
            
            echo json_encode(['status' => 'success']);
            exit;
            
        } catch (\PDOException $e) {
            // Log error ke file server (jika perlu), kirim status error ke client
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data antrean.']);
            exit;
        }
    }
}
echo json_encode(['status' => 'error']);
?>