<?php
// frontend/laporan.php
require_once __DIR__ . '/../Backend/components/koneksi.php';
restrict_access(['admin', 'kasir']);

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Silakan login terlebih dahulu!");
    exit();
}

$id_outlet_user = $_SESSION['id_outlet'] ?? 1;

// Ambil parameter filter laporan
$periode = $_GET['periode'] ?? '';
$tahun = $_GET['tahun'] ?? date('Y');
$status_filter = $_GET['status'] ?? '';

// Ambil data laporan transaksi dengan filter
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
              LEFT JOIN tb_paket pk ON dt.id_paket = pk.id WHERE 1=1";
    
    $params = [];

    // Filter Status
    if (!empty($status_filter)) {
        $query .= " AND t.status = :status";
        $params['status'] = $status_filter;
    }

    // Filter Periode 3 Bulan
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
    $list_laporan = $stmt->fetchAll();

    // Hitung total pendapatan dari data yang tersaring
    $total_pendapatan = 0;
    foreach ($list_laporan as $l) {
        if ($l['dibayar'] === 'dibayar') {
            $subtotal = ($l['harga_paket'] * $l['qty']);
            $diskon_bersih = abs($l['diskon']);
            $grand_total = $subtotal + $l['biaya_tambahan'] - $diskon_bersih + $l['pajak'];
            $total_pendapatan += $grand_total;
        }
    }

} catch (PDOException $e) {
    $list_laporan = [];
    $total_pendapatan = 0;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Laporan - Lumiere Laundry</title>
    
    <!-- Stylesheets Bawaan Template Frontend -->
    <link rel="stylesheet" href="./assets/libraries/glide/css/glide.core.min.css">
    <link rel="stylesheet" href="./assets/libraries/aos/aos.css">
    <link rel="stylesheet" href="./assets/css/main.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <!-- FontAwesome & Bootstrap Icons -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .table-custom th {
            background-color: #fcf1f3;
            color: #800020;
            font-weight: 600;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: white !important;
            }
        }
    </style>
</head>

