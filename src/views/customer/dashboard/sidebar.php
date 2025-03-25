<?php
/** @var $this \App\Core\View */
/** @var $user \App\Models\User */

// Определение текущего маршрута для выделения активного пункта меню
$currentRoute = $this->route ?? '';
?>

<div class="customer-sidebar bg-white shadow-sm rounded p-4 h-100">
    <div class="text-center mb-4">
        <div class="customer-avatar mb-3">
            <?php if (!empty($user->avatar) && file_exists(dirname($_SERVER['DOCUMENT_ROOT']) . '/public' . $user->avatar)): ?>
                <img src="<?= $user->avatar ?>" alt="Аватар пользователя" class="rounded-circle" width="80" height="80">
            <?php else: ?>
                <img src="/assets/img/default-avatar.png" alt="Аватар пользователя" class="rounded-circle" width="80" height="80">
            <?php endif; ?>
        </div>
        <h5 class="mb-1"><?= $this->escape($user->full_name) ?></h5>
        <p class="text-muted small mb-0"><?= $this->escape($user->email) ?></p>
    </div>
    
    <hr>
    
    <nav class="customer-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?= $currentRoute === 'customer/dashboard/index' ? 'active' : '' ?>" href="/customer/dashboard">
                    <i class="bi bi-house-door me-2"></i> Главная
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?= $currentRoute === 'customer/dashboard/orders' ? 'active' : '' ?>" href="/customer/dashboard/orders">
                    <i class="bi bi-box-seam me-2"></i> Мои заказы
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?= $currentRoute === 'customer/dashboard/wishlist' ? 'active' : '' ?>" href="/customer/dashboard/wishlist">
                    <i class="bi bi-heart me-2"></i> Избранное
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?= $currentRoute === 'customer/dashboard/preorders' ? 'active' : '' ?>" href="/customer/dashboard/preorders">
                    <i class="bi bi-clock-history me-2"></i> Предзаказы
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?= $currentRoute === 'customer/dashboard/profile' ? 'active' : '' ?>" href="/customer/profile">
                    <i class="bi bi-person me-2"></i> Мой профиль
                </a>
            </li>
        </ul>
    </nav>
    
    <hr>
    
    <div class="customer-actions">
        <a href="/catalog" class="btn btn-outline-primary w-100 mb-2">
            <i class="bi bi-shop me-2"></i> В каталог
        </a>
        <a href="/logout" class="btn btn-outline-danger w-100">
            <i class="bi bi-box-arrow-right me-2"></i> Выйти
        </a>
    </div>
</div>
