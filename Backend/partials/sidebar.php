<?php
// Backend/partials/sidebar.php
$role = $_SESSION['role'] ?? 'kasir';
$current_page = basename($_SERVER['PHP_SELF']);

$menus = [
    ['url' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'roles' => ['admin', 'kasir']],
    ['url' => 'outlet.php', 'label' => 'Outlet', 'icon' => 'bi-shop', 'roles' => ['admin']],
    ['url' => 'member.php', 'label' => 'Member', 'icon' => 'bi-people', 'roles' => ['admin', 'kasir']],
    ['url' => 'paket.php', 'label' => 'Paket Cucian', 'icon' => 'bi-tag', 'roles' => ['admin']],
    ['url' => 'user.php', 'label' => 'Pengguna / Kasir', 'icon' => 'bi-person-badge', 'roles' => ['admin']],
    ['url' => 'transaksi.php', 'label' => 'Transaksi', 'icon' => 'bi-cart-check', 'roles' => ['admin', 'kasir']],
    ['url' => 'laporan.php', 'label' => 'Laporan', 'icon' => 'bi-file-earmark-text', 'roles' => ['admin', 'kasir', 'owner']],
];
?>
<nav id="sidebar" class="d-none d-md-block">
    <!-- Bagian Logo dengan tinggi presisi 70px agar garisnya menyambung rata dengan topbar -->
    <div class="px-4 d-flex align-items-center" style="height: 70px; color: #800020; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.1rem; border-bottom: 1px solid #ebd3d7; box-sizing: border-box;">
        <i class="bi bi-basket3-fill me-2 fs-4"></i> LaundryApp
    </div>
    <ul class="nav flex-column mt-3">
        <?php foreach ($menus as $m): ?>
            <?php if (in_array($role, $m['roles'])): ?>
                <li class="nav-item">
                    <a href="<?= $m['url']; ?>" class="nav-link <?= ($current_page == $m['url']) ? 'active' : ''; ?>">
                        <i class="bi <?= $m['icon']; ?> fs-5 me-3"></i> <?= $m['label']; ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li class="nav-item mt-4">
            <a href="logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right fs-5 me-3"></i> Logout
            </a>
        </li>
    </ul>
</nav>