<?php
// Hubungkan ke file koneksi dengan huruf B besar pada folder Backend
require_once __DIR__ . '/../Backend/components/koneksi.php';

// Cek apakah kasir sudah login
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['kasir', 'admin'])) {
    header("Location: ../backend/login.php");
    exit;
}
?>
<!doctype html>
<html lang="en" dir="ltr" data-bs-theme="auto">

<head>
    <script src="./assets/js/color-modes.js"></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Kasir - Lumiere Laundry</title>

    <link rel="apple-touch-icon" sizes="180x180" href="./assets/logo/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="./assets/logo/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./assets/logo/favicon-16x16.png">
    <link rel="icon" href="./assets/logo/favicon.ico">
    <link rel="manifest" href="./assets/logo/site.webmanifest">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="./assets/libraries/glide/css/glide.core.min.css">
    <link rel="stylesheet" href="./assets/libraries/aos/aos.css">
    <link rel="stylesheet" href="./assets/css/main.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <!-- FontAwesome untuk Icon Kasir -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
</head>

<body>

    <!-- loader-wrapper -->
    <div class="loader-wrapper">
        <div class="spinner-border text-primary p-5" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- header top / Navbar Kasir -->
    <header class="navigation position-absolute w-100 bg-body-tertiary shadow border-bottom border-light border-opacity-10 rounded-bottom-3 rounded-bottom-sm-4">
        <nav class="navbar navbar-expand-xl" aria-label="Offcanvas navbar large">
            <div class="container py-1">
                <a href="home.php" class="navbar-brand">
                    <img src="./assets/logo/logo.png" height="40" alt="logo">
                </a>

                <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar2" aria-controls="offcanvasNavbar2" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="offcanvas offcanvas-end border-0 rounded-start-0 rounded-start-sm-4" tabindex="-1" id="offcanvasNavbar2" aria-labelledby="offcanvasNavbar2Label">
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
                                <a href="home.php" class="px-3 text-body-emphasis bg-body-secondary-hover nav-link rounded-3 text-base leading-6 fw-semibold active" aria-current="page">
                                    Beranda
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="member.php" class="px-3 text-body-emphasis bg-body-secondary-hover nav-link rounded-3 text-base leading-6 fw-semibold">
                                    Registrasi Member
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="transaksi.php" class="px-3 text-body-emphasis bg-body-secondary-hover nav-link rounded-3 text-base leading-6 fw-semibold">
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
    </header>

    <!-- header body / Sambutan Kasir -->
    <div class="overflow-hidden position-relative">
        <img src="./assets/img/bg/kasirgambar.jpg" class="position-absolute z-n1 top-0 h-100 w-100 object-fit-cover" alt="Laundry Background">
        <div class="overlay position-absolute z-n1 top-0 h-100 w-100 bg-dark"
            style="opacity: 0.85; mix-blend-mode: multiply; filter: contrast(1.15) brightness(0.85);">
        </div>

        <div class="container">
            <div class="min-vh-100 row align-items-center">
                <div class="col-12 col-xl-10 mx-auto text-center text-xl-start">
                    <div class="pt-9 pt-md-10 pt-xl-11 pb-7 pb-md-8 pb-xl-9">
                        <div class="mt-4 pt-2">
                            <h1 class="m-0 text-white tracking-tight text-5xl fw-bold" data-aos="fade" data-aos-duration="2000">
                                Selamat Datang, <?= htmlspecialchars($_SESSION['nama'] ?? 'Kasir'); ?>!
                            </h1>
                            <p class="m-0 mt-4 text-white text-lg leading-8" data-aos="fade" data-aos-duration="2500">
                                Panel Sistem Kasir Lumiere Laundry. Kelola data pelanggan, input cucian masuk, dan cetak laporan dengan cepat dan mudah.
                            </p>
                            <div class="mt-4 pt-3 d-flex align-items-center justify-content-center justify-content-xl-start column-gap-3" data-aos="fade" data-aos-duration="3000">
                                <a href="transaksi.php" class="btn btn-lg btn-primary text-white text-sm fw-semibold">
                                    <i class="fa fa-receipt me-2"></i> Mulai Transaksi
                                </a>
                                <a href="member.php" class="btn btn-lg btn-outline-light text-sm fw-semibold">
                                    <i class="fa fa-user-plus me-2"></i> Daftar Member
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Pintas Fitur Kasir -->
    <div class="overflow-hidden py-7 py-sm-8 py-xl-9 bg-body-tertiary">
        <div class="container">
            <div class="mx-auto max-w-2xl text-center mb-5">
                <h2 class="m-0 text-primary-emphasis text-base leading-7 fw-semibold">Menu Utama Kasir</h2>
                <p class="m-0 mt-2 text-body-emphasis text-4xl tracking-tight fw-bold">Pilih Layanan Sistem</p>
            </div>
            <div class="row row-cols-1 row-cols-xl-3 gy-5 gx-xl-4 justify-content-center">
                
                <!-- Card 1: Member -->
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center rounded-4 bg-body">
                        <div class="card-body">
                            <div class="mb-3 text-primary"><i class="fa fa-user-plus fa-3x"></i></div>
                            <h3 class="card-title text-body-emphasis text-lg fw-semibold">Registrasi Member</h3>
                            <p class="text-body-secondary text-sm mt-3">Daftarkan pelanggan baru ke sistem sebelum memproses transaksi cucian.</p>
                            <a href="member.php" class="btn btn-primary text-white mt-4 btn-sm fw-semibold">Buka Menu</a>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Transaksi -->
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center rounded-4 bg-body">
                        <div class="card-body">
                            <div class="mb-3 text-primary"><i class="fa fa-receipt fa-3x"></i></div>
                            <h3 class="card-title text-body-emphasis text-lg fw-semibold">Entri Transaksi</h3>
                            <p class="text-body-secondary text-sm mt-3">Input data cucian masuk, pilih paket laundry, dan perbarui status pengerjaan.</p>
                            <a href="transaksi.php" class="btn btn-primary text-white mt-4 btn-sm fw-semibold">Buka Menu</a>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Laporan -->
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center rounded-4 bg-body">
                        <div class="card-body">
                            <div class="mb-3 text-primary"><i class="fa fa-file-invoice-dollar fa-3x"></i></div>
                            <h3 class="card-title text-body-emphasis text-lg fw-semibold">Generate Laporan</h3>
                            <p class="text-body-secondary text-sm mt-3">Cetak rekapitulasi data transaksi keuangan harian maupun bulanan outlet.</p>
                            <a href="laporan.php" class="btn btn-primary text-white mt-4 btn-sm fw-semibold">Buka Menu</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer sederhana -->
    <footer class="py-4 bg-body border-top text-center text-body-secondary text-sm">
        <div class="container">
            <p class="mb-0">© <?= date('Y'); ?> Freshen Laundry Kasir System.</p>
        </div>
    </footer>

    <!-- Back to top button -->
    <button type="button" class="btn btn-primary btn-back-to-top rounded-circle justify-content-center align-items-center p-2 text-white">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-caret-up-fill" viewBox="0 0 16 16">
            <path d="m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z" />
        </svg>
    </button>

    <!-- Bootstrap JavaScript & Scripts -->
    <script src="./assets/libraries/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/libraries/glide/glide.min.js"></script>
    <script src="./assets/libraries/aos/aos.js"></script>
    <script src="./assets/js/scripts.js"></script>

</body>

</html>