<?php
// Backend/user.php
require_once __DIR__ . '/components/koneksi.php';
restrict_access(['admin']);

// Cek apakah user sudah login dan memiliki hak akses (biasanya hanya admin)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Silakan login terlebih dahulu!");
    exit();
}

$nama_user = $_SESSION['nama'] ?? 'Administrator';
$role_user = $_SESSION['role'] ?? 'admin';
$id_outlet_user = $_SESSION['id_outlet'] ?? 1;

// Batasi akses jika bukan admin (opsional, sesuaikan kebutuhan)
if ($role_user !== 'admin') {
    header("Location: dashboard.php?error=Akses ditolak! Halaman khusus Admin.");
    exit();
}

// Ambil informasi nama outlet aktif berdasarkan user yang sedang login di database
try {
    $stmt_outlet = $pdo->prepare("SELECT tb_outlet.nama FROM tb_user JOIN tb_outlet ON tb_user.id_outlet = tb_outlet.id WHERE tb_user.id = :id_user");
    $stmt_outlet->execute(['id_user' => $_SESSION['user_id']]);
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

// 1. Proses Tambah User / Karyawan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_user'])) {
    $nama      = trim($_POST['nama']);
    $username  = trim($_POST['username']);
    $password  = $_POST['password'];
    $id_outlet = $_POST['id_outlet'];
    $role      = $_POST['role'];

    if (!empty($nama) && !empty($username) && !empty($password) && !empty($id_outlet) && !empty($role)) {
        try {
            // Cek apakah username sudah digunakan
            $stmt_check = $pdo->prepare("SELECT id FROM tb_user WHERE username = :username");
            $stmt_check->execute(['username' => $username]);
            if ($stmt_check->rowCount() > 0) {
                $pesan_error = "Username '$username' sudah terdaftar, gunakan username lain!";
            } else {
                // Hash password menggunakan password_hash agar aman
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO tb_user (nama, username, password, id_outlet, role) VALUES (:nama, :username, :password, :id_outlet, :role)");
                $stmt->execute([
                    'nama'      => $nama,
                    'username'  => $username,
                    'password'  => $hashed_password,
                    'id_outlet' => $id_outlet,
                    'role'      => $role
                ]);
                $pesan_sukses = "Pengguna baru berhasil ditambahkan!";
            }
        } catch (PDOException $e) {
            $pesan_error = "Gagal menyimpan data: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Semua field wajib diisi!";
    }
}

// 2. Proses Edit / Update User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id        = $_POST['id'];
    $nama      = trim($_POST['nama']);
    $username  = trim($_POST['username']);
    $password  = $_POST['password'];
    $id_outlet = $_POST['id_outlet'];
    $role      = $_POST['role'];

    if (!empty($id) && !empty($nama) && !empty($username) && !empty($id_outlet) && !empty($role)) {
        try {
            if (!empty($password)) {
                // Jika password diisi, update beserta password baru
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE tb_user SET nama = :nama, username = :username, password = :password, id_outlet = :id_outlet, role = :role WHERE id = :id");
                $stmt->execute([
                    'id'        => $id,
                    'nama'      => $nama,
                    'username'  => $username,
                    'password'  => $hashed_password,
                    'id_outlet' => $id_outlet,
                    'role'      => $role
                ]);
            } else {
                // Jika password kosong, jangan ubah password lama
                $stmt = $pdo->prepare("UPDATE tb_user SET nama = :nama, username = :username, id_outlet = :id_outlet, role = :role WHERE id = :id");
                $stmt->execute([
                    'id'        => $id,
                    'nama'      => $nama,
                    'username'  => $username,
                    'id_outlet' => $id_outlet,
                    'role'      => $role
                ]);
            }
            $pesan_sukses = "Data pengguna berhasil diperbarui!";
        } catch (PDOException $e) {
            $pesan_error = "Gagal memperbarui data: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Field utama wajib diisi!";
    }
}

// 3. Proses Hapus User
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Mencegah user menghapus akun sendiri yang sedang aktif login
    if ($id == $_SESSION['user_id']) {
        $pesan_error = "Anda tidak dapat menghapus akun yang sedang digunakan saat ini!";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM tb_user WHERE id = :id");
            $stmt->execute(['id' => $id]);
            header("Location: user.php?pesan=hapus_sukses");
            exit();
        } catch (PDOException $e) {
            $pesan_error = "Gagal menghapus pengguna.";
        }
    }
}

if (isset($_GET['pesan']) && $_GET['pesan'] == 'hapus_sukses') {
    $pesan_sukses = "Data pengguna berhasil dihapus!";
}

