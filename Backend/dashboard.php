<?php
// Backend/dashboard.php
require_once __DIR__ . '/components/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Silakan login terlebih dahulu!");
    exit();
}

$role_user = $_SESSION['role'] ?? 'admin';

// Proses Update Status Transaksi dari Dashboard
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
        header("Location: dashboard.php?pesan=status_sukses");
        exit();
    } catch (PDOException $e) {
        $pesan_error = "Gagal memperbarui status transaksi.";
    }
}

// Hitung statistik ringkasan
try {
    $total_pelanggan = $pdo->query("SELECT COUNT(*) FROM tb_member")->fetchColumn();
    $cucian_diproses = $pdo->query("SELECT COUNT(*) FROM tb_transaksi WHERE status = 'proses' OR status = 'baru'")->fetchColumn();
    $cucian_siap     = $pdo->query("SELECT COUNT(*) FROM tb_transaksi WHERE status = 'selesai'")->fetchColumn();
} catch (PDOException $e) {
    $total_pelanggan = 0;
    $cucian_diproses = 0;
    $cucian_siap = 0;
}

// Ambil riwayat transaksi terbaru
try {
    $query = "SELECT t.*, 
             COALESCE(m.nama, 'Member Umum / Terhapus') AS nama_member, 
             COALESCE(u.nama, 'Administrator') AS nama_user, 
             COALESCE(pk.nama_paket, 'Paket Manual') AS nama_paket, 
             COALESCE(pk.harga, 0) AS harga_paket,
             COALESCE(dt.qty, 1) AS qty
             FROM tb_transaksi t
             LEFT JOIN tb_member m ON t.id_member = m.id
             LEFT JOIN tb_user u ON t.id_user = u.id
             LEFT JOIN tb_detail_transaksi dt ON t.id = dt.id_transaksi
             LEFT JOIN tb_paket pk ON dt.id_paket = pk.id
             ORDER BY t.id DESC LIMIT 5";
    $list_transaksi = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    $list_transaksi = [];
}

// Panggil Header
include __DIR__ . '/partials/header.php';
?>

<!-- Tambahan Style Khusus untuk Memaksa Tampilan Tabel Sesuai Keinginan -->
<style>
    .text-burgundy {
        color: #800020 !important;
    }
    /* Memaksa semua teks di baris tabel menjadi lebih tebal dan jelas */
    .table-custom-style tbody td {
        font-weight: 600 !important;
        color: #212529 !important;
    }
    .table-custom-style thead th {
        background-color: #fcf1f3 !important;
        color: #800020 !important;
        font-weight: 700 !important;
    }
</style>

