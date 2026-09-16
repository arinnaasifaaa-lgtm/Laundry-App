<?php
// Backend/user.php
require_once __DIR__ . '/components/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Silakan login terlebih dahulu!");
    exit();
}

// Batasi akses: Hanya ADMIN yang boleh mengelola pengguna
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php?error=Akses ditolak! Halaman khusus Administrator.");
    exit();
}

$nama_user = $_SESSION['nama'] ?? 'Administrator';
$role_user = $_SESSION['role'] ?? 'admin';
$id_outlet_user = $_SESSION['id_outlet'] ?? 1;

// Ambil nama outlet aktif
try {
    $stmt_outlet = $pdo->prepare("SELECT nama FROM tb_outlet WHERE id = :id");
    $stmt_outlet->execute(['id' => $id_outlet_user]);
    $outlet_active = $stmt_outlet->fetch();
    $nama_outlet = $outlet_active['nama'] ?? 'Outlet Utama';
} catch (PDOException $e) {
    $nama_outlet = 'Outlet Utama';
}

$pesan_sukses = '';
$pesan_error = '';

// 1. Proses Tambah User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_user'])) {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Disarankan di-hash, tapi sesuai modul standar aplikasi laundry SMK biasanya plain/md5/password_hash. Kita pakai password_hash agar aman.
    $id_outlet = $_POST['id_outlet'];
    $role = $_POST['role'];

    if (!empty($nama) && !empty($username) && !empty($password)) {
        try {
            // Cek apakah username sudah ada
            $stmt_cek = $pdo->prepare("SELECT id FROM tb_user WHERE username = :username");
            $stmt_cek->execute(['username' => $username]);
            if ($stmt_cek->rowCount() > 0) {
                $pesan_error = "Username sudah digunakan, pilih username lain!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO tb_user (nama, username, password, id_outlet, role) VALUES (:nama, :username, :password, :id_outlet, :role)");
                $stmt->execute([
                    'nama' => $nama,
                    'username' => $username,
                    'password' => $hashed_password,
                    'id_outlet' => $id_outlet,
                    'role' => $role
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

// 2. Proses Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = $_POST['id'];
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $id_outlet = $_POST['id_outlet'];
    $role = $_POST['role'];

    if (!empty($nama) && !empty($username)) {
        try {
            if (!empty($password)) {
                // Jika password diisi, update beserta passwordnya
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE tb_user SET nama = :nama, username = :username, password = :password, id_outlet = :id_outlet, role = :role WHERE id = :id");
                $stmt->execute([
                    'id' => $id,
                    'nama' => $nama,
                    'username' => $username,
                    'password' => $hashed_password,
                    'id_outlet' => $id_outlet,
                    'role' => $role
                ]);
            } else {
                // Jika password kosong, jangan ubah password
                $stmt = $pdo->prepare("UPDATE tb_user SET nama = :nama, username = :username, id_outlet = :id_outlet, role = :role WHERE id = :id");
                $stmt->execute([
                    'id' => $id,
                    'nama' => $nama,
                    'username' => $username,
                    'id_outlet' => $id_outlet,
                    'role' => $role
                ]);
            }
            $pesan_sukses = "Data pengguna berhasil diperbarui!";
        } catch (PDOException $e) {
            $pesan_error = "Gagal memperbarui data: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Nama dan username wajib diisi!";
    }
}

// 3. Proses Hapus User
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Cegah hapus akun sendiri
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
    $pesan_sukses = "Pengguna berhasil dihapus!";
}

// Ambil daftar user dan nama outletnya pakai JOIN
try {
    $query = "SELECT tb_user.*, tb_outlet.nama AS nama_outlet_user 
              FROM tb_user 
              LEFT JOIN tb_outlet ON tb_user.id_outlet = tb_outlet.id 
              ORDER BY tb_user.id DESC";
    $stmt_list = $pdo->query($query);
    $list_user = $stmt_list->fetchAll();

    // Ambil juga list outlet untuk dropdown pilihan di modal
    $stmt_opt_outlet = $pdo->query("SELECT * FROM tb_outlet ORDER BY nama ASC");
    $options_outlet = $stmt_opt_outlet->fetchAll();
} catch (PDOException $e) {
    $list_user = [];
    $options_outlet = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Laundry App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --burgundy-primary: #800020;
            --burgundy-hover: #600018;
            --burgundy-light: #fcf1f3;
        }
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        #sidebar { min-width: 260px; max-width: 260px; background-color: #ffffff; border-right: 1px solid #ebd3d7; min-height: 100vh; }
        #sidebar .sidebar-brand { padding: 1.5rem; font-weight: 700; color: var(--burgundy-primary); border-bottom: 1px solid #f0e6e8; }
        #sidebar .nav-link { color: #495057; padding: 0.85rem 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 0.75rem; transition: all 0.2s ease; }
        #sidebar .nav-link:hover, #sidebar .nav-link.active { background-color: var(--burgundy-light); color: var(--burgundy-primary); border-left: 4px solid var(--burgundy-primary); }
        .navbar-top { background-color: #ffffff; border-bottom: 1px solid #ebd3d7; padding: 1rem 2rem; }
        .bg-burgundy-soft { background-color: var(--burgundy-light); color: var(--burgundy-primary); }
        .btn-burgundy { background-color: var(--burgundy-primary); color: #fff; }
        .btn-burgundy:hover { background-color: var(--burgundy-hover); color: #fff; }
        .table-custom th { background-color: #fcf1f3; color: var(--burgundy-primary); font-weight: 600; }
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
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2 fs-5"></i> Dashboard</a></li>
                <li class="nav-item"><a href="outlet.php" class="nav-link"><i class="bi bi-shop fs-5"></i> Outlet</a></li>
                <li class="nav-item"><a href="user.php" class="nav-link active"><i class="bi bi-person-badge fs-5"></i> Pengguna</a></li>
                <li class="nav-item"><a href="member.php" class="nav-link"><i class="bi bi-people fs-5"></i> Member</a></li>
                <li class="nav-item"><a href="paket.php" class="nav-link"><i class="bi bi-tag fs-5"></i> Paket Cucian</a></li>
                <li class="nav-item"><a href="transaksi.php" class="nav-link"><i class="bi bi-cart-check fs-5"></i> Transaksi</a></li>
                <li class="nav-item mt-4"><a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right fs-5"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Wrapper -->
        <div id="content" class="w-100 p-0">
            <!-- Top Navbar -->
            <nav class="navbar navbar-top navbar-expand mb-4">
                <div class="container-fluid">
                    <span class="badge bg-burgundy-soft px-3 py-2"><i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($nama_outlet); ?></span>
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

            <!-- Page Content -->
            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold text-dark">Manajemen Pengguna (User)</h3>
                        <p class="text-muted mb-0">Kelola akun Admin, Kasir, dan Owner.</p>
                    </div>
                    <button type="button" class="btn btn-burgundy rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Pengguna
                    </button>
                </div>

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

                <!-- Tabel User -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Role / Hak Akses</th>
                                    <th>Outlet Tugas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($list_user) > 0): ?>
                                    <?php $no = 1; foreach ($list_user as $u): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($u['nama']); ?></td>
                                            <td><code><?= htmlspecialchars($u['username']); ?></code></td>
                                            <td>
                                                <span class="badge bg-secondary text-uppercase" style="font-size: 11px;">
                                                    <?= htmlspecialchars($u['role']); ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($u['nama_outlet_user'] ?? 'Outlet Tidak Ditemukan'); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary rounded-circle btn-edit" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalEditUser"
                                                        data-id="<?= $u['id']; ?>"
                                                        data-nama="<?= htmlspecialchars($u['nama']); ?>"
                                                        data-username="<?= htmlspecialchars($u['username']); ?>"
                                                        data-role="<?= $u['role']; ?>"
                                                        data-outlet="<?= $u['id_outlet']; ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="user.php?hapus=<?= $u['id']; ?>" onclick="return confirm('Yakin ingin menghapus pengguna ini?');" class="btn btn-sm btn-outline-danger rounded-circle">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada pengguna terdaftar.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Tambah User -->
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
                            <input type="text" class="form-control" name="nama" required placeholder="Nama pegawai">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Username</label>
                            <input type="text" class="form-control" name="username" required placeholder="Username login">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Password</label>
                            <input type="password" class="form-control" name="password" required placeholder="Password akun">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Outlet Penugasan</label>
                            <select class="form-select" name="id_outlet" required>
                                <?php foreach ($options_outlet as $opt): ?>
                                    <option value="<?= $opt['id']; ?>"><?= htmlspecialchars($opt['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Role / Hak Akses</label>
                            <select class="form-select" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="kasir">Kasir</option>
                                <option value="owner">Owner</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_user" class="btn btn-burgundy rounded-pill px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit User -->
    <div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="" method="POST">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark">Edit Pengguna</h5>
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
                            <label class="form-label fw-semibold small">Password Baru <span class="text-muted fw-normal">(Kosongkan jika tidak diubah)</span></label>
                            <input type="password" class="form-control" name="password" placeholder="Isi hanya jika ingin mengganti password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Outlet Penugasan</label>
                            <select class="form-select" id="edit-outlet" name="id_outlet" required>
                                <?php foreach ($options_outlet as $opt): ?>
                                    <option value="<?= $opt['id']; ?>"><?= htmlspecialchars($opt['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Role / Hak Akses</label>
                            <select class="form-select" id="edit-role" name="role" required>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalEditUser = document.getElementById('modalEditUser');
        modalEditUser.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('edit-id').value = button.getAttribute('data-id');
            document.getElementById('edit-nama').value = button.getAttribute('data-nama');
            document.getElementById('edit-username').value = button.getAttribute('data-username');
            document.getElementById('edit-outlet').value = button.getAttribute('data-outlet');
            document.getElementById('edit-role').value = button.getAttribute('data-role');
        });
    </script>
</body>
</html>