// Ambil semua data user beserta nama outletnya menggunakan JOIN
try {
    $query = "SELECT tb_user.*, tb_outlet.nama AS nama_outlet 
              FROM tb_user 
              LEFT JOIN tb_outlet ON tb_user.id_outlet = tb_outlet.id 
              ORDER BY tb_user.id DESC";
    $stmt_list = $pdo->query($query);
    $list_user = $stmt_list->fetchAll();
} catch (PDOException $e) {
    $list_user = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Laundry App</title>
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

        <!-- Paket Cucian: HANYA ADMIN -->
        <?php if ((isset($role_user) && $role_user === 'admin') || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
        <li class="nav-item">
            <a href="paket.php" class="nav-link">
                <i class="bi bi-tag fs-5"></i> Paket Cucian
            </a>
        </li>
        <?php endif; ?>

        <!-- Pengguna / Kasir: HANYA ADMIN (Karena ini halaman user.php, maka aktif) -->
        <?php if ((isset($role_user) && $role_user === 'admin') || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
        <li class="nav-item">
            <a href="user.php" class="nav-link active">
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
                        <h3 class="fw-bold text-dark">Manajemen Pengguna</h3>
                        <p class="text-muted mb-0">Kelola akun administrator, kasir, dan owner yang memiliki akses ke aplikasi.</p>
                    </div>
                    <button type="button" class="btn btn-burgundy rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                        <i class="bi bi-person-plus-fill me-1"></i> Tambah Pengguna
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

                <!-- Tabel Pengguna -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Outlet</th>
                                    <th>Role / Hak Akses</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($list_user) > 0): ?>
                                    <?php $no = 1; foreach ($list_user as $u): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($u['nama']); ?></td>
                                            <td><code class="text-secondary"><?= htmlspecialchars($u['username']); ?></code></td>
                                            <td>
                                                <i class="bi bi-shop me-1 text-danger"></i> <?= htmlspecialchars($u['nama_outlet'] ?? 'Outlet Tidak Ditemukan'); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $badge_color = 'bg-secondary';
                                                    if ($u['role'] === 'admin') $badge_color = 'bg-danger';
                                                    elseif ($u['role'] === 'kasir') $badge_color = 'bg-primary';
                                                    elseif ($u['role'] === 'owner') $badge_color = 'bg-success';
                                                ?>
                                                <span class="badge <?= $badge_color; ?> text-uppercase" style="font-size: 11px;">
                                                    <?= htmlspecialchars($u['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle btn-edit"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditUser"
                                                    data-id="<?= htmlspecialchars($u['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-nama="<?= htmlspecialchars($u['nama'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-username="<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-id_outlet="<?= htmlspecialchars($u['id_outlet'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-role="<?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                                <a href="user.php?hapus=<?= htmlspecialchars($u['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    onclick="return confirm('Yakin ingin menghapus pengguna ini?');"
                                                    class="btn btn-sm btn-outline-danger rounded-circle ms-1">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-person-badge fs-2 d-block mb-2"></i>
                                            Belum ada data pengguna.
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

    <!-- Modal Tambah Pengguna -->
    <div class="modal fade" id="modalTambahUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="" method="POST">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark">Tambah Pengguna Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama" required placeholder="Contoh: Budi Santoso">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Username</label>
                            <input type="text" class="form-control" name="username" required placeholder="Contoh: kasir1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Password</label>
                            <input type="password" class="form-control" name="password" required placeholder="Masukkan password akun">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Penempatan Outlet</label>
                            <select name="id_outlet" class="form-select" required>
                                <option value="">-- Pilih Outlet Cabang --</option>
                                <?php foreach ($semua_outlet as $ot): ?>
                                    <option value="<?= $ot['id']; ?>"><?= htmlspecialchars($ot['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Role / Hak Akses</label>
                            <select name="role" class="form-select" required>
                                <option value="">-- Pilih Role --</option>
                                <option value="admin">Admin</option>
                                <option value="kasir">Kasir</option>
                                <option value="owner">Owner</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_user" class="btn btn-burgundy rounded-pill px-4">Simpan Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Pengguna -->
    <div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="" method="POST">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark">Edit Data Pengguna</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Nama Lengkap</label>
                            <input type="text" class="form-control" id="edit-nama" name="nama" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Username</label>
                            <input type="text" class="form-control" id="edit-username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Password Baru <span class="text-muted fw-normal">(Kosongkan jika tidak ingin diubah)</span></label>
                            <input type="password" class="form-control" name="password" placeholder="Biarkan kosong jika tetap">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Penempatan Outlet</label>
                            <select name="id_outlet" id="edit-id_outlet" class="form-select" required>
                                <option value="">-- Pilih Outlet Cabang --</option>
                                <?php foreach ($semua_outlet as $ot): ?>
                                    <option value="<?= $ot['id']; ?>"><?= htmlspecialchars($ot['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Role / Hak Akses</label>
                            <select name="role" id="edit-role" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="kasir">Kasir</option>
                                <option value="owner">Owner</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_user" class="btn btn-burgundy rounded-pill px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalEditUser = document.getElementById('modalEditUser');
        modalEditUser.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('edit-id').value = button.getAttribute('data-id');
            document.getElementById('edit-nama').value = button.getAttribute('data-nama');
            document.getElementById('edit-username').value = button.getAttribute('data-username');
            document.getElementById('edit-id_outlet').value = button.getAttribute('data-id_outlet');
            document.getElementById('edit-role').value = button.getAttribute('data-role');
        });
    </script>
</body>

</html>