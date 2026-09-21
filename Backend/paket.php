<?php
// Backend/paket.php
require_once __DIR__ . '/components/koneksi.php';
restrict_access(['admin']);

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

// Ambil daftar seluruh outlet untuk pilihan dropdown
try {
    $stmt_list_outlet = $pdo->query("SELECT * FROM tb_outlet ORDER BY nama ASC");
    $semua_outlet = $stmt_list_outlet->fetchAll();
} catch (PDOException $e) {
    $semua_outlet = [];
}

$pesan_sukses = '';
$pesan_error = '';

// 1. Proses Tambah Paket Cucian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_paket'])) {
    $id_outlet  = $_POST['id_outlet'];
    $jenis      = trim($_POST['jenis']);
    $nama_paket = trim($_POST['nama_paket']);
    $harga      = trim($_POST['harga']);

    if (!empty($id_outlet) && !empty($jenis) && !empty($nama_paket) && !empty($harga)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tb_paket (id_outlet, jenis, nama_paket, harga) VALUES (:id_outlet, :jenis, :nama_paket, :harga)");
            $stmt->execute([
                'id_outlet'  => $id_outlet,
                'jenis'      => $jenis,
                'nama_paket' => $nama_paket,
                'harga'      => $harga
            ]);
            $pesan_sukses = "Paket cucian baru berhasil ditambahkan!";
        } catch (PDOException $e) {
            $pesan_error = "Gagal menyimpan data: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Semua field wajib diisi!";
    }
}

// 2. Proses Edit / Update Paket Cucian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_paket'])) {
    $id         = $_POST['id'];
    $id_outlet  = $_POST['id_outlet'];
    $jenis      = trim($_POST['jenis']);
    $nama_paket = trim($_POST['nama_paket']);
    $harga      = trim($_POST['harga']);

    if (!empty($id) && !empty($id_outlet) && !empty($jenis) && !empty($nama_paket) && !empty($harga)) {
        try {
            $stmt = $pdo->prepare("UPDATE tb_paket SET id_outlet = :id_outlet, jenis = :jenis, nama_paket = :nama_paket, harga = :harga WHERE id = :id");
            $stmt->execute([
                'id'         => $id,
                'id_outlet'  => $id_outlet,
                'jenis'      => $jenis,
                'nama_paket' => $nama_paket,
                'harga'      => $harga
            ]);
            $pesan_sukses = "Data paket cucian berhasil diperbarui!";
        } catch (PDOException $e) {
            $pesan_error = "Gagal memperbarui data: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Semua field wajib diisi!";
    }
}

// 3. Proses Hapus Paket Cucian
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tb_paket WHERE id = :id");
        $stmt->execute(['id' => $id]);
        header("Location: paket.php?pesan=hapus_sukses");
        exit();
    } catch (PDOException $e) {
        $pesan_error = "Gagal menghapus paket cucian.";
    }
}

if (isset($_GET['pesan']) && $_GET['pesan'] == 'hapus_sukses') {
    $pesan_sukses = "Data paket cucian berhasil dihapus!";
}

