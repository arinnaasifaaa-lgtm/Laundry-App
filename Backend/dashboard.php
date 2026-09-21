<?php
// Backend/dashboard.php
require_once __DIR__ . '/components/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Silakan login terlebih dahulu!");
    exit();
}

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

// Proses Update Status Transaksi dari Dashboard (jika ada aksi ubah status)
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

// Ambil riwayat transaksi terbaru (disamakan dengan halaman transaksi.php)
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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Laundry App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Logo -->
   <link rel="icon" type="image/jpeg" href="img/loundryku.jpg">
    
    <style>
        :root {
            --burgundy-primary: #800020;
            --burgundy-hover: #600018;
            --burgundy-light: #fcf1f3;
            --burgundy-bg: #f7ebee;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
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

        #content {
            width: 100%;
            padding: 2rem;
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

        .stat-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .table-custom th {
            background-color: #fcf1f3;
            color: var(--burgundy-primary);
            font-weight: 600;
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
        <!-- Dashboard: Bisa diakses Admin & Kasir (atau disesuaikan) -->
        <?php if (in_array($role_user, ['admin', 'kasir'])): ?>
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2 fs-5"></i> Dashboard
            </a>
        </li>
        <?php endif; ?>

        <!-- CRUD Outlet: HANYA ADMIN -->
        <?php if ($role_user === 'admin'): ?>
        <li class="nav-item">
            <a href="outlet.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'outlet.php') ? 'active' : ''; ?>">
                <i class="bi bi-shop fs-5"></i> Outlet
            </a>
        </li>
        <?php endif; ?>

        <!-- Registrasi Pelanggan / Member: ADMIN & KASIR -->
        <?php if (in_array($role_user, ['admin', 'kasir'])): ?>
        <li class="nav-item">
            <a href="member.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'member.php') ? 'active' : ''; ?>">
                <i class="bi bi-people fs-5"></i> Member
            </a>
        </li>
        <?php endif; ?>

        <!-- CRUD Paket Cucian: HANYA ADMIN -->
        <?php if ($role_user === 'admin'): ?>
        <li class="nav-item">
            <a href="paket.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'paket.php') ? 'active' : ''; ?>">
                <i class="bi bi-tag fs-5"></i> Paket Cucian
            </a>
        </li>
        <?php endif; ?>

        <!-- CRUD Pengguna / Kasir: HANYA ADMIN -->
        <?php if ($role_user === 'admin'): ?>
        <li class="nav-item">
            <a href="user.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'user.php') ? 'active' : ''; ?>">
                <i class="bi bi-person-badge fs-5"></i> Pengguna / Kasir
            </a>
        </li>
        <?php endif; ?>

        <!-- Entri Transaksi: ADMIN & KASIR -->
        <?php if (in_array($role_user, ['admin', 'kasir'])): ?>
        <li class="nav-item">
            <a href="transaksi.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'transaksi.php') ? 'active' : ''; ?>">
                <i class="bi bi-cart-check fs-5"></i> Transaksi
            </a>
        </li>
        <?php endif; ?>

        <!-- Generate Laporan: ADMIN, KASIR, & OWNER -->
        <?php if (in_array($role_user, ['admin', 'kasir', 'owner'])): ?>
        <li class="nav-item">
            <a href="laporan.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'laporan.php') ? 'active' : ''; ?>">
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

        <!-- Main Wrapper -->
        <div id="content" class="p-0">
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

            <!-- Page Content -->
            <div class="container-fluid px-4">
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
                        <div class="card stat-card p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-burgundy-soft p-3 rounded-4 fs-4">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">Total Pelanggan</span>
                                    <h3 class="fw-bold mb-0 text-dark"><?= $total_pelanggan; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-burgundy-soft p-3 rounded-4 fs-4">
                                    <i class="bi bi-arrow-repeat"></i>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">Cucian Diproses</span>
                                    <h3 class="fw-bold mb-0 text-dark"><?= $cucian_diproses; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-burgundy-soft p-3 rounded-4 fs-4">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">Selesai / Siap Diambil</span>
                                    <h3 class="fw-bold mb-0 text-dark"><?= $cucian_siap; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Riwayat Transaksi Terbaru -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2 text-secondary"></i>Riwayat Transaksi Terbaru</h5>
                        <a href="transaksi.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Lihat Semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Invoice</th>
                                    <th>Member</th>
                                    <th>Paket & Rincian Harga</th>
                                    <th>Tgl Masuk</th>
                                    <th>Status Cucian</th>
                                    <th>Pembayaran</th>
                                    <th>Aksi</th>
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
                                            <td><?= $no++; ?></td>
                                            <td><code class="fw-bold text-dark"><?= htmlspecialchars($t['kode_invoice']); ?></code></td>
                                            <td class="fw-semibold"><?= htmlspecialchars($t['nama_member']); ?></td>
                                            <td>
                                                <span class="small d-block fw-bold"><?= htmlspecialchars($t['nama_paket']); ?></span>
                                                <span class="text-muted" style="font-size: 12px;">Qty/Berat: <?= $t['qty']; ?></span>

                                                <?php if ($t['biaya_tambahan'] > 0 || $t['diskon'] > 0 || $t['pajak'] > 0): ?>
                                                    <div class="text-secondary mt-1" style="font-size: 11px;">
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
                                                <div class="fw-bold text-dark mt-1" style="font-size: 12px;">
                                                    Total: Rp <?= number_format($grand_total, 0, ',', '.'); ?>
                                                </div>
                                            </td>
                                            <td class="small text-secondary"><?= htmlspecialchars($t['tgl']); ?></td>
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
                                                    <span class="badge bg-success text-uppercase" style="font-size: 10px;">Lunas</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger text-uppercase" style="font-size: 10px;">Belum Lunas</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                                                        Ubah Status
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="dashboard.php?id=<?= $t['id']; ?>&ubah_status=baru">Baru</a></li>
                                                        <li><a class="dropdown-item" href="dashboard.php?id=<?= $t['id']; ?>&ubah_status=proses">Proses</a></li>
                                                        <li><a class="dropdown-item" href="dashboard.php?id=<?= $t['id']; ?>&ubah_status=selesai">Selesai</a></li>
                                                        <li><a class="dropdown-item" href="dashboard.php?id=<?= $t['id']; ?>&ubah_status=diambil">Diambil (Lunas)</a></li>
                                                    </ul>
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