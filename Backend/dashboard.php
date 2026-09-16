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
$id_outlet = $_SESSION['id_outlet'] ?? 1;

// Ambil informasi nama outlet dari database
try {
    $stmt_outlet = $pdo->prepare("SELECT nama FROM tb_outlet WHERE id = :id");
    $stmt_outlet->execute(['id' => $id_outlet]);
    $outlet = $stmt_outlet->fetch();
    $nama_outlet = $outlet['nama'] ?? 'Outlet Utama';
} catch (PDOException $e) {
    $nama_outlet = 'Outlet Utama';
}

// Inisialisasi variabel statistik
$total_pelanggan = 0;
$cucian_diproses = 0;
$cucian_selesai = 0;
$transaksi_terbaru = [];

try {
    // 1. Hitung Total Pelanggan / Member
    $stmt_member = $pdo->prepare("SELECT COUNT(*) FROM tb_member");
    $stmt_member->execute();
    $total_pelanggan = $stmt_member->fetchColumn();

    // 2. Hitung Cucian Diproses (status = 'baru' atau 'proses')
    $stmt_proses = $pdo->prepare("SELECT COUNT(*) FROM tb_transaksi WHERE id_outlet = :id_outlet AND (status = 'baru' OR status = 'proses')");
    $stmt_proses->execute(['id_outlet' => $id_outlet]);
    $cucian_diproses = $stmt_proses->fetchColumn();

    // 3. Hitung Selesai / Siap Diambil (status = 'selesai')
    $stmt_selesai = $pdo->prepare("SELECT COUNT(*) FROM tb_transaksi WHERE id_outlet = :id_outlet AND status = 'selesai'");
    $stmt_selesai->execute(['id_outlet' => $id_outlet]);
    $cucian_selesai = $stmt_selesai->fetchColumn();

    // 4. Ambil Riwayat Transaksi Terbaru (limit 5 transaksi terakhir)
    $stmt_transaksi = $pdo->prepare("
        SELECT t.id, t.kode_invoice, t.tgl, t.status, t.dibayar, m.nama as nama_pelanggan 
        FROM tb_transaksi t 
        JOIN tb_member m ON t.id_member = m.id 
        WHERE t.id_outlet = :id_outlet 
        ORDER BY t.tgl DESC LIMIT 5
    ");
    $stmt_transaksi->execute(['id_outlet' => $id_outlet]);
    $transaksi_terbaru = $stmt_transaksi->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Tangani error query jika tabel belum ada
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Laundry App</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        /* Sidebar Styling */
        #sidebar {
            min-width: 260px;
            max-width: 260px;
            background-color: #ffffff;
            border-right: 1px solid #ebd3d7;
            min-height: 100vh;
            transition: all 0.3s;
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
        #sidebar .nav-link:hover, #sidebar .nav-link.active {
            background-color: var(--burgundy-light);
            color: var(--burgundy-primary);
            border-left: 4px solid var(--burgundy-primary);
        }
        /* Main Content Styling */
        #content {
            width: 100%;
            padding: 2rem;
        }
        .navbar-top {
            background-color: #ffffff;
            border-bottom: 1px solid #ebd3d7;
            padding: 1rem 2rem;
        }
        .card-stat {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.04);
            transition: transform 0.2s;
        }
        .card-stat:hover {
            transform: translateY(-3px);
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
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="bi bi-speedometer2 fs-5"></i> Dashboard
                    </a>
                </li>

                <!-- Menu khusus ADMIN: Outlet & Pengguna -->
                <?php if ($role_user === 'admin'): ?>
                <li class="nav-item">
                    <a href="outlet.php" class="nav-link">
                        <i class="bi bi-shop fs-5"></i> Outlet
                    </a>
                </li>
                <li class="nav-item">
                    <a href="user.php" class="nav-link">
                        <i class="bi bi-person-badge fs-5"></i> Pengguna
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a href="member.php" class="nav-link">
                        <i class="bi bi-people fs-5"></i> Member
                    </a>
                </li>

                <?php if ($role_user === 'admin'): ?>
                <li class="nav-item">
                    <a href="paket.php" class="nav-link">
                        <i class="bi bi-tag fs-5"></i> Paket Cucian
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a href="transaksi.php" class="nav-link">
                        <i class="bi bi-cart-check fs-5"></i> Transaksi
                    </a>
                </li>
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
                        <span class="badge bg-burgundy-soft px-3 py-2">
                            <i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($nama_outlet); ?>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end">
                            <span class="d-block fw-bold text-dark small"><?= htmlspecialchars($nama_user); ?></span>
                            <span class="badge bg-secondary text-uppercase" style="font-size: 10px;"><?= htmlspecialchars($role_user); ?></span>
                        </div>
                        <div class="bg-burgundy-soft rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                            <?= strtoupper(substr($nama_user, 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content / Body -->
            <div class="container-fluid px-4">
                <div class="mb-4">
                    <h3 class="fw-bold text-dark">Dashboard Operasional</h3>
                    <p class="text-muted">Pantau status cucian, member, dan transaksi laundry di sini.</p>
                </div>

                <!-- Statistik Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card card-stat p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-burgundy-soft p-3 rounded-3 fs-3 me-3">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Total Pelanggan</h6>
                                    <h3 class="fw-bold mb-0"><?= number_format($total_pelanggan); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stat p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-burgundy-soft p-3 rounded-3 fs-3 me-3">
                                    <i class="bi bi-arrow-repeat"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Cucian Diproses</h6>
                                    <h3 class="fw-bold mb-0"><?= number_format($cucian_diproses); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stat p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-burgundy-soft p-3 rounded-3 fs-3 me-3">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Selesai / Siap Diambil</h6>
                                    <h3 class="fw-bold mb-0"><?= number_format($cucian_selesai); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Transaksi Terbaru -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-danger me-2"></i>Riwayat Transaksi Terbaru</h5>
                        <a href="transaksi.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Lihat Semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-custom">
                            <thead>
                                <tr>
                                    <th># Invoice</th>
                                    <th>Nama Pelanggan</th>
                                    <th>Tgl Masuk</th>
                                    <th>Status Cucian</th>
                                    <th>Status Pembayaran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($transaksi_terbaru)): ?>
                                    <?php foreach ($transaksi_terbaru as $row): ?>
                                        <tr>
                                            <td class="fw-bold text-danger"><?= htmlspecialchars($row['kode_invoice']); ?></td>
                                            <td><?= htmlspecialchars($row['nama_pelanggan']); ?></td>
                                            <td><?= htmlspecialchars($row['tgl']); ?></td>
                                            <td>
                                                <?php 
                                                    $status = $row['status'];
                                                    $badge_bg = 'bg-secondary';
                                                    if ($status == 'baru') $badge_bg = 'bg-info text-dark';
                                                    elseif ($status == 'proses') $badge_bg = 'bg-warning text-dark';
                                                    elseif ($status == 'selesai') $badge_bg = 'bg-primary';
                                                    elseif ($status == 'diambil') $badge_bg = 'bg-success';
                                                ?>
                                                <span class="badge <?= $badge_bg; ?> text-uppercase" style="font-size: 11px;"><?= htmlspecialchars($status); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($row['dibayar'] == 'dibayar'): ?>
                                                    <span class="badge bg-success" style="font-size: 11px;">Lunas</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger" style="font-size: 11px;">Belum Dibayar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="transaksi.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-light text-dark border" title="Detail Transaksi">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            Belum ada data transaksi yang tercatat di outlet ini.
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

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>