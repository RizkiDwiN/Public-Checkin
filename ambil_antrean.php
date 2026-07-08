<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST");
header('Content-Type: application/json');

date_default_timezone_set('Asia/Jakarta');

$host = getenv('DB_HOST');
$user = getenv('DB_USER'); 
$pass = getenv('DB_PASS');    
$db   = getenv('DB_NAME'); 
$port = getenv('DB_PORT');
$charset = 'utf8mb4';

// Masukkan variabel port ke dalam string DSN menggunakan port=$port
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     header('Content-Type: application/json');
     // Tips: Jika masih error saat setup awal, Anda bisa ubah teks di bawah menjadi $e->getMessage() untuk melacak masalahnya
     echo json_encode(['status' => 'error', 'message' => 'Koneksi database bermasalah.']);
     exit;
}


$tanggal_input = $_GET['tanggal'] ?? date('d-m-Y');

// Konversi format tanggal dari JS (DD-MM-YYYY) ke MySQL (YYYY-MM-DD)
$date_object = DateTime::createFromFormat('d-m-Y', $tanggal_input);
$tanggal_mysql = $date_object ? $date_object->format('Y-m-d') : date('Y-m-d');

try {
    // Prepared Statement untuk pengamanan maksimal
    $stmt = $pdo->prepare("SELECT id, nama, tujuan, waktu, DATE_FORMAT(tanggal, '%d-%m-%Y') AS tanggal_format, status 
                           FROM antrean 
                           WHERE tanggal = :tanggal 
                           ORDER BY id DESC");
    
    $stmt->execute([':tanggal' => $tanggal_mysql]);
    $data_antrean = $stmt->fetchAll();
    
    // Format output data agar seragam dengan JavaScript Dashboard Anda
    $result = [];
    foreach ($data_antrean as $row) {
        $result[] = [
            'id' => $row['id'],
            'nama' => $row['nama'],
            'tujuan' => $row['tujuan'],
            'waktu' => $row['waktu'],
            'tanggal' => $row['tanggal_format'],
            'status' => $row['status']
        ];
    }
    
    echo json_encode($result);
    
} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal memuat data.']);
}
?>
