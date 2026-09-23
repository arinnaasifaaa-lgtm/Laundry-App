<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top border-bottom py-3 px-4" style="border-color: #e2e8f0 !important;">
    <div class="container-fluid">
        <!-- Logo & Brand (Nuansa Oren & Biru Soft) -->
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-dark" href="home.php" style="font-family: 'Inter', sans-serif;">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 38px; height: 38px; background-color: #ff7700;">
                <i class="bi bi-basket3-fill fs-5"></i>
            </div>
            <span style="color: #1e293b; font-weight: 700;">LaundryApp</span>
        </a>

        <!-- Menu Navigasi di Tengah -->
        <div class="collapse navbar-collapse justify-content-center">
            <ul class="navbar-nav gap-2">
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-pill fw-semibold <?= basename($_SERVER['PHP_SELF']) == 'home.php' ? 'text-white' : 'text-secondary'; ?>" href="home.php" style="<?= basename($_SERVER['PHP_SELF']) == 'home.php' ? 'background-color: #ff7700;' : ''; ?>">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-pill fw-semibold <?= basename($_SERVER['PHP_SELF']) == 'member.php' ? 'text-white' : 'text-secondary'; ?>" href="member.php" style="<?= basename($_SERVER['PHP_SELF']) == 'member.php' ? 'background-color: #ff7700;' : ''; ?>">Registrasi Member</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-pill fw-semibold <?= basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'text-white' : 'text-secondary'; ?>" href="transaksi.php" style="<?= basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'background-color: #ff7700;' : ''; ?>">Entri Transaksi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-pill fw-semibold <?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'text-white' : 'text-secondary'; ?>" href="laporan.php" style="<?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'background-color: #ff7700;' : ''; ?>">Generate Laporan</a>
                </li>
            </ul>
        </div>

        <!-- Profil Pengguna & Tombol Logout (Biru Soft & Oren) -->
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2 text-end">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 38px; height: 38px; font-size: 14px; background-color: #3b82f6;">
                    <?= strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1)); ?>
                </div>
                <div class="text-start">
                    <span class="d-block fw-bold text-dark small" style="line-height: 1.2;"><?= htmlspecialchars($_SESSION['nama'] ?? 'Tamu'); ?></span>
                    <span class="badge bg-info text-dark text-uppercase" style="font-size: 9px; background-color: #e0f2fe !important; color: #0369a1 !important;"><?= htmlspecialchars($_SESSION['role'] ?? 'kasir'); ?></span>
                </div>
            </div>

            <!-- Tombol Logout dengan Peringatan Konfirmasi SweetAlert2 -->
            <a href="#" onclick="konfirmasiLogout(event)" class="btn btn-sm rounded-pill px-4 py-2 fw-semibold text-white shadow-sm" style="background-color: #ff7700; border: none;" onmouseover="this.style.backgroundColor='#e66d00';" onmouseout="this.style.backgroundColor='#ff7700';">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<!-- Pustaka SweetAlert2 & Script Peringatan Logout -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function konfirmasiLogout(event) {
    event.preventDefault();
    
    Swal.fire({
        title: 'Keluar dari Sistem?',
        text: "Sesi Anda akan diakhiri dan Anda harus login kembali.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff7700',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Logout!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mengarah ke file logout.php di folder frontend yang sama
            window.location.href = 'logout.php';
        }
    });
}
</script>