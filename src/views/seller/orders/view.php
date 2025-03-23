<?php
/** 
 * @var array $order The order details
 * @var array $orderItems The items in the order
 * @var \App\Core\View $this The view instance
 */
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="/seller">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/seller/orders">
                            <i class="bi bi-bag"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/seller/products">
                            <i class="bi bi-box"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/seller/profile">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/seller/delivery-areas">
                            <i class="bi bi-geo-alt"></i> Delivery Areas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/seller/payment-methods">
                            <i class="bi bi-credit-card"></i> Payment Methods
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Order #<?= $order['id'] ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/seller/orders" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Order Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Order Date:</strong> <?= date('F j, Y H:i', strtotime($order['order_date'])) ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-<?= $this->getStatusBadgeClass($order['status']) ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </p>
                                    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method_name'] ?? 'Not specified') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total Amount:</strong> $<?= number_format($order['total_amount'], 2) ?></p>
                                    <p><strong>Delivery Fee:</strong> $<?= number_format($order['delivery_fee'] ?? 0, 2) ?></p>
                                    <p><strong>Order Total:</strong> $<?= number_format(($order['total_amount'] + ($order['delivery_fee'] ?? 0)), 2) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Order Items</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($orderItems)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No items found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($orderItems as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                    <td>$<?= number_format($item['price'] ?? 0, 2) ?></td>
                                                    <td><?= $item['quantity'] ?></td>
                                                    <td>$<?= number_format(($item['price'] ?? 0) * $item['quantity'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Customer Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Name:</strong> <?= htmlspecialchars($order['buyer_name'] ?? 'Not specified') ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email'] ?? 'Not specified') ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone'] ?? 'Not specified') ?></p>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Delivery Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Address:</strong> <?= htmlspecialchars($order['address'] ?? 'Not specified') ?></p>
                            <p><strong>City:</strong> <?= htmlspecialchars($order['city_name'] ?? 'Not specified') ?></p>
                            <p><strong>District:</strong> <?= htmlspecialchars($order['district_name'] ?? 'Not specified') ?></p>
                            <p><strong>Notes:</strong> <?= htmlspecialchars($order['notes'] ?? 'None') ?></p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Update Order Status</h5>
                        </div>
                        <div class="card-body">
                            <form action="/seller/orders/<?= $order['id'] ?>/update-status" method="post">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="new" <?= $order['status'] === 'new' ? 'selected' : '' ?>>New</option>
                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Update Status</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
