<?php
// Backend/components/koneksi.php
$host = 'localhost';
$db   = 'laundry_app';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Memaksimalkan Prepared Statements asli
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Jangan tampilkan pesan error asli database ke publik (mencegah Information Disclosure)
    error_log($e->getMessage());
    exit("Terjadi kesalahan pada koneksi database sistem.");
}

// Memulai Session yang aman jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']), // Aktif jika menggunakan HTTPS
        'httponly' => true, // Mencegah akses cookie via JavaScript (XSS Protection)
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Fungsi Generate CSRF Token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fungsi Verifikasi CSRF Token
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        exit("Akses ditolak: CSRF Token tidak valid!");
    }
}

// ==========================================
// Fungsi Helper untuk Outlet Aktif
// ==========================================
function get_nama_outlet_aktif($pdo) {
    if (!isset($_SESSION['id_outlet'])) {
        return 'Outlet Utama';
    }
    try {
        $stmt = $pdo->prepare("SELECT nama FROM tb_outlet WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['id_outlet']]);
        $result = $stmt->fetch();
        return $result['nama'] ?? 'Outlet Utama';
    } catch (PDOException $e) {
        return 'Outlet Utama';
    }
}

// ==========================================
// Fungsi Satpam Hak Akses Role
// ==========================================
function restrict_access($allowed_roles = []) {
    if (!isset($_SESSION['role'])) {
        header("Location: login.php");
        exit();
    }
    
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        echo "<script>alert('Akses Ditolak! Anda tidak memiliki izin ke halaman ini.'); window.location='dashboard.php';</script>";
        exit();
    }
}
?>