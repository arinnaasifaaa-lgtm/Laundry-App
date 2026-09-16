<?php
// Backend/paket.php
require_once __DIR__ . '/components/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Silakan login terlebih dahulu!");
    exit();
}

$nama_user = $_SESSION['nama'] ?? 'Administrator';
$role_user = $_SESSION['role'] ?? 'admin';
$id_outlet = $_SESSION['id_outlet'] ?? 1;

// Ambil informasi nama outlet
try {
    $stmt_outlet = $pdo->prepare("SELECT nama FROM tb_outlet WHERE id = :id");
    $stmt_outlet->execute(['id' => $id_outlet]);
    $outlet = $stmt_outlet->fetch();
    $nama_outlet = $outlet['nama'] ?? 'Outlet Utama';
} catch (PDOException $e) {
    $nama_outlet = 'Outlet Utama';
}

$pesan_sukses = '';
$pesan_error = '';

// 1. Proses Tambah Paket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_paket'])) {
    $jenis = $_POST['jenis'];
    $nama_paket = trim($_POST['nama_paket']);
    $harga = trim($_POST['harga']);

    if (!empty($nama_paket) && !empty($harga)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tb_paket (id_outlet, jenis, nama_paket, harga) VALUES (:id_outlet, :jenis, :nama_paket, :harga)");
            $stmt->execute([
                'id_outlet' => $id_outlet,
                'jenis' => $jenis,
                'nama_paket' => $nama_paket,
                'harga' => $harga
            ]);
            $pesan_sukses = "Paket cucian baru berhasil ditambahkan!";
        } catch (PDOException $e) {
            $pesan_error = "Gagal menyimpan data: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Nama paket dan harga wajib diisi!";
    }
}

// 2. Proses Edit / Update Paket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_paket'])) {
    $id = $_POST['id'];
    $jenis = $_POST['jenis'];
    $nama_paket = trim($_POST['nama_paket']);
    $harga = trim($_POST['harga']);

    if (!empty($nama_paket) && !empty($harga)) {
        try {
            $stmt = $pdo->prepare("UPDATE tb_paket SET jenis = :jenis, nama_paket = :nama_paket, harga = :harga WHERE id = :id");
            $stmt->execute([
                'id' => $id,
                'jenis' => $jenis,
                'nama_paket' => $nama_paket,
                'harga' => $harga
            ]);
            $pesan_sukses = "Paket cucian berhasil diperbarui!";
        } catch (PDOException $e) {
            $pesan_error = "Gagal memperbarui data: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Nama paket dan harga wajib diisi!";
    }
}

// 3. Proses Hapus Paket
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tb_paket WHERE id = :id");
        $stmt->execute(['id' => $id]);
        header("Location: paket.php?pesan=hapus_sukses");
        exit();
    } catch (PDOException $e) {
        $pesan_error = "Gagal menghapus data (kemungkinan data sedang digunakan di transaksi).";
    }
}

if (isset($_GET['pesan']) && $_GET['pesan'] == 'hapus_sukses') {
    $pesan_sukses = "Paket cucian berhasil dihapus!";
}

