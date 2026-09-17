<?php
// Backend/transaksi.php
require_once __DIR__ . '/components/koneksi.php';
restrict_access(['admin', 'kasir']);

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

$pesan_sukses = '';
$pesan_error = '';

// 1. Proses Tambah Transaksi Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_transaksi'])) {
    $id_outlet      = $id_outlet_user;
    $id_member      = $_POST['id_member'];
    $id_paket       = $_POST['id_paket'];
    $tgl            = date('Y-m-d H:i:s');
    $batas_waktu    = date('Y-m-d H:i:s', strtotime('+3 days'));
    
    $dibayar        = $_POST['dibayar']; 
    $tgl_bayar      = ($dibayar === 'dibayar') ? date('Y-m-d H:i:s') : NULL;
    
    $qty            = floatval($_POST['qty']); 
    $biaya_tambahan = floatval($_POST['biaya_tambahan'] ?? 0);
    $diskon         = abs(floatval($_POST['diskon'] ?? 0)); 
    $pajak          = floatval($_POST['pajak'] ?? 0);
    $status         = 'baru'; 
    $id_user        = $_SESSION['user_id'];

    if (!empty($id_member) && !empty($id_paket) && $qty > 0) {
        try {
            $stmt_p = $pdo->prepare("SELECT harga FROM tb_paket WHERE id = :id");
            $stmt_p->execute(['id' => $id_paket]);
            $paket = $stmt_p->fetch();

            if ($paket) {
                $kode_invoice = 'TRX-' . date('YmdHis');

                $stmt = $pdo->prepare("INSERT INTO tb_transaksi (id_outlet, kode_invoice, id_member, tgl, batas_waktu, tgl_bayar, biaya_tambahan, diskon, pajak, status, dibayar, id_user) VALUES (:id_outlet, :kode_invoice, :id_member, :tgl, :batas_waktu, :tgl_bayar, :biaya_tambahan, :diskon, :pajak, :status, :dibayar, :id_user)");
                
                $stmt->execute([
                    'id_outlet'      => $id_outlet,
                    'kode_invoice'   => $kode_invoice,
                    'id_member'      => $id_member,
                    'tgl'            => $tgl,
                    'batas_waktu'    => $batas_waktu,
                    'tgl_bayar'      => $tgl_bayar,
                    'biaya_tambahan' => $biaya_tambahan,
                    'diskon'         => $diskon,
                    'pajak'          => $pajak,
                    'status'         => $status,
                    'dibayar'        => $dibayar,
                    'id_user'        => $id_user
                ]);

                $id_transaksi_baru = $pdo->lastInsertId();

                $stmt_detail = $pdo->prepare("INSERT INTO tb_detail_transaksi (id_transaksi, id_paket, qty, keterangan) VALUES (:id_transaksi, :id_paket, :qty, :keterangan)");
                $stmt_detail->execute([
                    'id_transaksi' => $id_transaksi_baru,
                    'id_paket'     => $id_paket,
                    'qty'          => $qty,
                    'keterangan'   => 'Cucian masuk'
                ]);

                $pesan_sukses = "Transaksi berhasil disimpan dengan Invoice: <b>$kode_invoice</b>";
            } else {
                $pesan_error = "Paket cucian tidak ditemukan!";
            }
        } catch (PDOException $e) {
            $pesan_error = "Gagal menyimpan transaksi: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Member, paket, dan jumlah/berat wajib diisi dengan benar!";
    }
}

