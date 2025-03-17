<?php
/** 
 * @var \App\Models\User $user
 * @var array $sellerProfile
 * @var array $stats
 * @var array $recentOrders
 * @var array $notifications
 * @var int $unreadNotifications
 */
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="/seller/dashboard">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/seller/products">
                            <i class="bi bi-box"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/seller/orders">
                            <i class="bi bi-cart"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/seller/delivery-areas">
                            <i class="bi bi-geo-alt"></i> Delivery Areas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/seller/profile">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="/seller/products/new" class="btn btn-sm btn-outline-primary">Add New Product</a>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="badge bg-danger"><?= $unreadNotifications ?></span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (empty($notifications)): ?>
                                <li><span class="dropdown-item">No new notifications</span></li>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <li>
                                        <a class="dropdown-item <?= $notification['is_read'] ? '' : 'fw-bold' ?>" href="#">
                                            <?= htmlspecialchars($notification['content']) ?>
                                            <small class="text-muted d-block">
                                                <?= date('M j, Y H:i', strtotime($notification['created_at'])) ?>
                                            </small>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="/seller/notifications">View All</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Orders</h5>
                            <h2 class="card-text"><?= number_format($stats['total_orders'] ?? 0) ?></h2>
                            <p class="card-text text-muted">Lifetime orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Revenue</h5>
                            <h2 class="card-text">$<?= number_format($stats['total_revenue'] ?? 0, 2) ?></h2>
                            <p class="card-text text-muted">Total earnings</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Active Products</h5>
                            <h2 class="card-text"><?= number_format($stats['active_products'] ?? 0) ?></h2>
                            <p class="card-text text-muted">Products in catalog</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Orders</h5>
                    <a href="/seller/orders" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">No orders yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td><?= htmlspecialchars($order['buyer_name']) ?></td>
                                        <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $this->getStatusBadgeClass($order['status']) ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y H:i', strtotime($order['order_date'])) ?></td>
                                        <td>
                                            <a href="/seller/orders/<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Seller Profile -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Seller Profile</h5>
                    <a href="/seller/profile/edit" class="btn btn-sm btn-outline-primary">Edit Profile</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?= htmlspecialchars($user->full_name) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($user->email) ?></p>
                            <p><strong>Seller Type:</strong> <?= ucfirst($sellerProfile['seller_type'] ?? 'ordinary') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Minimum Order:</strong> $<?= number_format($sellerProfile['min_order_amount'] ?? 0, 2) ?></p>
                            <p><strong>Member Since:</strong> <?= date('F j, Y', strtotime($user->created_at)) ?></p>
                            <p><strong>Status:</strong> <?= ucfirst($user->status) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Helper function for order status badge colors
function getStatusBadgeClass($status) {
    return match ($status) {
        'new' => 'info',
        'processing' => 'primary',
        'paid' => 'success',
        'shipped' => 'warning',
        'delivered' => 'success',
        'cancelled' => 'danger',
        default => 'secondary'
    };
}
