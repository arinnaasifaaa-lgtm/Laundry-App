<?php
// Konfigurasi Koneksi Database Langsung
$host = "localhost";
$user = "root";
$pass = "";
$db   = "laundry_app"; // Pastikan nama database kamu sudah sesuai

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$result_resi = null;
$search_query = "";

if (isset($_GET['cari_resi'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['invoice']);
    if (!empty($search_query)) {
        $query = "SELECT t.*, m.nama as nama_member, o.nama as nama_outlet, p.nama_paket, p.harga as harga_paket, dt.qty 
                  FROM tb_transaksi t 
                  JOIN tb_member m ON t.id_member = m.id 
                  JOIN tb_outlet o ON t.id_outlet = o.id 
                  JOIN tb_detail_transaksi dt ON t.id = dt.id_transaksi
                  JOIN tb_paket p ON dt.id_paket = p.id
                  WHERE t.kode_invoice = '$search_query'";
        $result_resi = mysqli_query($conn, $query);
    }
}

$paket_query = mysqli_query($conn, "SELECT * FROM tb_paket");
$outlet_query = mysqli_query($conn, "SELECT * FROM tb_outlet");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaundryApp - Aesthetic & Professional Laundry</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-burgundy: #7A0016;
            --burgundy-hover: #5c0010;
            --bg-soft: #fbfbfc;
            --text-dark: #1a1a1a;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            background-color: var(--bg-soft);
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        .navbar {
            background-color: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--primary-burgundy) !important;
            letter-spacing: -0.5px;
        }

        .nav-link {
            font-weight: 500;
            color: #4a5568 !important;
            transition: all 0.2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary-burgundy) !important;
        }

        .hero-section {
            background: linear-gradient(135deg, #7A0016 0%, #3d000b 100%);
            color: white;
            padding: 110px 0 160px 0;
            border-radius: 0 0 40px 40px;
        }

        .search-container {
            margin-top: -85px;
            position: relative;
            z-index: 10;
        }

        .search-card, .card-aesthetic {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid #edf2f7;
            transition: all 0.3s ease;
        }

        .search-card {
            padding: 40px;
            box-shadow: 0 20px 50px rgba(122, 0, 22, 0.08);
            border: 1px solid #fff;
        }

        .card-aesthetic:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }

        .form-control-aesthetic {
            border-radius: 14px;
            padding: 14px 20px;
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            font-weight: 500;
            transition: all 0.2s;
        }

        .form-control-aesthetic:focus {
            background-color: #fff;
            border-color: var(--primary-burgundy);
            box-shadow: 0 0 0 4px rgba(122, 0, 22, 0.1);
        }

        .btn-aesthetic {
            background-color: var(--primary-burgundy);
            color: white;
            border-radius: 14px;
            font-weight: 600;
            padding: 14px 24px;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-aesthetic:hover {
            background-color: var(--burgundy-hover);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(122, 0, 22, 0.25);
        }

        .icon-wrapper {
            width: 52px;
            height: 52px;
            background-color: #fdf2f4;
            color: var(--primary-burgundy);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 1.3rem;
            margin-bottom: 20px;
        }

        .footer {
            background-color: #0f172a;
            color: #94a3b8;
            padding: 50px 0;
            margin-top: 100px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container py-2">
            <a class="navbar-brand d-flex align-items-center gap-2 fs-4" href="index.php">
                <div class="text-white rounded-3 p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px; background-color: var(--primary-burgundy);">
                    <i class="fa-solid fa-shirt fs-6"></i>
                </div>
                LaundryApp
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-4">
                    <li class="nav-item"><a class="nav-link active" href="#">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#cek-resi">Cek Resi</a></li>
                    <li class="nav-item"><a class="nav-link" href="#layanan">Paket Cucian</a></li>
                    <li class="nav-item"><a class="nav-link" href="#outlet">Outlet</a></li>
                    <li class="nav-item">
                        <a href="../Backend/login.php" class="btn btn-outline-danger btn-sm px-4 rounded-pill fw-semibold" style="border-color: var(--primary-burgundy); color: var(--primary-burgundy);">Login Pegawai</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section text-center">
        <div class="container">
            <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-bold mb-3 shadow-sm" style="color: var(--primary-burgundy) !important;">✨ Premium & Trusted Laundry Service</span>
            <h1 class="display-4 fw-extrabold mb-3" style="letter-spacing: -1px;">Solusi Kebersihan Pakaian Anda</h1>
            <p class="lead opacity-75 mx-auto" style="max-width: 550px;">Cepat, bersih, wangi, dan profesional. Pantau status cucian Anda secara real-time langsung dari genggaman.</p>
        </div>
    </header>

    <!-- Main Content & Form Cek Resi -->
    <main class="container" id="cek-resi">
        <div class="row justify-content-center search-container">
            <div class="col-lg-9">
                <div class="search-card">
                    
                    <!-- NOTIFIKASI SUKSES PESANAN BERHASIL -->
                    <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                        <div class="alert border-0 rounded-4 shadow-sm p-4 mb-4 text-center" role="alert" style="background-color: #d1e7dd; color: #0f5132;">
                            <div class="fs-1 mb-2"><i class="fa-solid fa-circle-check text-success"></i></div>
                            <h4 class="fw-bold mb-1">Yeay, Pesanan Berhasil Dikirim!</h4>
                            <p class="mb-2 text-muted small">Terima kasih telah mempercayakan cucian Anda pada LaundryApp.</p>
                            <div class="bg-white p-2 px-3 rounded-3 d-inline-block border border-success-subtle shadow-sm">
                                <span class="text-dark small d-block">Nomor Resi / Invoice Anda:</span>
                                <strong class="fs-5" style="color: var(--primary-burgundy);"><?php echo htmlspecialchars($_GET['invoice']); ?></strong>
                            </div>
                            <p class="mb-0 mt-3 small text-muted">Simpan atau salin nomor resi di atas untuk melacak progres cucian Anda di bawah.</p>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mb-4">
                        <h3 class="fw-bold fs-4 mb-1">Lacak Status Cucian</h3>
                        <p class="text-muted small">Masukkan kode invoice untuk melihat progres cucian Anda</p>
                    </div>

                    <form action="index.php#cek-resi" method="GET" class="row g-3">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-2 border-end-0 rounded-start-4 text-muted ps-3"><i class="fa-solid fa-receipt"></i></span>
                                <input type="text" name="invoice" class="form-control form-control-aesthetic border-start-0 ps-0" placeholder="Contoh: TRX-2026..." value="<?php echo htmlspecialchars($search_query); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="cari_resi" class="btn btn-aesthetic w-100 h-100"><i class="fa-solid fa-magnifying-glass me-2"></i> Cek Status</button>
                        </div>
                    </form>

                    <!-- Hasil Pencarian Resi -->
                    <?php if (isset($_GET['cari_resi'])): ?>
                        <div class="mt-4 pt-4 border-top">
                            <?php if ($result_resi && mysqli_num_rows($result_resi) > 0): ?>
                                <?php $data = mysqli_fetch_assoc($result_resi); ?>
                                <div class="p-4 rounded-4 bg-light border border-success-subtle">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="fw-bold text-success m-0"><i class="fa-solid fa-circle-check me-2"></i>Transaksi Ditemukan</h5>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-semibold">Valid</span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">No. Invoice</span>
                                            <strong class="text-dark fs-6"><?php echo $data['kode_invoice']; ?></strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Nama Pelanggan</span>
                                            <strong class="text-dark fs-6"><?php echo $data['nama_member']; ?></strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Outlet Proses</span>
                                            <strong class="text-dark fs-6"><?php echo $data['nama_outlet']; ?></strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Paket Cucian</span>
                                            <strong class="text-dark fs-6"><?php echo $data['nama_paket']; ?> (<?= $data['qty']; ?> kg/pcs)</strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block mb-1">Status Pengerjaan</span>
                                            <span class="badge px-3 py-2 rounded-pill 
                                                <?php 
                                                    if($data['status'] == 'baru') echo 'bg-secondary';
                                                    elseif($data['status'] == 'proses') echo 'bg-warning text-dark';
                                                    elseif($data['status'] == 'selesai') echo 'bg-primary';
                                                    else echo 'bg-success';
                                                ?>">
                                                <?php echo strtoupper($data['status']); ?>
                                            </span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block mb-1">Status Pembayaran</span>
                                            <span class="badge px-3 py-2 rounded-pill <?php echo ($data['dibayar'] == 'lunas') ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo strtoupper($data['dibayar']); ?>
                                            </span>
                                        </div>

                                        <!-- Kotak Rincian Biaya -->
                                        <div class="col-12 mt-3">
                                            <div class="bg-white p-3 rounded-3 border border-secondary-subtle">
                                                <div class="fw-bold small text-secondary mb-2">Rincian Biaya:</div>
                                                <?php 
                                                    $subtotal       = ($data['harga_paket'] ?? 0) * ($data['qty'] ?? 1);
                                                    $biaya_tambahan = floatval($data['biaya_tambahan'] ?? 0);
                                                    $diskon         = abs(floatval($data['diskon'] ?? 0));
                                                    $pajak          = floatval($data['pajak'] ?? 0);
                                                    $grand_total    = $subtotal + $biaya_tambahan - $diskon + $pajak;
                                                ?>
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>Subtotal Paket (<?php echo $data['qty']; ?> x Rp <?php echo number_format($data['harga_paket'], 0, ',', '.'); ?>):</span>
                                                    <span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                                                </div>
                                                <?php if ($biaya_tambahan > 0): ?>
                                                <div class="d-flex justify-content-between small text-success mb-1">
                                                    <span>Biaya Tambahan:</span>
                                                    <span>+ Rp <?php echo number_format($biaya_tambahan, 0, ',', '.'); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($diskon > 0): ?>
                                                <div class="d-flex justify-content-between small text-danger mb-1">
                                                    <span>Diskon:</span>
                                                    <span>- Rp <?php echo number_format($diskon, 0, ',', '.'); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($pajak > 0): ?>
                                                <div class="d-flex justify-content-between small text-info mb-1">
                                                    <span>Pajak:</span>
                                                    <span>+ Rp <?php echo number_format($pajak, 0, ',', '.'); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <hr class="my-2">
                                                <div class="d-flex justify-content-between fw-bold text-dark">
                                                    <span>Total Harga Akhir:</span>
                                                    <span style="color: var(--primary-burgundy);">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></span>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger border-0 rounded-4 text-center p-3 mb-0" role="alert">
                                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Data transaksi dengan kode <strong><?php echo htmlspecialchars($search_query); ?></strong> tidak ditemukan.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section Daftar Paket & Harga -->
        <section class="mt-5 pt-5" id="layanan">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-2">Paket Cucian Pilihan</h2>
                <p class="text-muted">Kualitas terbaik dengan harga bersahabat untuk kenyamanan pakaian Anda</p>
            </div>
            <div class="row g-4">
                <?php while($paket = mysqli_fetch_assoc($paket_query)): ?>
                <div class="col-md-4">
                    <div class="card card-aesthetic h-100 p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="icon-wrapper">
                                <i class="fa-solid fa-basket-shopping"></i>
                            </div>
                            <h4 class="fw-bold fs-5 mb-2"><?php echo $paket['nama_paket']; ?></h4>
                            <p class="text-muted small mb-3">Jenis: <span class="badge bg-light text-dark border px-2 py-1"><?php echo ucfirst($paket['jenis']); ?></span></p>
                            <h3 class="fw-bold mb-4" style="color: var(--primary-burgundy);">Rp <?php echo number_format($paket['harga'], 0, ',', '.'); ?> <span class="fs-6 text-muted fw-normal">/ satuan</span></h3>
                            <ul class="list-unstyled text-muted small mb-4">
                                <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Pencucian higienis terpisah</li>
                                <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Pewangi pakaian eksklusif</li>
                                <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Rapi, bersih & siap pakai</li>
                            </ul>
                        </div>
                        <!-- Tombol Pilih Paket memicu Modal -->
                        <button type="button" class="btn btn-outline-danger rounded-pill w-100 fw-semibold py-2" style="border-color: var(--primary-burgundy); color: var(--primary-burgundy);" data-bs-toggle="modal" data-bs-target="#modalPesan<?php echo $paket['id']; ?>">
                            Pilih Paket
                        </button>
                    </div>
                </div>

                <!-- Modal Form Pemesanan -->
                <div class="modal fade" id="modalPesan<?php echo $paket['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 rounded-4 shadow">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold">Pesan Paket: <?php echo $paket['nama_paket']; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <form action="proses_pesan.php" method="POST">
                                    <input type="hidden" name="id_paket" value="<?php echo $paket['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Nama Lengkap Anda</label>
                                        <input type="text" name="nama" class="form-control form-control-aesthetic" placeholder="Masukkan nama Anda" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Nomor Telepon / WhatsApp</label>
                                        <input type="text" name="telepon" class="form-control form-control-aesthetic" placeholder="Contoh: 08123456789" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Pilih Outlet Terdekat</label>
                                        <select name="id_outlet" class="form-control form-control-aesthetic" required>
                                            <option value="">-- Pilih Outlet --</option>
                                            <?php 
                                            $outlet_modal = mysqli_query($conn, "SELECT * FROM tb_outlet");
                                            while($o = mysqli_fetch_assoc($outlet_modal)): 
                                            ?>
                                                <option value="<?php echo $o['id']; ?>"><?php echo $o['nama']; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Alamat / Catatan Penjemputan</label>
                                        <textarea name="alamat" class="form-control form-control-aesthetic" rows="2" placeholder="Masukkan alamat lengkap..." required></textarea>
                                    </div>
                                    <button type="submit" name="pesan_sekarang" class="btn btn-aesthetic w-100 py-3 mt-2">Kirim Pesanan Sekarang</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </section>

        <!-- Section Outlet Kami -->
        <section class="mt-5 pt-5" id="outlet">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-2">Outlet Kami</h2>
                <p class="text-muted">Kunjungi cabang operasional terdekat dari lokasi Anda</p>
            </div>
            <div class="row g-4 justify-content-center">
                <?php while($outlet = mysqli_fetch_assoc($outlet_query)): ?>
                <div class="col-md-6">
                    <div class="card card-aesthetic p-4 h-100">
                        <div class="d-flex align-items-start gap-3">
                            <div class="icon-wrapper m-0 flex-shrink-0">
                                <i class="fa-solid fa-store"></i>
                            </div>
                            <div>
                                <h4 class="fw-bold fs-5 mb-1"><?php echo $outlet['nama']; ?></h4>
                                <p class="text-muted mb-0 small"><i class="fa-solid fa-location-dot me-1" style="color: var(--primary-burgundy);"></i> <?php echo $outlet['alamat']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer text-center">
        <div class="container">
            <p class="mb-1 fw-semibold">&copy; 2026 LaundryApp. Seluruh Hak Cipta Dilindungi.</p>
            <p class="small text-muted mb-0">Sistem Informasi Manajemen & Pelacakan Laundry Profesional.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>