// Ambil semua data paket cucian beserta nama outletnya menggunakan JOIN
try {
    $query = "SELECT tb_paket.*, tb_outlet.nama AS nama_outlet 
              FROM tb_paket 
              LEFT JOIN tb_outlet ON tb_paket.id_outlet = tb_outlet.id 
              ORDER BY tb_paket.id DESC";
    $stmt_list = $pdo->query($query);
    $list_paket = $stmt_list->fetchAll();
} catch (PDOException $e) {
    $list_paket = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Paket Cucian - Laundry App</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .btn-burgundy {
            background-color: var(--burgundy-primary);
            color: #fff;
        }

        .btn-burgundy:hover {
            background-color: var(--burgundy-hover);
            color: #fff;
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
        <!-- Dashboard: Admin & Kasir -->
        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'kasir'])): ?>
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link">
                <i class="bi bi-speedometer2 fs-5"></i> Dashboard
            </a>
        </li>
        <?php endif; ?>

        <!-- Outlet: HANYA ADMIN -->
        <?php if ((isset($role_user) && $role_user === 'admin') || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
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

        <!-- Paket Cucian: HANYA ADMIN (Karena ini halaman paket.php, maka aktif) -->
        <?php if ((isset($role_user) && $role_user === 'admin') || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
        <li class="nav-item">
            <a href="paket.php" class="nav-link active">
                <i class="bi bi-tag fs-5"></i> Paket Cucian
            </a>
        </li>
        <?php endif; ?>

        <!-- Pengguna / Kasir: HANYA ADMIN -->
        <?php if ((isset($role_user) && $role_user === 'admin') || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
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

        <!-- Laporan: Admin, Kasir, & Owner -->
        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'kasir', 'owner'])): ?>
        <li class="nav-item">
            <a href="laporan.php" class="nav-link">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold text-dark">Manajemen Paket Cucian</h3>
                        <p class="text-muted mb-0">Atur jenis layanan dan harga paket laundry untuk setiap outlet.</p>
                    </div>
                    <button type="button" class="btn btn-burgundy rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPaket">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Paket
                    </button>
                </div>

                <!-- Alert Notifikasi -->
                <?php if (!empty($pesan_sukses)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> <?= $pesan_sukses; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pesan_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $pesan_error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Tabel Paket Cucian -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Outlet</th>
                                    <th>Jenis Layanan</th>
                                    <th>Nama Paket</th>
                                    <th>Harga (Rp)</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($list_paket) > 0): ?>
                                    <?php $no = 1; foreach ($list_paket as $p): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td class="fw-semibold text-secondary">
                                                <i class="bi bi-shop me-1 text-danger"></i> <?= htmlspecialchars($p['nama_outlet'] ?? 'Outlet Tidak Ditemukan'); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary text-uppercase" style="font-size: 11px;">
                                                    <?= htmlspecialchars($p['jenis']); ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($p['nama_paket']); ?></td>
                                            <td class="text-success fw-semibold">Rp <?= number_format($p['harga'], 0, ',', '.'); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle btn-edit"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditPaket"
                                                    data-id="<?= htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-id_outlet="<?= htmlspecialchars($p['id_outlet'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-jenis="<?= htmlspecialchars($p['jenis'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-nama_paket="<?= htmlspecialchars($p['nama_paket'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-harga="<?= htmlspecialchars($p['harga'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                                <a href="paket.php?hapus=<?= htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    onclick="return confirm('Yakin ingin menghapus paket cucian ini?');"
                                                    class="btn btn-sm btn-outline-danger rounded-circle ms-1">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-tag fs-2 d-block mb-2"></i>
                                            Belum ada data paket cucian.
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

    <!-- Modal Tambah Paket Cucian -->
    <div class="modal fade" id="modalTambahPaket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="" method="POST">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark">Tambah Paket Cucian Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Pilih Outlet</label>
                            <select name="id_outlet" class="form-select" required>
                                <option value="">-- Pilih Outlet Cabang --</option>
                                <?php foreach ($semua_outlet as $ot): ?>
                                    <option value="<?= $ot['id']; ?>"><?= htmlspecialchars($ot['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Jenis Layanan</label>
                            <select name="jenis" class="form-select" required>
                                <option value="">-- Pilih Jenis Layanan --</option>
                                <option value="kiloan">Kiloan</option>
                                <option value="selimut">Selimut</option>
                                <option value="bed_cover">Bed Cover</option>
                                <option value="kaos">Kaos</option>
                                <option value="lain">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Nama Paket</label>
                            <input type="text" class="form-control" name="nama_paket" required placeholder="Contoh: Paket Reguler 3 Hari">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Harga (Rupiah)</label>
                            <input type="number" class="form-control" name="harga" required placeholder="Contoh: 6000">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_paket" class="btn btn-burgundy rounded-pill px-4">Simpan Paket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Paket Cucian -->
    <div class="modal fade" id="modalEditPaket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="" method="POST">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark">Edit Data Paket Cucian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Pilih Outlet</label>
                            <select name="id_outlet" id="edit-id_outlet" class="form-select" required>
                                <option value="">-- Pilih Outlet Cabang --</option>
                                <?php foreach ($semua_outlet as $ot): ?>
                                    <option value="<?= $ot['id']; ?>"><?= htmlspecialchars($ot['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Jenis Layanan</label>
                            <select name="jenis" id="edit-jenis" class="form-select" required>
                                <option value="kiloan">Kiloan</option>
                                <option value="selimut">Selimut</option>
                                <option value="bed_cover">Bed Cover</option>
                                <option value="kaos">Kaos</option>
                                <option value="lain">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Nama Paket</label>
                            <input type="text" class="form-control" id="edit-nama_paket" name="nama_paket" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Harga (Rupiah)</label>
                            <input type="number" class="form-control" id="edit-harga" name="harga" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_paket" class="btn btn-burgundy rounded-pill px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalEditPaket = document.getElementById('modalEditPaket');
        modalEditPaket.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('edit-id').value = button.getAttribute('data-id');
            document.getElementById('edit-id_outlet').value = button.getAttribute('data-id_outlet');
            document.getElementById('edit-jenis').value = button.getAttribute('data-jenis');
            document.getElementById('edit-nama_paket').value = button.getAttribute('data-nama_paket');
            document.getElementById('edit-harga').value = button.getAttribute('data-harga');
        });
    </script>
</body>

</html>