// 2. Proses Edit / Update Transaksi yang Sudah Ada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_transaksi'])) {
    $id_transaksi   = $_POST['id_transaksi'];
    $id_member      = $_POST['id_member'];
    $id_paket       = $_POST['id_paket'];
    $qty            = floatval($_POST['qty']);
    $biaya_tambahan = floatval($_POST['biaya_tambahan'] ?? 0);
    $diskon         = abs(floatval($_POST['diskon'] ?? 0));
    $pajak          = floatval($_POST['pajak'] ?? 0);
    $dibayar        = $_POST['dibayar'];
    
    // Perbarui tanggal bayar jika status diubah jadi lunas
    $tgl_bayar_sql  = ($dibayar === 'dibayar') ? ", tgl_bayar = COALESCE(tgl_bayar, NOW())" : ", tgl_bayar = NULL";

    try {
        // Update tabel transaksi utama
        $stmt_up = $pdo->prepare("UPDATE tb_transaksi SET id_member = :id_member, biaya_tambahan = :biaya_tambahan, diskon = :diskon, pajak = :pajak, dibayar = :dibayar $tgl_bayar_sql WHERE id = :id");
        $stmt_up->execute([
            'id_member'      => $id_member,
            'biaya_tambahan' => $biaya_tambahan,
            'diskon'         => $diskon,
            'pajak'          => $pajak,
            'dibayar'        => $dibayar,
            'id'             => $id_transaksi
        ]);

        // Update tabel detail transaksi (paket & qty/berat)
        $stmt_dt_up = $pdo->prepare("UPDATE tb_detail_transaksi SET id_paket = :id_paket, qty = :qty WHERE id_transaksi = :id_transaksi");
        $stmt_dt_up->execute([
            'id_paket'     => $id_paket,
            'qty'          => $qty,
            'id_transaksi' => $id_transaksi
        ]);

        $pesan_sukses = "Data transaksi berhasil diperbarui!";
    } catch (PDOException $e) {
        $pesan_error = "Gagal memperbarui transaksi: " . $e->getMessage();
    }
}

// 3. Proses Update Status Transaksi
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
        header("Location: transaksi.php?pesan=status_sukses");
        exit();
    } catch (PDOException $e) {
        $pesan_error = "Gagal memperbarui status transaksi.";
    }
}

if (isset($_GET['pesan']) && $_GET['pesan'] == 'status_sukses') {
    $pesan_sukses = "Status transaksi berhasil diperbarui!";
}

