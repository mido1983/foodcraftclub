<?php
/**
 * @var App\Models\User $user
 * @var int $unreadNotifications
 */
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= str_ends_with($_SERVER['REQUEST_URI'], '/seller') ? 'active' : '' ?>" href="/seller">
                    <i class="bi bi-speedometer2 me-1"></i>
                    Панель управления
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/seller/products') ? 'active' : '' ?>" href="/seller/products">
                    <i class="bi bi-box-seam me-1"></i>
                    Мои продукты
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/seller/orders') ? 'active' : '' ?>" href="/seller/orders">
                    <i class="bi bi-cart-check me-1"></i>
                    Заказы
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/seller/profile') ? 'active' : '' ?>" href="/seller/profile">
                    <i class="bi bi-person-circle me-1"></i>
                    Мой профиль
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Дополнительно</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/seller/statistics') ? 'active' : '' ?>" href="/seller/statistics">
                    <i class="bi bi-graph-up me-1"></i>
                    Статистика
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/seller/reviews') ? 'active' : '' ?>" href="/seller/reviews">
                    <i class="bi bi-star me-1"></i>
                    Отзывы
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/seller/messages') ? 'active' : '' ?>" href="/seller/messages">
                    <i class="bi bi-chat-dots me-1"></i>
                    Сообщения
                    <?php if (isset($unreadNotifications) && $unreadNotifications > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $unreadNotifications ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
    </div>
</nav>
