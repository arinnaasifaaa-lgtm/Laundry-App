<?php
// Backend/laporan.php
require_once __DIR__ . '/components/koneksi.php';
restrict_access(['admin', 'kasir', 'owner']);

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Silakan login terlebih dahulu!");
    exit();
}

$nama_user = $_SESSION['nama'] ?? 'Pengguna';
$role_user = $_SESSION['role'] ?? 'kasir';
$id_outlet_user = $_SESSION['id_outlet'] ?? 1;

// Ambil informasi nama outlet aktif user
try {
    $stmt_outlet = $pdo->prepare("SELECT tb_outlet.nama FROM tb_user JOIN tb_outlet ON tb_user.id_outlet = tb_outlet.id WHERE tb_user.id = :id_user");
    $stmt_outlet->execute(['id_user' => $_SESSION['user_id']]);
    $outlet_active = $stmt_outlet->fetch();
    $nama_outlet = $outlet_active['nama'] ?? 'Outlet Utama';
} catch (PDOException $e) {
    $nama_outlet = 'Outlet Utama';
}

// Ambil data untuk filter outlet
try {
    $stmt_list_outlet = $pdo->query("SELECT * FROM tb_outlet ORDER BY nama ASC");
    $semua_outlet = $stmt_list_outlet->fetchAll();
} catch (PDOException $e) {
    $semua_outlet = [];
}

// PERBAIKAN: Tangkap parameter filter dengan benar dari URL (GET)
$filter_status = $_GET['status'] ?? '';
$filter_outlet = $_GET['id_outlet'] ?? '';

// Query utama untuk mengambil data laporan transaksi dengan filter dinamis
try {
    $query = "SELECT t.*, 
              COALESCE(m.nama, 'Member Umum') AS nama_member, 
              COALESCE(u.nama, 'Kasir') AS nama_user, 
              COALESCE(o.nama, 'Outlet Utama') AS nama_outlet_transaksi,
              COALESCE(pk.nama_paket, 'Paket Manual') AS nama_paket, 
              COALESCE(pk.harga, 0) AS harga_paket,
              COALESCE(dt.qty, 1) AS qty
              FROM tb_transaksi t
              LEFT JOIN tb_member m ON t.id_member = m.id
              LEFT JOIN tb_user u ON t.id_user = u.id
              LEFT JOIN tb_outlet o ON t.id_outlet = o.id
              LEFT JOIN tb_detail_transaksi dt ON t.id = dt.id_transaksi
              LEFT JOIN tb_paket pk ON dt.id_paket = pk.id
              WHERE 1=1";

    $params = [];

    // Jika filter outlet dipilih (tidak kosong)
    if (!empty($filter_outlet)) {
        $query .= " AND t.id_outlet = :id_outlet";
        $params['id_outlet'] = $filter_outlet;
    }

    // Jika filter status dipilih (tidak kosong)
    if (!empty($filter_status)) {
        $query .= " AND t.status = :status";
        $params['status'] = $filter_status;
    }

    $query .= " ORDER BY t.id DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $laporan_list = $stmt->fetchAll();

    // Hitung total omset dari transaksi yang lunas (dibayar = 'dibayar')
    $total_omset = 0;
    foreach ($laporan_list as $row) {
        if ($row['dibayar'] === 'dibayar') {
            $subtotal = ($row['harga_paket'] * $row['qty']);
            $grand_total = $subtotal + $row['biaya_tambahan'] - $row['diskon'] + $row['pajak'];
            $total_omset += $grand_total;
        }
    }

} catch (PDOException $e) {
    $laporan_list = [];
    $total_omset = 0;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi - LaundryApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --burgundy-primary: #800020;
            --burgundy-hover: #600018;
            --burgundy-light: #fcf1f3;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        #sidebar {
            min-width: 260px;
            max-width: 260px;
            background-color: #ffffff;
            border-right: 1px solid #ebd3d7;
            min-height: 100vh;
        }

        #sidebar .sidebar-brand {
            padding: 1.5rem;
            font-weight: 700;
            color: var(--burgundy-primary);
            border-bottom: 1px solid #f0e6e8;
        }

        #sidebar .nav-link {
            color: #495057;
            padding: 0.85rem 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
        }

        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            background-color: var(--burgundy-light);
            color: var(--burgundy-primary);
            border-left: 4px solid var(--burgundy-primary);
        }

        .navbar-top {
            background-color: #ffffff;
            border-bottom: 1px solid #ebd3d7;
            padding: 1rem 2rem;
        }

        .bg-burgundy-soft {
            background-color: var(--burgundy-light);
            color: var(--burgundy-primary);
        }

        .table-custom th {
            background-color: #fcf1f3;
            color: var(--burgundy-primary);
            font-weight: 600;
        }

        /* Styling khusus saat halaman dicetak (Print) */
        @media print {
            #sidebar, .navbar-top, .no-print, .btn {
                display: none !important;
            }
            body {
                background-color: white !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            #content {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>

<body>

    <div class="d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="d-none d-md-block">
    <div class="sidebar-brand d-flex align-items-center gap-2 fs-5">
        <i class="bi bi-basket3-fill"></i> LaundryApp
    </div>
    <ul class="nav flex-column mt-3">
        <!-- Dashboard: Hanya Admin & Kasir -->
        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'kasir'])): ?>
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link">
                <i class="bi bi-speedometer2 fs-5"></i> Dashboard
            </a>
        </li>
        <?php endif; ?>

        <!-- Outlet: HANYA ADMIN -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li class="nav-item">
            <a href="outlet.php" class="nav-link">
                <i class="bi bi-shop fs-5"></i> Outlet
            </a>
        </li>
        <?php endif; ?>

        <!-- Member: Admin & Kasir -->
        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'kasir'])): ?>
        <li class="nav-item">
            <a href="member.php" class="nav-link">
                <i class="bi bi-people fs-5"></i> Member
            </a>
        </li>
        <?php endif; ?>

        <!-- Paket Cucian: HANYA ADMIN -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li class="nav-item">
            <a href="paket.php" class="nav-link">
                <i class="bi bi-tag fs-5"></i> Paket Cucian
            </a>
        </li>
        <?php endif; ?>

        <!-- Pengguna / Kasir: HANYA ADMIN -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li class="nav-item">
            <a href="user.php" class="nav-link">
                <i class="bi bi-person-badge fs-5"></i> Pengguna / Kasir
            </a>
        </li>
        <?php endif; ?>

        <!-- Transaksi: Admin & Kasir -->
        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'kasir'])): ?>
        <li class="nav-item">
            <a href="transaksi.php" class="nav-link">
                <i class="bi bi-cart-check fs-5"></i> Transaksi
            </a>
        </li>
        <?php endif; ?>

        <!-- Laporan: Admin, Kasir, & Owner (Karena ini halaman laporan.php, maka aktif) -->
        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'kasir', 'owner'])): ?>
        <li class="nav-item">
            <a href="laporan.php" class="nav-link active">
                <i class="bi bi-file-earmark-text fs-5"></i> Laporan
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-item mt-4">
            <a href="logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right fs-5"></i> Logout
            </a>
        </li>
    </ul>
