<?php
// Backend/pages/transaksi_proses.php
require_once __DIR__ . '/../components/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Silakan login terlebih dahulu!");
    exit();
}

// Cegah Kasir masuk ke Backend, tendang otomatis ke Frontend
if (isset($_SESSION['role']) && $_SESSION['role'] === 'kasir') {
    header("Location: ../frontend/home.php");
    exit();
}

restrict_access(['admin', 'owner']);

$nama_user = $_SESSION['nama'] ?? 'Administrator';
$role_user = $_SESSION['role'] ?? 'admin';
$id_outlet_user = $_SESSION['id_outlet'] ?? 1;

// Ambil informasi nama outlet aktif user
try {
    $stmt_outlet = $pdo->prepare("SELECT nama FROM tb_outlet WHERE id = :id");
    $stmt_outlet->execute(['id' => $id_outlet_user]);
    $outlet_active = $stmt_outlet->fetch();
    $nama_outlet = $outlet_active['nama'] ?? 'Outlet Utama';
} catch (PDOException $e) {
    $nama_outlet = 'Outlet Utama';
}

$pesan_sukses = '';
$pesan_error = '';

// Tangkap pesan dari URL (jika ada redirect dari transaksi_aksi.php)
if (isset($_GET['success'])) {
    $pesan_sukses = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $pesan_error = htmlspecialchars($_GET['error']);
}

// Ambil parameter filter
$periode = $_GET['periode'] ?? '';
$tahun = $_GET['tahun'] ?? date('Y');
$status_filter = $_GET['status'] ?? '';

// Fungsi Bantuan untuk Pencatatan Activity Log ke Database
function catat_log($pdo, $username, $aktivitas) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (username, activity, created_at) VALUES (:username, :activity, NOW())");
        $stmt->execute([
            'username' => $username,
            'activity' => $aktivitas
        ]);
    } catch (PDOException $e) {
        // Abaikan jika tabel log mengalami kendala
    }
}

// 1. Proses Edit / Update Transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_transaksi'])) {
    $id_transaksi   = $_POST['id_transaksi'];
    $id_member      = $_POST['id_member'];
    $id_paket       = $_POST['id_paket'];
    $qty            = floatval($_POST['qty']);
    $biaya_tambahan = floatval($_POST['biaya_tambahan'] ?? 0);
    $diskon         = abs(floatval($_POST['diskon'] ?? 0));
    $pajak          = floatval($_POST['pajak'] ?? 0);
    $dibayar        = $_POST['dibayar'];
    
    $tgl_bayar_sql  = ($dibayar === 'dibayar') ? ", tgl_bayar = COALESCE(tgl_bayar, NOW())" : ", tgl_bayar = NULL";

    try {
        $stmt_up = $pdo->prepare("UPDATE tb_transaksi SET id_member = :id_member, biaya_tambahan = :biaya_tambahan, diskon = :diskon, pajak = :pajak, dibayar = :dibayar $tgl_bayar_sql WHERE id = :id");
        $stmt_up->execute([
            'id_member'      => $id_member,
            'biaya_tambahan' => $biaya_tambahan,
            'diskon'         => $diskon,
            'pajak'          => $pajak,
            'dibayar'        => $dibayar,
            'id'             => $id_transaksi
        ]);

        $stmt_dt_up = $pdo->prepare("UPDATE tb_detail_transaksi SET id_paket = :id_paket, qty = :qty WHERE id_transaksi = :id_transaksi");
        $stmt_dt_up->execute([
            'id_paket'     => $id_paket,
            'qty'          => $qty,
            'id_transaksi' => $id_transaksi
        ]);

        $pesan_sukses = "Data transaksi berhasil diperbarui!";
        catat_log($pdo, $nama_user, "Memperbarui data transaksi ID: $id_transaksi");

    } catch (PDOException $e) {
        $pesan_error = "Gagal memperbarui transaksi: " . $e->getMessage();
    }
}

// 2. Proses Update Status Transaksi
if (isset($_GET['ubah_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status_baru = $_GET['ubah_status'];
    try {
        if ($status_baru === 'diambil') {
            $stmt = $pdo->prepare("UPDATE tb_transaksi SET status = :status, dibayar = 'dibayar', tgl_bayar = NOW() WHERE id = :id");
        } else {
            $stmt = $pdo->prepare("UPDATE tb_transaksi SET status = :status WHERE id = :id");
        }
        $stmt->execute(['status' => $status_baru, 'id' => $id]);
        
        catat_log($pdo, $nama_user, "Mengubah status transaksi ID $id menjadi $status_baru");
        header("Location: transaksi.php?success=Status transaksi berhasil diperbarui");
        exit();
    } catch (PDOException $e) {
        $pesan_error = "Gagal memperbarui status transaksi.";
    }
}

// Ambil data member, paket, dan transaksi dengan filter
try {
    $list_member = $pdo->query("SELECT * FROM tb_member ORDER BY nama ASC")->fetchAll();
    $list_paket  = $pdo->query("SELECT tb_paket.*, tb_outlet.nama AS nama_outlet FROM tb_paket JOIN tb_outlet ON tb_paket.id_outlet = tb_outlet.id ORDER BY tb_paket.nama_paket ASC")->fetchAll();
    
    $query = "SELECT t.*, 
              m.id AS id_member_asli,
              COALESCE(m.nama, 'Member Umum / Terhapus') AS nama_member, 
              COALESCE(u.nama, 'Administrator') AS nama_user, 
              dt.id_paket AS id_paket_asli,
              COALESCE(pk.nama_paket, 'Paket Manual') AS nama_paket, 
              COALESCE(pk.harga, 0) AS harga_paket,
              COALESCE(dt.qty, 1) AS qty
              FROM tb_transaksi t
              LEFT JOIN tb_member m ON t.id_member = m.id
              LEFT JOIN tb_user u ON t.id_user = u.id
              LEFT JOIN tb_detail_transaksi dt ON t.id = dt.id_transaksi
              LEFT JOIN tb_paket pk ON dt.id_paket = pk.id WHERE 1=1";
    
    $params = [];

    if (!empty($status_filter)) {
        $query .= " AND t.status = :status";
        $params['status'] = $status_filter;
    }

    if (!empty($periode)) {
        $map_bulan = [
            1 => ['-01-01', '-03-31'],
            2 => ['-04-01', '-06-30'],
            3 => ['-07-01', '-09-30'],
            4 => ['-10-01', '-12-31']
        ];
        
        if (isset($map_bulan[$periode])) {
            $query .= " AND t.tgl BETWEEN :start_date AND :end_date";
            $params['start_date'] = $tahun . $map_bulan[$periode][0] . ' 00:00:00';
            $params['end_date'] = $tahun . $map_bulan[$periode][1] . ' 23:59:59';
        }
    }

    $query .= " ORDER BY t.id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $list_transaksi = $stmt->fetchAll();

} catch (PDOException $e) {
    $list_member = [];
    $list_paket  = [];
    $list_transaksi = [];
}