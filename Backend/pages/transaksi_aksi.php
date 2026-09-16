<?php
// Backend/pages/transaksi_aksi.php
require_once __DIR__ . '/../components/koneksi.php';

// Pastikan hanya user yang sudah login bisa mengakses
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    // Ambil dan sanitasi input data transaksi
    $id_outlet   = filter_var($_POST['id_outlet'], FILTER_SANITIZE_NUMBER_INT);
    $kode_invoice= htmlspecialchars(trim($_POST['kode_invoice']), ENT_QUOTES, 'UTF-8');
    $id_member   = filter_var($_POST['id_member'], FILTER_SANITIZE_NUMBER_INT);
    $id_user     = $_SESSION['user_id'];
    
    // Data detail transaksi
    $id_paket    = filter_var($_POST['id_paket'], FILTER_SANITIZE_NUMBER_INT);
    $qty         = filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $keterangan  = htmlspecialchars(trim($_POST['keterangan']), ENT_QUOTES, 'UTF-8');

    try {
        $pdo->beginTransaction();

        // 1. Insert ke tabel tb_transaksi menggunakan Prepared Statement
        $sqlTransaksi = "INSERT INTO tb_transaksi (id_outlet, kode_invoice, id_member, tgl, batas_waktu, tgl_bayar, biaya_tambahan, diskon, pajak, status, dibayar, id_user) 
                         VALUES (:id_outlet, :kode_invoice, :id_member, NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), NULL, 0, 0, 0, 'baru', 'belum_dibayar', :id_user)";
        
        $stmt = $pdo->prepare($sqlTransaksi);
        $stmt->execute([
            'id_outlet'    => $id_outlet,
            'kode_invoice' => $kode_invoice,
            'id_member'    => $id_member,
            'id_user'      => $id_user
        ]);

        // Ambil ID transaksi yang baru saja dimasukkan
        $id_transaksi = $pdo->lastInsertId();

        // 2. Memanggil Stored Procedure yang sudah kamu buat: sp_detail_transaksi
        // Pastikan parameter stored procedure sesuai dengan struktur di phpMyAdmin kamu
        $stmtProc = $pdo->prepare("CALL sp_detail_transaksi(:id_transaksi, :id_paket, :qty, :keterangan)");
        $stmtProc->execute([
            'id_transaksi' => $id_transaksi,
            'id_paket'     => $id_paket,
            'qty'          => $qty,
            'keterangan'   => $keterangan
        ]);

        $pdo->commit();
        header("Location: transaksi.php?success=Transaksi berhasil disimpan");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        header("Location: transaksi.php?error=Gagal memproses transaksi");
        exit();
    }
}
?>