</nav>

        <!-- Main Content -->
        <div id="content" class="p-0 w-100">
            <!-- Top Navbar -->
            <!-- Top Navbar -->
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

            <!-- Page Body -->
            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold text-dark mb-1">Generate Laporan Transaksi</h3>
                        <p class="text-muted mb-0">Rekapitulasi data transaksi dan total pendapatan laundry.</p>
                    </div>
                    <button onclick="window.print()" class="btn btn-dark rounded-pill px-4 shadow-sm">
                        <i class="bi bi-printer-fill me-2"></i> Cetak Laporan
                    </button>
                </div>

                <!-- Card Informasi Total Omset -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-burgundy-soft p-3 rounded-4 fs-4">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">Total Pendapatan (Lunas)</span>
                                    <h4 class="fw-bold mb-0 text-dark">Rp <?= number_format($total_omset, 0, ',', '.'); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-burgundy-soft p-3 rounded-4 fs-4">
                                    <i class="bi bi-receipt"></i>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">Total Transaksi Tercatat</span>
                                    <h4 class="fw-bold mb-0 text-dark"><?= count($laporan_list); ?> Data</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Filter Laporan -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 no-print">
                    <form method="GET" action="laporan.php" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Filter Outlet</label>
                            <select name="id_outlet" class="form-select rounded-pill">
                                <option value="">Semua Outlet</option>
                                <?php foreach ($semua_outlet as $o): ?>
                                    <option value="<?= $o['id']; ?>" <?= ($filter_outlet == $o['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($o['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Status Cucian</label>
                            <select name="status" class="form-select rounded-pill">
                                <option value="">Semua Status</option>
                                <option value="baru" <?= ($filter_status === 'baru') ? 'selected' : ''; ?>>Baru</option>
                                <option value="proses" <?= ($filter_status === 'proses') ? 'selected' : ''; ?>>Proses</option>
                                <option value="selesai" <?= ($filter_status === 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                <option value="diambil" <?= ($filter_status === 'diambil') ? 'selected' : ''; ?>>Diambil</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-danger rounded-pill px-4 w-100" style="background-color: var(--burgundy-primary); border: none;">
                                <i class="bi bi-filter me-1"></i> Tampilkan Laporan
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabel Data Laporan -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-table me-2 text-secondary"></i>Tabel Rekapitulasi Laporan</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Invoice</th>
                                    <th>Outlet</th>
                                    <th>Member</th>
                                    <th>Paket & Rincian Harga</th>
                                    <th>Tgl Masuk</th>
                                    <th>Status</th>
                                    <th>Pembayaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($laporan_list) > 0): ?>
                                    <?php $no = 1; foreach ($laporan_list as $row): 
                                        $subtotal = ($row['harga_paket'] * $row['qty']);
                                        $grand_total = $subtotal + $row['biaya_tambahan'] - $row['diskon'] + $row['pajak'];
                                    ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><code class="fw-bold text-dark"><?= htmlspecialchars($row['kode_invoice']); ?></code></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['nama_outlet_transaksi']); ?></span></td>
                                            <td class="fw-semibold"><?= htmlspecialchars($row['nama_member']); ?></td>
                                            <td>
                                                <span class="small d-block fw-bold"><?= htmlspecialchars($row['nama_paket']); ?></span>
                                                <span class="text-muted" style="font-size: 12px;">Qty/Berat: <?= $row['qty']; ?></span>
                                                <div class="fw-bold text-dark mt-1" style="font-size: 12px;">
                                                    Total: Rp <?= number_format($grand_total, 0, ',', '.'); ?>
                                                </div>
                                            </td>
                                            <td class="small text-secondary"><?= htmlspecialchars($row['tgl']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary text-uppercase" style="font-size: 10px;">
                                                    <?= htmlspecialchars($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($row['dibayar'] === 'dibayar'): ?>
                                                    <span class="badge bg-success text-uppercase" style="font-size: 10px;">Lunas</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger text-uppercase" style="font-size: 10px;">Belum Lunas</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bi bi-file-earmark-x fs-2 d-block mb-2"></i>
                                            Tidak ada data laporan yang sesuai dengan filter.
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