<div class="d-flex" style="width: 100%; overflow-x: hidden;">
    <!-- Panggil Sidebar -->
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <!-- Main Wrapper -->
    <div id="content" class="p-0" style="min-height: 100vh; background-color: #f8f9fa;">
        <!-- Panggil Topbar -->
        <?php include __DIR__ . '/partials/topbar.php'; ?>

        <!-- Page Content -->
        <div class="container-fluid px-4 pb-5">
            <div class="mb-4">
                <h3 class="fw-bold text-dark">Dashboard Operasional</h3>
                <p class="text-muted mb-0">Pantau status cucian, member, dan transaksi laundry di sini.</p>
            </div>

            <!-- Notifikasi Status -->
            <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'status_sukses'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Status transaksi berhasil diperbarui dari Dashboard!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Statistik Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card p-3 border-0 shadow-sm rounded-4 bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-burgundy-soft p-3 rounded-4 fs-4 text-burgundy">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block fw-semibold">Total Pelanggan</span>
                                <h3 class="fw-bold mb-0 text-dark"><?= $total_pelanggan; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-3 border-0 shadow-sm rounded-4 bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-burgundy-soft p-3 rounded-4 fs-4 text-burgundy">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block fw-semibold">Cucian Diproses</span>
                                <h3 class="fw-bold mb-0 text-dark"><?= $cucian_diproses; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-3 border-0 shadow-sm rounded-4 bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-burgundy-soft p-3 rounded-4 fs-4 text-burgundy">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block fw-semibold">Selesai / Siap Diambil</span>
                                <h3 class="fw-bold mb-0 text-dark"><?= $cucian_siap; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Riwayat Transaksi Terbaru -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2 text-secondary"></i>Riwayat Transaksi Terbaru</h5>
                    <a href="transaksi.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-custom-style">
                        <thead>
                            <tr>
                                <th class="py-3 ps-3">#</th>
                                <th class="py-3">Invoice</th>
                                <th class="py-3">Member</th>
                                <th class="py-3">Paket & Rincian Harga</th>
                                <th class="py-3">Tgl Masuk</th>
                                <th class="py-3">Status Cucian</th>
                                <th class="py-3">Pembayaran</th>
                                <th class="py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($list_transaksi) > 0): ?>
                                <?php $no = 1;
                                foreach ($list_transaksi as $t):
                                    $subtotal = ($t['harga_paket'] * $t['qty']);
                                    $grand_total = $subtotal + $t['biaya_tambahan'] - $t['diskon'] + $t['pajak'];
                                ?>
                                    <tr>
                                        <td class="ps-3"><?= $no++; ?></td>
                                        <td><code class="fw-bold text-burgundy"><?= htmlspecialchars($t['kode_invoice']); ?></code></td>
                                        <td><?= htmlspecialchars($t['nama_member']); ?></td>
                                        <td>
                                            <span class="d-block fw-bold"><?= htmlspecialchars($t['nama_paket']); ?></span>
                                            <span class="text-muted" style="font-size: 12px; font-weight: normal;">Qty/Berat: <?= $t['qty']; ?></span>

                                            <?php if ($t['biaya_tambahan'] > 0 || $t['diskon'] > 0 || $t['pajak'] > 0): ?>
                                                <div class="mt-1" style="font-size: 11px; font-weight: normal;">
                                                    <?php if ($t['biaya_tambahan'] > 0): ?>
                                                        <span class="text-success">+ Tambahan: Rp <?= number_format($t['biaya_tambahan'], 0, ',', '.'); ?></span><br>
                                                    <?php endif; ?>
                                                    <?php if ($t['diskon'] > 0): ?>
                                                        <span class="text-danger">- Diskon: Rp <?= number_format($t['diskon'], 0, ',', '.'); ?></span><br>
                                                    <?php endif; ?>
                                                    <?php if ($t['pajak'] > 0): ?>
                                                        <span class="text-info">+ Pajak: Rp <?= number_format($t['pajak'], 0, ',', '.'); ?></span><br>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="text-dark mt-1" style="font-size: 12px;">
                                                Total: Rp <?= number_format($grand_total, 0, ',', '.'); ?>
                                            </div>
                                        </td>
                                        <td style="font-size: 13px;"><?= htmlspecialchars($t['tgl']); ?></td>
                                        <td>
                                            <?php
                                            $status = $t['status'];
                                            $badge_s = 'bg-secondary';
                                            if ($status === 'baru') $badge_s = 'bg-info text-dark';
                                            elseif ($status === 'proses') $badge_s = 'bg-warning text-dark';
                                            elseif ($status === 'selesai') $badge_s = 'bg-primary';
                                            elseif ($status === 'diambil') $badge_s = 'bg-success';
                                            ?>
                                            <span class="badge <?= $badge_s; ?> text-uppercase" style="font-size: 10px;">
                                                <?= htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($t['dibayar'] === 'dibayar'): ?>
                                                <span class="badge bg-success text-uppercase" style="font-size: 10px;">LUNAS</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger text-uppercase" style="font-size: 10px;">BELUM LUNAS</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="transaksi_detail.php?id=<?= $t['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1" title="Detail / Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill px-3 py-1" type="button" data-bs-toggle="dropdown" style="font-size: 13px;">
                                                        Status
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="dashboard.php?id=<?= $t['id']; ?>&ubah_status=baru">Baru</a></li>
                                                        <li><a class="dropdown-item" href="dashboard.php?id=<?= $t['id']; ?>&ubah_status=proses">Proses</a></li>
                                                        <li><a class="dropdown-item" href="dashboard.php?id=<?= $t['id']; ?>&ubah_status=selesai">Selesai</a></li>
                                                        <li><a class="dropdown-item" href="dashboard.php?id=<?= $t['id']; ?>&ubah_status=diambil">Diambil (Lunas)</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-cart-x fs-2 d-block mb-2"></i>
                                        Belum ada data riwayat transaksi.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>