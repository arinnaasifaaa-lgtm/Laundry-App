<?php
// lacak.php (Halaman untuk Pelanggan)
require_once __DIR__ . '/components/koneksi.php';

$data_lacak = null;
$pesan_error = '';

// Proses ketika tombol "Cek Status" diklik
if (isset($_GET['invoice']) && !empty($_GET['invoice'])) {
    $invoice = trim($_GET['invoice']);
    
    try {
        // Query untuk menarik data transaksi beserta rincian harga, diskon, dan paketnya
        $stmt = $pdo->prepare("SELECT t.*, 
                               COALESCE(m.nama, 'Pelanggan') AS nama_member, 
                               COALESCE(pk.nama_paket, 'Paket Manual') AS nama_paket, 
                               COALESCE(pk.harga, 0) AS harga_paket,
                               COALESCE(dt.qty, 1) AS qty,
                               COALESCE(o.nama, 'Outlet Utama') AS nama_outlet
                               FROM tb_transaksi t
                               LEFT JOIN tb_member m ON t.id_member = m.id
                               LEFT JOIN tb_detail_transaksi dt ON t.id = dt.id_transaksi
                               LEFT JOIN tb_paket pk ON dt.id_paket = pk.id
                               LEFT JOIN tb_outlet o ON t.id_outlet = o.id
                               WHERE t.kode_invoice = :invoice");
        $stmt->execute(['invoice' => $invoice]);
        $data_lacak = $stmt->fetch();

        if (!$data_lacak) {
            $pesan_error = "Nomor invoice tidak ditemukan. Pastikan kembali kode yang Anda masukkan benar!";
        }
    } catch (PDOException $e) {
        $pesan_error = "Terjadi kesalahan sistem.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Status Cucian - LaundryApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .card-custom { border-radius: 1rem; border: none; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05); }
        .btn-burgundy { background-color: #800020; color: #fff; }
        .btn-burgundy:hover { background-color: #600018; color: #fff; }
        .text-burgundy { color: #800020; }
    </style>
</head>
<body>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <!-- Judul & Form Pencarian -->
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-dark">Lacak Status Cucian</h3>
                    <p class="text-muted">Masukkan kode invoice untuk melihat progres dan rincian biaya cucian Anda</p>
                </div>

                <form action="" method="GET" class="card card-custom p-3 mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-receipt"></i></span>
                        <input type="text" name="invoice" class="form-control border-start-0 ps-0" placeholder="Contoh: TRX-20260917091716" value="<?= htmlspecialchars($_GET['invoice'] ?? ''); ?>" required>
                        <button class="btn btn-burgundy px-4" type="submit">Cek Status</button>
                    </div>
                </form>

                <?php if (!empty($pesan_error)): ?>
                    <div class="alert alert-danger text-center" role="alert">
                        <i class="bi bi-exclamation-circle me-1"></i> <?= $pesan_error; ?>
                    </div>
                <?php endif; ?>

                <!-- Hasil Pencarian -->
                <?php if ($data_lacak): 
                    // Perhitungan matematika rincian harga secara otomatis
                    $subtotal       = ($data_lacak['harga_paket'] * $data_lacak['qty']);
                    $biaya_tambahan = floatval($data_lacak['biaya_tambahan']);
                    $diskon         = abs(floatval($data_lacak['diskon']));
                    $pajak          = floatval($data_lacak['pajak']);
                    $grand_total    = $subtotal + $biaya_tambahan - $diskon + $pajak;
                ?>
                    <div class="card card-custom p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
                            <div>
                                <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Transaksi Ditemukan</span>
                                <h5 class="fw-bold text-dark mb-0 mt-1"><?= htmlspecialchars($data_lacak['kode_invoice']); ?></h5>
                            </div>
                            <span class="badge bg-light text-dark border">Valid</span>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <span class="text-muted small d-block">Nama Pelanggan</span>
                                <strong class="text-dark"><?= htmlspecialchars($data_lacak['nama_member']); ?></strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">Outlet Proses</span>
                                <strong class="text-dark"><?= htmlspecialchars($data_lacak['nama_outlet']); ?></strong>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <span class="text-muted small d-block">Paket Cucian</span>
                                <strong class="text-dark"><?= htmlspecialchars($data_lacak['nama_paket']); ?></strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">Jumlah / Berat</span>
                                <strong class="text-dark"><?= $data_lacak['qty']; ?> Kg/Pcs</strong>
                            </div>
                        </div>

                        <!-- KOTAK RINCIAN HARGA YANG DIMINTA -->
                        <div class="bg-light p-3 rounded-3 mb-3">
                            <div class="fw-bold small text-secondary mb-2">Rincian Biaya:</div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Subtotal Paket:</span>
                                <span>Rp <?= number_format($subtotal, 0, ',', '.'); ?></span>
                            </div>
                            <?php if ($biaya_tambahan > 0): ?>
                            <div class="d-flex justify-content-between small text-success mb-1">
                                <span>Biaya Tambahan:</span>
                                <span>+ Rp <?= number_format($biaya_tambahan, 0, ',', '.'); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($diskon > 0): ?>
                            <div class="d-flex justify-content-between small text-danger mb-1">
                                <span>Diskon:</span>
                                <span>- Rp <?= number_format($diskon, 0, ',', '.'); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($pajak > 0): ?>
                            <div class="d-flex justify-content-between small text-info mb-1">
                                <span>Pajak:</span>
                                <span>+ Rp <?= number_format($pajak, 0, ',', '.'); ?></span>
                            </div>
                            <?php endif; ?>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fw-bold text-dark">
                                <span>Total Harga Akhir:</span>
                                <span class="text-burgundy">Rp <?= number_format($grand_total, 0, ',', '.'); ?></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <span class="text-muted small d-block mb-1">Status Pengerjaan</span>
                                <?php
                                    $status = $data_lacak['status'];
                                    $badge_s = 'bg-secondary';
                                    if ($status === 'baru') $badge_s = 'bg-info text-dark';
                                    elseif ($status === 'proses') $badge_s = 'bg-warning text-dark';
                                    elseif ($status === 'selesai') $badge_s = 'bg-primary';
                                    elseif ($status === 'diambil') $badge_s = 'bg-success';
                                ?>
                                <span class="badge <?= $badge_s; ?> text-uppercase"><?= htmlspecialchars($status); ?></span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block mb-1">Status Pembayaran</span>
                                <?php if ($data_lacak['dibayar'] === 'dibayar'): ?>
                                    <span class="badge bg-success text-uppercase">Lunas</span>
                                <?php else: ?>
                                    <span class="badge bg-danger text-uppercase">Belum Lunas</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>