<body>
     <?php include 'navbar.php'; ?>

    <!-- Navbar Atas Ala Template Frontend (Disembunyikan saat cetak) -->
    <!-- <header class="navigation position-sticky top-0 w-100 bg-body-tertiary shadow-sm border-bottom z-3 no-print">
        <nav class="navbar navbar-expand-xl" aria-label="Offcanvas navbar large">
            <div class="container py-1">
                <a href="home.php" class="navbar-brand">
                    <img src="./assets/logo/logo.png" height="40" alt="logo">
                </a>

                <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar2" aria-controls="offcanvasNavbar2" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="offcanvas offcanvas-end border-0 rounded-start-0" tabindex="-1" id="offcanvasNavbar2" aria-labelledby="offcanvasNavbar2Label">
                    <div class="offcanvas-header" style="padding: 2rem 2rem 1.5rem 2rem;">
                        <h5 class="offcanvas-title m-0" id="offcanvasNavbar2Label">
                            <a class="navbar-brand" href="home.php">
                                <img src="./assets/logo/logo.png" height="32" alt="logo">
                            </a>
                        </h5>
                        <button type="button" class="btn-close text-body-emphasis" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>

                    <div class="offcanvas-body">
                        <ul class="navbar-nav align-items-xl-center flex-grow-1 column-gap-4 row-gap-4 row-gap-xl-2 ms-auto">
                            <li class="nav-item">
                                <a href="home.php" class="px-3 text-body-emphasis bg-body-secondary-hover nav-link rounded-3 text-base leading-6 fw-semibold">
                                    Beranda
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="member.php" class="px-3 text-body-emphasis bg-body-secondary-hover nav-link rounded-3 text-base leading-6 fw-semibold">
                                    Registrasi Member
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="transaksi.php" class="px-3 text-body-emphasis bg-body-secondary-hover nav-link rounded-3 text-base leading-6 fw-semibold">
                                    Entri Transaksi
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="laporan.php" class="px-3 text-body-emphasis bg-body-secondary-hover nav-link rounded-3 text-base leading-6 fw-semibold active" aria-current="page">
                                    Generate Laporan
                                </a>
                            </li>
                            <li class="nav-item ms-xl-3">
                                <a href="../backend/logout.php" class="btn btn-danger text-white btn-sm px-3 rounded-pill">
                                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header> -->

    <!-- Konten Utama Halaman Laporan -->
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <h2 class="fw-bold text-dark">Generate Laporan Transaksi</h2>
                <p class="text-muted mb-0">Saring dan cetak rekapitulasi laporan pendapatan laundry.</p>
            </div>
            <button onclick="window.print()" class="btn btn-dark rounded-pill px-4 shadow-sm text-white">
                <i class="bi bi-printer-fill me-1"></i> Cetak Laporan
            </button>
        </div>

        <!-- Form Filter Laporan (Disembunyikan saat cetak) -->
        <div class="card shadow-sm border-0 rounded-4 p-4 mb-4 bg-white no-print">
            <form method="GET" action="laporan.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary">Periode 3 Bulan</label>
                    <select name="periode" class="form-select rounded-pill shadow-sm">
                        <option value="">Semua Periode</option>
                        <option value="1" <?= $periode=='1'?'selected':''; ?>>Januari - Maret</option>
                        <option value="2" <?= $periode=='2'?'selected':''; ?>>April - Juni</option>
                        <option value="3" <?= $periode=='3'?'selected':''; ?>>Juli - September</option>
                        <option value="4" <?= $periode=='4'?'selected':''; ?>>Oktober - Desember</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">Tahun</label>
                    <input type="number" name="tahun" class="form-control rounded-pill shadow-sm" value="<?= htmlspecialchars($tahun); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">Status Pembayaran / Filter</label>
                    <select name="status" class="form-select rounded-pill shadow-sm">
                        <option value="">Semua Status Cucian</option>
                        <option value="baru" <?= $status_filter=='baru'?'selected':''; ?>>Baru</option>
                        <option value="proses" <?= $status_filter=='proses'?'selected':''; ?>>Proses</option>
                        <option value="selesai" <?= $status_filter=='selesai'?'selected':''; ?>>Selesai</option>
                        <option value="diambil" <?= $status_filter=='diambil'?'selected':''; ?>>Diambil</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary rounded-pill w-100 shadow-sm text-white"><i class="bi bi-filter me-1"></i> Tampilkan</button>
                </div>
            </form>
        </div>

        <!-- Ringkasan Pendapatan -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm rounded-4 bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase small fw-bold mb-1">Total Pendapatan (Transaksi Lunas)</h6>
                            <h3 class="fw-bold mb-0">Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h3>
                        </div>
                        <div class="fs-1 text-white-50">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Rekapitulasi Laporan -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="text-center mb-4 d-none d-print-block">
                <h3 class="fw-bold">LUMIERE LAUNDRY - LAPORAN TRANSAKSI</h3>
                <p class="text-muted small">Dicetak pada: <?= date('d-m-Y H:i:s'); ?></p>
                <hr>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice</th>
                            <th>Member</th>
                            <th>Paket & Rincian</th>
                            <th>Tgl Masuk</th>
                            <th>Status</th>
                            <th>Pembayaran</th>
                            <th>Total (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($list_laporan) > 0): ?>
                            <?php $no = 1; foreach ($list_laporan as $l): 
                                $subtotal = ($l['harga_paket'] * $l['qty']);
                                $diskon_bersih = abs($l['diskon']);
                                $grand_total = $subtotal + $l['biaya_tambahan'] - $diskon_bersih + $l['pajak'];
                            ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><code class="fw-bold text-dark"><?= htmlspecialchars($l['kode_invoice']); ?></code></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($l['nama_member']); ?></td>
                                    <td>
                                        <span class="small d-block fw-bold"><?= htmlspecialchars($l['nama_paket']); ?></span>
                                        <span class="text-muted" style="font-size: 11px;">Berat/Qty: <?= $l['qty']; ?></span>
                                    </td>
                                    <td class="small text-secondary"><?= htmlspecialchars($l['tgl']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary text-uppercase" style="font-size: 9px;"><?= htmlspecialchars($l['status']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($l['dibayar'] === 'dibayar'): ?>
                                            <span class="badge bg-success text-uppercase" style="font-size: 9px;">Lunas</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger text-uppercase" style="font-size: 9px;">Belum Lunas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-dark">Rp <?= number_format($grand_total, 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-file-earmark-text fs-2 d-block mb-2"></i>
                                    Tidak ada data laporan untuk filter yang dipilih.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts Bootstrap & Template -->
    <script src="./assets/libraries/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/libraries/glide/glide.min.js"></script>
    <script src="./assets/libraries/aos/aos.js"></script>
    <script src="./assets/js/scripts.js"></script>
</body>
</html>