// Ambil data member, paket, dan transaksi
try {
    $list_member = $pdo->query("SELECT * FROM tb_member ORDER BY nama ASC")->fetchAll();
    $list_paket  = $pdo->query("SELECT tb_paket.*, tb_outlet.nama AS nama_outlet FROM tb_paket JOIN tb_outlet ON tb_paket.id_outlet = tb_outlet.id ORDER BY tb_paket.nama_paket ASC")->fetchAll();
    
    $query = "SELECT t.*, 
              m.id AS id_member_asli,
              COALESCE(m.nama, 'Member Umum / Terhapus') AS nama_member, 
              COALESCE(u.nama, 'Administrator') AS nama_user, 
              dt.id_paket AS id_paket_asli,
              COALESCE(pk.nama_paket, 'Paket Manual') AS nama_paket, 
              COALESCE(pk.harga, 0) AS harga_paket,
              COALESCE(dt.qty, 1) AS qty
              FROM tb_transaksi t
              LEFT JOIN tb_member m ON t.id_member = m.id
              LEFT JOIN tb_user u ON t.id_user = u.id
              LEFT JOIN tb_detail_transaksi dt ON t.id = dt.id_transaksi
              LEFT JOIN tb_paket pk ON dt.id_paket = pk.id
              ORDER BY t.id DESC";
    $list_transaksi = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    $list_member = [];
    $list_paket  = [];
    $list_transaksi = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Transaksi - Laundry App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2 fs-5"></i> Dashboard</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item"><a href="outlet.php" class="nav-link"><i class="bi bi-shop fs-5"></i> Outlet</a></li>
                <?php endif; ?>
                <li class="nav-item"><a href="member.php" class="nav-link"><i class="bi bi-people fs-5"></i> Member</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item"><a href="paket.php" class="nav-link"><i class="bi bi-tag fs-5"></i> Paket Cucian</a></li>
                <li class="nav-item"><a href="user.php" class="nav-link"><i class="bi bi-person-badge fs-5"></i> Pengguna / Kasir</a></li>
                <?php endif; ?>
                <li class="nav-item"><a href="transaksi.php" class="nav-link active"><i class="bi bi-cart-check fs-5"></i> Transaksi</a></li>
                <li class="nav-item"><a href="laporan.php" class="nav-link"><i class="bi bi-file-earmark-text fs-5"></i> Laporan</a></li>
                <li class="nav-item mt-4"><a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right fs-5"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Wrapper -->
        <div id="content" class="p-0 w-100">
            <!-- Top Navbar -->
            <nav class="navbar navbar-top navbar-expand mb-4">
                <div class="container-fluid">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-burgundy-soft px-3 py-2 rounded-pill">
                            <i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($nama_outlet); ?>
                        </span>
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
                        <h3 class="fw-bold text-dark">Manajemen Transaksi Kasir</h3>
                        <p class="text-muted mb-0">Input cucian masuk, edit rincian berat/diskon, atur progres pengerjaan.</p>
                    </div>
                    <button type="button" class="btn btn-burgundy rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahTransaksi">
                        <i class="bi bi-plus-lg me-1"></i> Transaksi Baru
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

                <!-- Tabel Transaksi -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
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
                                    <?php $no = 1; foreach ($list_transaksi as $t): 
                                        $subtotal = ($t['harga_paket'] * $t['qty']);
                                        $diskon_bersih = abs($t['diskon']);
                                        $grand_total = $subtotal + $t['biaya_tambahan'] - $diskon_bersih + $t['pajak'];
                                    ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><code class="fw-bold text-dark"><?= htmlspecialchars($t['kode_invoice']); ?></code></td>
                                            <td class="fw-semibold"><?= htmlspecialchars($t['nama_member']); ?></td>
                                            <td>
                                                <span class="small d-block fw-bold"><?= htmlspecialchars($t['nama_paket']); ?></span>
                                                <span class="text-muted" style="font-size: 12px;">Qty/Berat: <?= $t['qty']; ?></span>
                                                
                                                <?php if ($t['biaya_tambahan'] > 0 || $diskon_bersih > 0 || $t['pajak'] > 0): ?>
                                                    <div class="text-secondary mt-1" style="font-size: 11px;">
                                                        <?php if ($t['biaya_tambahan'] > 0): ?>
                                                            <span class="text-success">+ Tambahan: Rp <?= number_format($t['biaya_tambahan'], 0, ',', '.'); ?></span><br>
                                                        <?php endif; ?>
                                                        <?php if ($diskon_bersih > 0): ?>
                                                            <span class="text-danger">- Diskon: Rp <?= number_format($diskon_bersih, 0, ',', '.'); ?></span><br>
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
                                                <div class="d-flex gap-1">
                                                    <!-- Tombol Edit Transaksi -->
                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalEditTransaksi<?= $t['id']; ?>" 
                                                        title="Edit Berat, Diskon, Biaya Tambahan">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>

                                                    <!-- Dropdown Ubah Status -->
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                                                            Status
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="transaksi.php?id=<?= $t['id']; ?>&ubah_status=baru">Baru</a></li>
                                                            <li><a class="dropdown-item" href="transaksi.php?id=<?= $t['id']; ?>&ubah_status=proses">Proses</a></li>
                                                            <li><a class="dropdown-item" href="transaksi.php?id=<?= $t['id']; ?>&ubah_status=selesai">Selesai</a></li>
                                                            <li><a class="dropdown-item" href="transaksi.php?id=<?= $t['id']; ?>&ubah_status=diambil">Diambil (Lunas)</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Modal Edit Transaksi untuk Setiap Baris -->
                                        <div class="modal fade" id="modalEditTransaksi<?= $t['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content border-0 rounded-4 shadow">
                                                    <form action="" method="POST">
                                                        <div class="modal-header border-bottom-0 pb-0">
                                                            <h5 class="modal-title fw-bold text-dark">Edit Transaksi: <?= htmlspecialchars($t['kode_invoice']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <input type="hidden" name="id_transaksi" value="<?= $t['id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Pelanggan (Member)</label>
                                                                <select name="id_member" class="form-select" required>
                                                                    <?php foreach ($list_member as $m): ?>
                                                                        <option value="<?= $m['id']; ?>" <?= ($m['id'] == $t['id_member_asli']) ? 'selected' : ''; ?>>
                                                                            <?= htmlspecialchars($m['nama']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Paket Cucian</label>
                                                                <select name="id_paket" class="form-select" required>
                                                                    <?php foreach ($list_paket as $p): ?>
                                                                        <option value="<?= $p['id']; ?>" <?= ($p['id'] == $t['id_paket_asli']) ? 'selected' : ''; ?>>
                                                                            <?= htmlspecialchars($p['nama_paket']); ?> (Rp <?= number_format($p['harga'], 0, ',', '.'); ?>)
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Jumlah / Berat (Kg atau Pcs)</label>
                                                                <input type="number" step="0.01" min="0.01" class="form-control" name="qty" value="<?= $t['qty']; ?>" required>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label fw-semibold small">Biaya Tambahan (Rp)</label>
                                                                    <input type="number" min="0" class="form-control" name="biaya_tambahan" value="<?= $t['biaya_tambahan']; ?>">
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label fw-semibold small">Diskon (Rp)</label>
                                                                    <input type="number" min="0" class="form-control" name="diskon" value="<?= $t['diskon']; ?>">
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Pajak (Rp)</label>
                                                                <input type="number" min="0" class="form-control" name="pajak" value="<?= $t['pajak']; ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Status Pembayaran</label>
                                                                <select name="dibayar" class="form-select" required>
                                                                    <option value="belum_dibayar" <?= ($t['dibayar'] === 'belum_dibayar') ? 'selected' : ''; ?>>Belum Lunas</option>
                                                                    <option value="dibayar" <?= ($t['dibayar'] === 'dibayar') ? 'selected' : ''; ?>>Lunas</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-top-0 pt-0">
                                                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="edit_transaksi" class="btn btn-burgundy rounded-pill px-4">Simpan Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bi bi-cart-x fs-2 d-block mb-2"></i>
                                            Belum ada data transaksi kasir.
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

    <!-- Modal Tambah Transaksi Baru -->
    <div class="modal fade" id="modalTambahTransaksi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="" method="POST">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark">Input Transaksi Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Pilih Pelanggan (Member)</label>
                            <select name="id_member" class="form-select" required>
                                <option value="">-- Pilih Member --</option>
                                <?php foreach ($list_member as $m): ?>
                                    <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Pilih Paket Cucian</label>
                            <select name="id_paket" class="form-select" required>
                                <option value="">-- Pilih Paket & Layanan --</option>
                                <?php foreach ($list_paket as $p): ?>
                                    <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['nama_paket']); ?> (Rp <?= number_format($p['harga'], 0, ',', '.'); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Jumlah / Berat (Kg atau Pcs)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="qty" required placeholder="Contoh: 3.5">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold small">Biaya Tambahan (Rp)</label>
                                <input type="number" min="0" class="form-control" name="biaya_tambahan" value="0" placeholder="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold small">Diskon (Rp)</label>
                                <input type="number" min="0" class="form-control" name="diskon" value="0" placeholder="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Pajak (Rp)</label>
                            <input type="number" min="0" class="form-control" name="pajak" value="0" placeholder="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Status Pembayaran Awal</label>
                            <select name="dibayar" class="form-select" required>
                                <option value="belum_dibayar">Belum Lunas (Bayar nanti saat diambil)</option>
                                <option value="dibayar">Lunas (Bayar sekarang)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_transaksi" class="btn btn-burgundy rounded-pill px-4">Proses Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>