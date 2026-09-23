<?php
// frontend/transaksi.php
require_once __DIR__ . '/../Backend/components/koneksi.php';
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

// Ambil parameter filter
$periode = $_GET['periode'] ?? '';
$tahun = $_GET['tahun'] ?? date('Y');
$status_filter = $_GET['status'] ?? '';

// Fungsi Bantuan untuk Pencatatan Activity Log ke Database
function catat_log($pdo, $username, $aktivitas) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (username, activity, created_at) VALUES (:username, :activity, NOW())");
        $stmt->execute([
            'username' => $username,
            'activity' => $aktivitas
        ]);
    } catch (PDOException $e) {
        // Abaikan jika tabel log mengalami kendala
    }
}

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
                catat_log($pdo, $nama_user, "Menambahkan transaksi baru dengan invoice $kode_invoice");

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

// 2. Proses Edit / Update Transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_transaksi'])) {
    $id_transaksi   = $_POST['id_transaksi'];
    $id_member      = $_POST['id_member'];
    $id_paket       = $_POST['id_paket'];
    $qty            = floatval($_POST['qty']);
    $biaya_tambahan = floatval($_POST['biaya_tambahan'] ?? 0);
    $diskon         = abs(floatval($_POST['diskon'] ?? 0));
    $pajak          = floatval($_POST['pajak'] ?? 0);
    $dibayar        = $_POST['dibayar'];
    
    $tgl_bayar_sql  = ($dibayar === 'dibayar') ? ", tgl_bayar = COALESCE(tgl_bayar, NOW())" : ", tgl_bayar = NULL";

    try {
        $stmt_up = $pdo->prepare("UPDATE tb_transaksi SET id_member = :id_member, biaya_tambahan = :biaya_tambahan, diskon = :diskon, pajak = :pajak, dibayar = :dibayar $tgl_bayar_sql WHERE id = :id");
        $stmt_up->execute([
            'id_member'      => $id_member,
            'biaya_tambahan' => $biaya_tambahan,
            'diskon'         => $diskon,
            'pajak'          => $pajak,
            'dibayar'        => $dibayar,
            'id'             => $id_transaksi
        ]);

        $stmt_dt_up = $pdo->prepare("UPDATE tb_detail_transaksi SET id_paket = :id_paket, qty = :qty WHERE id_transaksi = :id_transaksi");
        $stmt_dt_up->execute([
            'id_paket'     => $id_paket,
            'qty'          => $qty,
            'id_transaksi' => $id_transaksi
        ]);

        $pesan_sukses = "Data transaksi berhasil diperbarui!";
        catat_log($pdo, $nama_user, "Memperbarui data transaksi ID: $id_transaksi");

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
        
        catat_log($pdo, $nama_user, "Mengubah status transaksi ID $id menjadi $status_baru");
        header("Location: transaksi.php?pesan=status_sukses");
        exit();
    } catch (PDOException $e) {
        $pesan_error = "Gagal memperbarui status transaksi.";
    }
}

if (isset($_GET['pesan']) && $_GET['pesan'] == 'status_sukses') {
    $pesan_sukses = "Status transaksi berhasil diperbarui!";
}

// Ambil data member, paket, dan transaksi dengan filter
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
    $list_transaksi = $stmt->fetchAll();

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
    <title>Manajemen Transaksi - Lumiere Laundry</title>
    
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
    </style>
</head>

<body>
     <?php include 'navbar.php'; ?>

    <!-- Navbar Atas Ala Template Frontend -->
    <!-- <header class="navigation position-sticky top-0 w-100 bg-body-tertiary shadow-sm border-bottom z-3">
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
                                <a href="transaksi.php" class="px-3 text-body-emphasis bg-body-secondary-hover nav-link rounded-3 text-base leading-6 fw-semibold active" aria-current="page">
                                    Entri Transaksi
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="laporan.php" class="px-3 text-body-emphasis bg-body-secondary-hover nav-link rounded-3 text-base leading-6 fw-semibold">
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

    <!-- Konten Utama Halaman Transaksi -->
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark">Manajemen Transaksi Kasir</h2>
                <p class="text-muted mb-0">Input cucian masuk, edit rincian berat/diskon, atur progres pengerjaan.</p>
            </div>
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#modalTambahTransaksi">
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

        <!-- Form Filter -->
        <div class="card shadow-sm border-0 rounded-4 p-4 mb-4 bg-white">
            <form method="GET" action="transaksi.php" class="row g-3 align-items-end">
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
                    <label class="form-label small fw-bold text-secondary">Status Cucian</label>
                    <select name="status" class="form-select rounded-pill shadow-sm">
                        <option value="">Semua Status</option>
                        <option value="baru" <?= $status_filter=='baru'?'selected':''; ?>>Baru</option>
                        <option value="proses" <?= $status_filter=='proses'?'selected':''; ?>>Proses</option>
                        <option value="selesai" <?= $status_filter=='selesai'?'selected':''; ?>>Selesai</option>
                        <option value="diambil" <?= $status_filter=='diambil'?'selected':''; ?>>Diambil</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary rounded-pill w-100 shadow-sm text-white"><i class="bi bi-filter me-1"></i> Filter</button>
                </div>
            </form>
        </div>

        <!-- Tabel Transaksi -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
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
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditTransaksi<?= $t['id']; ?>" 
                                                title="Edit Berat, Diskon, Biaya Tambahan">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

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

                                <!-- Modal Edit Transaksi -->
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
                                                    <button type="submit" name="edit_transaksi" class="btn btn-primary text-white rounded-pill px-4">Simpan Perubahan</button>
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
                                    Tidak ada data transaksi untuk periode tersebut.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                        <button type="submit" name="tambah_transaksi" class="btn btn-primary text-white rounded-pill px-4">Proses Transaksi</button>
                    </div>
                </form>
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