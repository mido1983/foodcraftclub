<?php
/**
 * @var array $sellerProfile
 * @var array $notifications
 * @var int $unreadNotifications
 */

// Determine the current page
$currentUri = $_SERVER['REQUEST_URI'];
$isHome = $currentUri === '/seller' || $currentUri === '/seller/dashboard';
$isProducts = strpos($currentUri, '/seller/products') === 0;
$isOrders = strpos($currentUri, '/seller/orders') === 0;
$isDeliveryAreas = strpos($currentUri, '/seller/delivery-areas') === 0;
$isProfile = strpos($currentUri, '/seller/profile') === 0;
$isNotifications = strpos($currentUri, '/seller/notifications') === 0;
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $isHome ? 'active' : '' ?>" href="/seller/dashboard">
                    <i class="bi bi-house-door"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $isProducts ? 'active' : '' ?>" href="/seller/products">
                    <i class="bi bi-box"></i>
                    Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $isOrders ? 'active' : '' ?>" href="/seller/orders">
                    <i class="bi bi-cart"></i>
                    Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $isDeliveryAreas ? 'active' : '' ?>" href="/seller/delivery-areas">
                    <i class="bi bi-geo-alt"></i>
                    Delivery Areas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $isProfile ? 'active' : '' ?>" href="/seller/profile">
                    <i class="bi bi-person"></i>
                    Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $isNotifications ? 'active' : '' ?>" href="/seller/notifications">
                    <i class="bi bi-bell"></i>
                    Notifications
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $unreadNotifications ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
        
        <hr>
        
        <div class="px-3 py-2">
            <div class="d-flex align-items-center">
                <?php if (!empty($sellerProfile['avatar_url'])): ?>
                    <img src="<?= $sellerProfile['avatar_url'] ?>" alt="Profile" class="rounded-circle me-2" width="32" height="32">
                <?php else: ?>
                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                        <i class="bi bi-person"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <small class="text-muted">Logged in as:</small>
                    <div class="fw-bold"><?= htmlspecialchars($sellerProfile['name']) ?></div>
                </div>
            </div>
            <div class="mt-2">
                <a href="/logout" class="btn btn-sm btn-outline-danger w-100">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>
