<?php
require_once __DIR__ . '/components/koneksi.php';
restrict_access(['admin', 'kasir', 'owner']);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Silakan login terlebih dahulu!");
    exit();
}

$nama_outlet_aktif = get_nama_outlet_aktif($pdo);
$status =$_GET['status'] ?? '';
$outlet =$_GET['id_outlet'] ?? '';
$periode =$_GET['periode'] ?? '';
$tahun =$_GET['tahun'] ?? date('Y');
$tab =$_GET['tab'] ?? 'transaksi';

try {
    // Menggunakan kolom `tgl` sesuai dengan struktur database dari transaksi.php
    $sql = "SELECT t.*, COALESCE(m.nama, 'Member Umum') AS nama_member, COALESCE(o.nama, 'Utama') AS nama_outlet_transaksi,
            COALESCE(pk.nama_paket, 'Paket Manual') AS nama_paket, COALESCE(pk.harga, 0) AS harga_paket, COALESCE(dt.qty, 1) AS qty
            FROM tb_transaksi t
            LEFT JOIN tb_member m ON t.id_member = m.id
            LEFT JOIN tb_outlet o ON t.id_outlet = o.id
            LEFT JOIN tb_detail_transaksi dt ON t.id = dt.id_transaksi
            LEFT JOIN tb_paket pk ON dt.id_paket = pk.id WHERE 1=1";
    $params = [];

    if (!empty($outlet)) {$sql .= " AND t.id_outlet = :outlet"; 
        $params['outlet'] =$outlet; 
    }
    if (!empty($status)) {$sql .= " AND t.status = :status"; 
        $params['status'] =$status; 
    }
    
    // Filter Periode 3 Bulan menggunakan kolom `tgl`
    if (!empty($periode)) {$map_bulan = [
            1 => ['-01-01', '-03-31'], // Januari - Maret
            2 => ['-04-01', '-06-30'], // April - Juni
            3 => ['-07-01', '-09-30'], // Juli - September
            4 => ['-10-01', '-12-31']  // Oktober - Desember
        ];
        
        if (isset($map_bulan[$periode])) {$sql .= " AND t.tgl BETWEEN :start_date AND :end_date";
            $params['start_date'] = $tahun .$map_bulan[$periode][0] . ' 00:00:00';$params['end_date'] = $tahun .$map_bulan[$periode][1] . ' 23:59:59';         }     }$stmt = $pdo->prepare($sql . " ORDER BY t.id DESC");
    $stmt->execute($params);
    $laporan_list =$stmt->fetchAll();

    $total_omset = 0;
    foreach ($laporan_list as$r) {
        if ($r['dibayar'] === 'dibayar') {
            $total_omset += (($r['harga_paket'] * $r['qty']) +$r['biaya_tambahan'] - $r['diskon'] +$r['pajak']);
        }
    }

    $list_log =$pdo->query("SELECT * FROM activity_log ORDER BY id DESC LIMIT 50")->fetchAll();
    $semua_outlet =$pdo->query("SELECT * FROM tb_outlet ORDER BY nama ASC")->fetchAll();
} catch (PDOException $e) {
    $laporan_list =$list_log = $semua_outlet = [];$total_omset = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Laporan & Aktivitas - LaundryApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="img/loundryku.jpg">
    
    <style>
        :root {
            --burgundy: #800020;
            --burgundy-hover: #600018;
            --burgundy-light: #fcf1f3;
            --burgundy-border: #ebd3d7;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        #sidebar {
            width: 260px;
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid var(--burgundy-border);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 1.5rem;
            font-weight: 700;
            color: var(--burgundy);
            border-bottom: 1px solid #f0e6e8;
        }

        #sidebar .nav-link {
            color: #495057;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
        }

        #sidebar .nav-link:hover, 
        #sidebar .nav-link.active {
            background-color: var(--burgundy-light);
            color: var(--burgundy);
            border-left: 4px solid var(--burgundy);
        }

        .main-wrapper {
            margin-left: 260px;
            width: calc(100% - 260px);
        }

        .navbar-top {
            background: #fff;
            border-bottom: 1px solid var(--burgundy-border);
            padding: 0.8rem 2rem;
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.03);
        }

        .bg-burgundy-soft {
            background-color: var(--burgundy-light);
            color: var(--burgundy);
        }

        .btn-burgundy {
            background-color: var(--burgundy);
            color: #fff;
            border: none;
        }

        .btn-burgundy:hover {
            background-color: var(--burgundy-hover);
            color: #fff;
        }

        .nav-pills .nav-link {
            color: #555;
            font-weight: 600;
            border-radius: 50rem;
            padding: 0.6rem 1.5rem;
            background: #fff;
            border: 1px solid #dee2e6;
        }

        .nav-pills .nav-link.active {
            background-color: var(--burgundy) !important;
            color: #fff !important;
            border-color: var(--burgundy);
            box-shadow: 0 4px 12px rgba(128,0,32,0.2);
        }

        .table-custom th {
            background-color: var(--burgundy-light) !important;
            color: var(--burgundy);
            font-weight: 600;
            border-bottom: none;
        }

        @media print {
            #sidebar, 
            .navbar-top, 
            .no-print {
                display: none !important;
            }
            .main-wrapper {
                margin: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="d-flex">
    <nav id="sidebar" class="d-none d-md-block">
        <div class="sidebar-brand d-flex align-items-center gap-2 fs-5"><i class="bi bi-basket3-fill"></i> LaundryApp</div>
        <ul class="nav flex-column mt-3 gap-1">
            <?php if (in_array($_SESSION['role'], ['admin', 'kasir'])): ?><li><a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2 fs-5"></i> Dashboard</a></li><?php endif; ?>
            <?php if ($_SESSION['role'] === 'admin'): ?><li><a href="outlet.php" class="nav-link"><i class="bi bi-shop fs-5"></i> Outlet</a></li><?php endif; ?>
            <?php if (in_array($_SESSION['role'], ['admin', 'kasir'])): ?><li><a href="member.php" class="nav-link"><i class="bi bi-people fs-5"></i> Member</a></li><?php endif; ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="paket.php" class="nav-link"><i class="bi bi-tag fs-5"></i> Paket Cucian</a></li>
                <li><a href="user.php" class="nav-link"><i class="bi bi-person-badge fs-5"></i> Pengguna / Kasir</a></li>
            <?php endif; ?>
            <?php if (in_array($_SESSION['role'], ['admin', 'kasir'])): ?><li><a href="transaksi.php" class="nav-link"><i class="bi bi-cart-check fs-5"></i> Transaksi</a></li><?php endif; ?>
            <?php if (in_array($_SESSION['role'], ['admin', 'kasir', 'owner'])): ?><li><a href="laporan.php" class="nav-link active"><i class="bi bi-file-earmark-text fs-5"></i> Laporan</a></li><?php endif; ?>
            <li class="mt-3"><a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right fs-5"></i> Logout</a></li>
        </ul>
    </nav>
    <div class="main-wrapper">
        <nav class="navbar navbar-top navbar-expand mb-4">
                <div class="container-fluid">
                    <div class="d-flex align-items-center gap-2">
                        <?php 
                        $nama_outlet_aktif = get_nama_outlet_aktif($pdo);
                        // Cek apakah user adalah admin, berikan opsi dropdown ganti outlet cepat
                        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): 
                            $stmt_all_o = $pdo->query("SELECT * FROM tb_outlet ORDER BY nama ASC");
                            $list_o = $stmt_all_o->fetchAll();
                        ?>
                            <div class="dropdown">
                                <button class="btn btn-sm bg-burgundy-soft dropdown-toggle px-3 py-2 rounded-pill fw-semibold border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($nama_outlet_aktif); ?>
                                </button>
                                <ul class="dropdown-menu shadow-sm border-0 rounded-4 p-2">
                                    <li><h6 class="dropdown-header small text-muted">Pindah Posisi Outlet:</h6></li>
                                    <?php foreach ($list_o as $lo): ?>
                                        <li>
                                            <a class="dropdown-item rounded-2 py-2 <?= ($_SESSION['id_outlet'] == $lo['id']) ? 'active bg-danger text-white' : ''; ?>" href="ganti_outlet.php?id=<?= $lo['id']; ?>&redirect=<?= urlencode(basename($_SERVER['PHP_SELF'])); ?>">
                                                <i class="bi bi-shop me-2"></i> <?= htmlspecialchars($lo['nama']); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <!-- Jika kasir, tampilkan teks badge biasa sesuai lokasi tugasnya -->
                            <span class="badge bg-burgundy-soft px-3 py-2 rounded-pill">
                                <i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($nama_outlet_aktif); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end">
                            <span class="d-block fw-bold text-dark small"><?= htmlspecialchars($_SESSION['nama'] ?? 'Pengguna'); ?></span>
                            <span class="badge bg-secondary text-uppercase" style="font-size: 10px;"><?= htmlspecialchars($_SESSION['role'] ?? 'kasir'); ?></span>
                        </div>
                        <div class="bg-burgundy-soft rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                            <?= strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </nav>
        <div class="container-fluid px-4 pb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Pusat Laporan & Aktivitas</h3>
                    <p class="text-muted small mb-0">Pantau rekapitulasi transaksi dan riwayat aktivitas sistem per rentang 3 bulan.</p>
                </div>
                <button onclick="window.print()" class="btn btn-dark rounded-pill px-4 shadow-sm no-print"><i class="bi bi-printer-fill me-2"></i> Cetak Laporan</button>
            </div>
            <ul class="nav nav-pills mb-4 gap-2 no-print" role="tablist">
                <li class="nav-item"><a class="nav-link <?= $tab=='transaksi'?'active':''; ?>" href="laporan.php?tab=transaksi"><i class="bi bi-receipt-cutoff me-2"></i> Laporan Transaksi</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab=='activity'?'active':''; ?>" href="laporan.php?tab=activity"><i class="bi bi-clock-history me-2"></i> Riwayat Aktivitas</a></li>
            </ul>
            <?php if ($tab == 'transaksi'): ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm rounded-4 p-3 border-start border-4 border-danger">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-burgundy-soft p-3 rounded-4 fs-4"><i class="bi bi-wallet2"></i></div>
                                <div><span class="text-muted small d-block">Total Pendapatan (Lunas)</span><h4 class="fw-bold text-dark mb-0">Rp <?= number_format($total_omset, 0, ',', '.'); ?></h4></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm rounded-4 p-3 border-start border-4 border-secondary">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-light p-3 rounded-4 fs-4 text-secondary"><i class="bi bi-receipt"></i></div>
                                <div><span class="text-muted small d-block">Total Transaksi Tercatat</span><h4 class="fw-bold text-dark mb-0"><?= count($laporan_list); ?> Data</h4></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm rounded-4 p-4 mb-4">
                    <form method="GET" action="laporan.php" class="row g-3 align-items-end mb-4 no-print bg-light p-3 rounded-4">
                        <input type="hidden" name="tab" value="transaksi">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-secondary">Filter Outlet</label>
                            <select name="id_outlet" class="form-select rounded-pill border-0 shadow-sm">
                                <option value="">Semua Outlet</option>
                                <?php foreach ($semua_outlet as$o): ?>
                                    <option value="<?= $o['id']; ?>" <?= $outlet == $o['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($o['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-secondary">Periode 3 Bulan</label>
                            <select name="periode" class="form-select rounded-pill border-0 shadow-sm">
                                <option value="">Semua Periode</option>
                                <option value="1" <?= $periode=='1'?'selected':''; ?>>Januari - Maret</option>
                                <option value="2" <?= $periode=='2'?'selected':''; ?>>April - Juni</option>
                                <option value="3" <?= $periode=='3'?'selected':''; ?>>Juli - September</option>
                                <option value="4" <?= $periode=='4'?'selected':''; ?>>Oktober - Desember</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary">Tahun</label>
                            <input type="number" name="tahun" class="form-control rounded-pill border-0 shadow-sm" value="<?= htmlspecialchars($tahun); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary">Status</label>
                            <select name="status" class="form-select rounded-pill border-0 shadow-sm">
                                <option value="">Semua Status</option>
                                <option value="baru" <?= $status=='baru'?'selected':''; ?>>Baru</option>
                                <option value="proses" <?= $status=='proses'?'selected':''; ?>>Proses</option>
                                <option value="selesai" <?= $status=='selesai'?'selected':''; ?>>Selesai</option>
                                <option value="diambil" <?= $status=='diambil'?'selected':''; ?>>Diambil</option>
                            </select>
                        </div>
                        <div class="col-md-2"><button type="submit" class="btn btn-burgundy rounded-pill w-100 shadow-sm"><i class="bi bi-filter me-1"></i> Filter</button></div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-custom rounded-3 overflow-hidden">
                            <thead>
                                <tr>
                                    <th class="py-3">#</th>
                                    <th class="py-3">Invoice</th>
                                    <th class="py-3">Outlet</th>
                                    <th class="py-3">Member</th>
                                    <th class="py-3">Paket</th>
                                    <th class="py-3">Total Harga</th>
                                    <th class="py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($laporan_list) > 0): ?>
                                    <?php $no = 1; foreach ($laporan_list as$row): 
                                        $gt = (($row['harga_paket'] * $row['qty']) +$row['biaya_tambahan'] - $row['diskon'] +$row['pajak']); ?>
                                        <tr>
                                            <td class="text-muted"><?= $no++; ?></td>
                                            <td><code class="fw-bold text-dark"><?= htmlspecialchars($row['kode_invoice']); ?></code></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['nama_outlet_transaksi']); ?></span></td>
                                            <td class="fw-semibold"><?= htmlspecialchars($row['nama_member']); ?></td>
                                            <td>
                                                <span class="small d-block fw-bold"><?= htmlspecialchars($row['nama_paket']); ?></span>
                                                <span class="text-muted" style="font-size: 11px;">Qty: <?= $row['qty']; ?></span>
                                            </td>
                                            <td class="fw-bold text-dark">Rp <?= number_format($gt, 0, ',', '.'); ?></td>
                                            <td><span class="badge bg-secondary text-uppercase" style="font-size: 10px;"><?= htmlspecialchars($row['status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data laporan untuk periode 3 bulan tersebut.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm rounded-4 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-secondary me-2"></i>Log Aktivitas Pengguna</h5>
                        <span class="badge bg-burgundy-soft px-3 py-2 rounded-pill small">50 Aktivitas Terakhir</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-custom rounded-3 overflow-hidden">
                            <thead>
                                <tr>
                                    <th class="py-3">#ID</th>
                                    <th class="py-3">Pengguna</th>
                                    <th class="py-3">Aktivitas Sistem</th>
                                    <th class="py-3">Waktu Kejadian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($list_log) > 0): ?>
                                    <?php foreach ($list_log as$log): ?>
                                        <tr>
                                            <td class="text-muted">#<?= $log['id']; ?></td>
                                            <td><span class="badge bg-burgundy-soft px-3 py-2"><i class="bi bi-person-fill me-1"></i> <?= htmlspecialchars($log['username']); ?></span></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($log['activity']); ?></td>
                                            <td class="small text-secondary"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($log['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada riwayat aktivitas.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>