// Ambil data paket dari database berdasarkan outlet user yang login
try {
    $stmt_paket = $pdo->prepare("SELECT * FROM tb_paket WHERE id_outlet = :id_outlet ORDER BY id DESC");
    $stmt_paket->execute(['id_outlet' => $id_outlet]);
    $list_paket = $stmt_paket->fetchAll();
} catch (PDOException $e) {
    $list_paket = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paket Cucian - Laundry App</title>
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
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="bi bi-speedometer2 fs-5"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="member.php" class="nav-link">
                        <i class="bi bi-people fs-5"></i> Member
                    </a>
                </li>
                <li class="nav-item">
                    <a href="paket.php" class="nav-link active">
                        <i class="bi bi-tag fs-5"></i> Paket Cucian
                    </a>
                </li>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold text-dark">Manajemen Paket Cucian</h3>
                        <p class="text-muted mb-0">Atur jenis layanan dan harga paket laundry untuk outlet ini.</p>
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

                <!-- Tabel Paket -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
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
                                            <td>
                                                <span class="badge bg-secondary text-uppercase" style="font-size: 11px;">
                                                    <?= htmlspecialchars($p['jenis']); ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($p['nama_paket']); ?></td>
                                            <td class="text-success fw-bold">Rp <?= number_format($p['harga'], 0, ',', '.'); ?></td>
                                            <td>
                                                <!-- Tombol Edit Paket -->
                                                <button class="btn btn-sm btn-outline-primary rounded-circle btn-edit" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalEditPaket"
                                                        data-id="<?= $p['id']; ?>"
                                                        data-jenis="<?= $p['jenis']; ?>"
                                                        data-nama="<?= htmlspecialchars($p['nama_paket']); ?>"
                                                        data-harga="<?= $p['harga']; ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <!-- Tombol Hapus Paket -->
                                                <a href="paket.php?hapus=<?= $p['id']; ?>" onclick="return confirm('Yakin ingin menghapus paket ini?');" class="btn btn-sm btn-outline-danger rounded-circle">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="bi bi-tag fs-2 d-block mb-2"></i>
                                            Belum ada paket cucian yang terdaftar di outlet ini.
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

    <!-- Modal Tambah Paket -->
    <div class="modal fade" id="modalTambahPaket" tabindex="-1" aria-labelledby="modalTambahPaketLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="" method="POST">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="modalTambahPaketLabel">Tambah Paket Cucian Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="jenis" class="form-label fw-semibold small">Jenis Layanan</label>
                            <select class="form-select" id="jenis" name="jenis">
                                <option value="kiloan">Kiloan</option>
                                <option value="selimut">Selimut</option>
                                <option value="bed_cover">Bed Cover</option>
                                <option value="kaos">Kaos</option>
                                <option value="lain">Lain-lain</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nama_paket" class="form-label fw-semibold small">Nama Paket</label>
                            <input type="text" class="form-control" id="nama_paket" name="nama_paket" required placeholder="Contoh: Paket Reguler 3 Hari">
                        </div>
                        <div class="mb-3">
                            <label for="harga" class="form-label fw-semibold small">Harga (Rupiah)</label>
                            <input type="number" class="form-control" id="harga" name="harga" required placeholder="Contoh: 6000">
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

    <!-- Modal Edit Paket -->
    <div class="modal fade" id="modalEditPaket" tabindex="-1" aria-labelledby="modalEditPaketLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="" method="POST">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="modalEditPaketLabel">Edit Paket Cucian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit-jenis" class="form-label fw-semibold small">Jenis Layanan</label>
                            <select class="form-select" id="edit-jenis" name="jenis">
                                <option value="kiloan">Kiloan</option>
                                <option value="selimut">Selimut</option>
                                <option value="bed_cover">Bed Cover</option>
                                <option value="kaos">Kaos</option>
                                <option value="lain">Lain-lain</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-nama-paket" class="form-label fw-semibold small">Nama Paket</label>
                            <input type="text" class="form-control" id="edit-nama-paket" name="nama_paket" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-harga" class="form-label fw-semibold small">Harga (Rupiah)</label>
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
        // Script untuk melempar data dari tombol edit paket ke dalam modal edit
        const modalEditPaket = document.getElementById('modalEditPaket');
        modalEditPaket.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            const id = button.getAttribute('data-id');
            const jenis = button.getAttribute('data-jenis');
            const nama = button.getAttribute('data-nama');
            const harga = button.getAttribute('data-harga');

            document.getElementById('edit-id').value = id;
            document.getElementById('edit-jenis').value = jenis;
            document.getElementById('edit-nama-paket').value = nama;
            document.getElementById('edit-harga').value = harga;
        });
    </script>
</body>
</html>