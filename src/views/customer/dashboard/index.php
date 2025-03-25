<?php
/** @var $this \App\Core\View */
/** @var $user \App\Models\User */
/** @var $recentOrders array */
/** @var $wishlistProducts array */
/** @var $preorderProducts array */

$this->title = 'u041bu0438u0447u043du044bu0439 u043au0430u0431u0438u043du0435u0442 - Food Craft Club';
$this->route = 'customer/dashboard/index';
?>

<div class="container py-4">
    <div class="row">
        <!-- u0411u043eu043au043eu0432u0430u044f u043fu0430u043du0435u043bu044c -->
        <div class="col-lg-3 mb-4">
            <?php include_once __DIR__ . '/sidebar.php'; ?>
        </div>
        
        <!-- u041eu0441u043du043eu0432u043du043eu0439 u043au043eu043du0442u0435u043du0442 -->
        <div class="col-lg-9">
            <!-- u041fu0440u0438u0432u0435u0442u0441u0442u0432u0438u0435 -->
            <div class="welcome-section bg-white shadow-sm rounded p-4 mb-4">
                <h2 class="h4 mb-3">u0414u043eu0431u0440u043e u043fu043eu0436u0430u043bu043eu0432u0430u0442u044c, <?= $this->escape($user->name) ?>!</h2>
                <p class="text-muted mb-0">u0417u0434u0435u0441u044c u0432u044b u043cu043eu0436u0435u0442u0435 u0443u043fu0440u0430u0432u043bu044fu0442u044c u0441u0432u043eu0438u043cu0438 u0437u0430u043au0430u0437u0430u043cu0438, u0438u0437u0431u0440u0430u043du043du044bu043cu0438 u0442u043eu0432u0430u0440u0430u043cu0438 u0438 u043fu0440u0435u0434u0437u0430u043au0430u0437u0430u043cu0438.</p>
            </div>
            
            <!-- u0411u044bu0441u0442u0440u044bu0435 u0434u0435u0439u0441u0442u0432u0438u044f -->
            <div class="quick-actions row mb-4">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="icon-wrapper mb-3">
                                <i class="bi bi-box-seam fs-1 text-primary"></i>
                            </div>
                            <h5 class="card-title">u041cu043eu0438 u0437u0430u043au0430u0437u044b</h5>
                            <p class="card-text small text-muted">u041fu0440u043eu0441u043cu043eu0442u0440 u0438 u043eu0442u0441u043bu0435u0436u0438u0432u0430u043du0438u0435 u0432u0430u0448u0438u0445 u0437u0430u043au0430u0437u043eu0432</p>
                            <a href="/customer/orders" class="btn btn-outline-primary btn-sm mt-2">u041fu0435u0440u0435u0439u0442u0438</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="icon-wrapper mb-3">
                                <i class="bi bi-heart fs-1 text-danger"></i>
                            </div>
                            <h5 class="card-title">u0418u0437u0431u0440u0430u043du043du043eu0435</h5>
                            <p class="card-text small text-muted">u0422u043eu0432u0430u0440u044b, u043au043eu0442u043eu0440u044bu0435 u0432u044b u0434u043eu0431u0430u0432u0438u043bu0438 u0432 u0438u0437u0431u0440u0430u043du043du043eu0435</p>
                            <a href="/customer/wishlist" class="btn btn-outline-danger btn-sm mt-2">u041fu0435u0440u0435u0439u0442u0438</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="icon-wrapper mb-3">
                                <i class="bi bi-clock-history fs-1 text-success"></i>
                            </div>
                            <h5 class="card-title">u041fu0440u0435u0434u0437u0430u043au0430u0437u044b</h5>
                            <p class="card-text small text-muted">u0422u043eu0432u0430u0440u044b, u043au043eu0442u043eu0440u044bu0435 u0432u044b u0437u0430u0440u0435u0437u0435u0440u0432u0438u0440u043eu0432u0430u043bu0438</p>
                            <a href="/customer/preorders" class="btn btn-outline-success btn-sm mt-2">u041fu0435u0440u0435u0439u0442u0438</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- u041fu043eu0441u043bu0435u0434u043du0438u0435 u0437u0430u043au0430u0437u044b -->
            <div class="recent-orders mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">u041fu043eu0441u043bu0435u0434u043du0438u0435 u0437u0430u043au0430u0437u044b</h3>
                    <a href="/customer/orders" class="btn btn-sm btn-link text-decoration-none">u0412u0441u0435 u0437u0430u043au0430u0437u044b</a>
                </div>
                
                <?php if (empty($recentOrders)): ?>
                    <div class="alert alert-light text-center py-4">
                        <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
                        <p class="mb-0">u0423 u0432u0430u0441 u0435u0449u0435 u043du0435u0442 u0437u0430u043au0430u0437u043eu0432</p>
                        <a href="/catalog" class="btn btn-primary mt-3">u041fu0435u0440u0435u0439u0442u0438 u0432 u043au0430u0442u0430u043bu043eu0433</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive bg-white shadow-sm rounded">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>u2116 u0437u0430u043au0430u0437u0430</th>
                                    <th>u0414u0430u0442u0430</th>
                                    <th>u0421u0443u043cu043cu0430</th>
                                    <th>u0421u0442u0430u0442u0443u0441</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?= $order->id ?></td>
                                        <td><?= $order->getFormattedDate() ?></td>
                                        <td><?= $order->getFormattedTotal() ?></td>
                                        <td>
                                            <span class="badge bg-<?= $this->getStatusBadgeClass($order->status) ?>">
                                                <?= $order->getStatusText() ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="/customer/orders/<?= $order->id ?>" class="btn btn-sm btn-outline-primary">
                                                u041fu043eu0434u0440u043eu0431u043du0435u0435
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- u0418u0437u0431u0440u0430u043du043du044bu0435 u0442u043eu0432u0430u0440u044b u0438 u043fu0440u0435u0434u0437u0430u043au0430u0437u044b -->
            <div class="row">
                <!-- u0418u0437u0431u0440u0430u043du043du044bu0435 u0442u043eu0432u0430u0440u044b -->
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 mb-0">u0418u0437u0431u0440u0430u043du043du044bu0435 u0442u043eu0432u0430u0440u044b</h3>
                        <a href="/customer/wishlist" class="btn btn-sm btn-link text-decoration-none">u0412u0441u0435 u0438u0437u0431u0440u0430u043du043du044bu0435</a>
                    </div>
                    
                    <?php if (empty($wishlistProducts)): ?>
                        <div class="alert alert-light text-center py-3">
                            <i class="bi bi-heart fs-3 d-block mb-2 text-muted"></i>
                            <p class="mb-0 small">u0423 u0432u0430u0441 u0435u0449u0435 u043du0435u0442 u0438u0437u0431u0440u0430u043du043du044bu0445 u0442u043eu0432u0430u0440u043eu0432</p>
                        </div>
                    <?php else: ?>
                        <div class="wishlist-items bg-white shadow-sm rounded p-3">
                            <?php foreach ($wishlistProducts as $index => $product): ?>
                                <?php if ($index < 3): ?>
                                    <div class="wishlist-item d-flex align-items-center py-2 <?= $index < count($wishlistProducts) - 1 ? 'border-bottom' : '' ?>">
                                        <img src="<?= $product->image ?? '/assets/img/no-image.jpg' ?>" alt="<?= $this->escape($product->name) ?>" class="rounded" width="50" height="50" style="object-fit: cover;">
                                        <div class="ms-3 flex-grow-1">
                                            <h6 class="mb-0 product-name"><?= $this->escape($product->name) ?></h6>
                                            <p class="mb-0 text-primary fw-bold"><?= number_format($product->price, 2, '.', ' ') ?> u0440u0443u0431.</p>
                                        </div>
                                        <a href="/product/<?= $product->id ?>" class="btn btn-sm btn-outline-primary ms-2">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <?php if (count($wishlistProducts) > 3): ?>
                                <div class="text-center mt-3">
                                    <a href="/customer/wishlist" class="btn btn-sm btn-outline-primary">u041fu043eu043au0430u0437u0430u0442u044c u0435u0449u0435 <?= count($wishlistProducts) - 3 ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- u041fu0440u0435u0434u0437u0430u043au0430u0437u044b -->
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 mb-0">u041fu0440u0435u0434u0437u0430u043au0430u0437u044b</h3>
                        <a href="/customer/preorders" class="btn btn-sm btn-link text-decoration-none">u0412u0441u0435 u043fu0440u0435u0434u0437u0430u043au0430u0437u044b</a>
                    </div>
                    
                    <?php if (empty($preorderProducts)): ?>
                        <div class="alert alert-light text-center py-3">
                            <i class="bi bi-clock-history fs-3 d-block mb-2 text-muted"></i>
                            <p class="mb-0 small">u0423 u0432u0430u0441 u0435u0449u0435 u043du0435u0442 u043fu0440u0435u0434u0437u0430u043au0430u0437u043eu0432</p>
                        </div>
                    <?php else: ?>
                        <div class="preorder-items bg-white shadow-sm rounded p-3">
                            <?php foreach ($preorderProducts as $index => $product): ?>
                                <?php if ($index < 3): ?>
                                    <div class="preorder-item d-flex align-items-center py-2 <?= $index < count($preorderProducts) - 1 ? 'border-bottom' : '' ?>">
                                        <img src="<?= $product->image ?? '/assets/img/no-image.jpg' ?>" alt="<?= $this->escape($product->name) ?>" class="rounded" width="50" height="50" style="object-fit: cover;">
                                        <div class="ms-3 flex-grow-1">
                                            <h6 class="mb-0 product-name"><?= $this->escape($product->name) ?></h6>
                                            <p class="mb-0 small text-muted">u041eu0436u0438u0434u0430u0435u0442u0441u044f: <?= $product->expected_date ?? 'u0421u043au043eu0440u043e u0432 u043du0430u043bu0438u0447u0438u0438' ?></p>
                                        </div>
                                        <a href="/product/<?= $product->id ?>" class="btn btn-sm btn-outline-success ms-2">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <?php if (count($preorderProducts) > 3): ?>
                                <div class="text-center mt-3">
                                    <a href="/customer/preorders" class="btn btn-sm btn-outline-success">u041fu043eu043au0430u0437u0430u0442u044c u0435u0449u0435 <?= count($preorderProducts) - 3 ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// u0414u043eu0431u0430u0432u043bu0435u043du0438u0435 u0445u0435u043bu043fu0435u0440u0430 u0434u043bu044f u043eu043fu0440u0435u0434u0435u043bu0435u043du0438u044f u043au043bu0430u0441u0441u0430 u0431u0435u0439u0434u0436u0430 u0441u0442u0430u0442u0443u0441u0430
if (!method_exists($this, 'getStatusBadgeClass')) {
    $this->getStatusBadgeClass = function ($status) {
        $classes = [
            'pending' => 'warning',
            'processing' => 'info',
            'shipped' => 'primary',
            'delivered' => 'success',
            'canceled' => 'danger'
        ];
        
        return $classes[$status] ?? 'secondary';
    };
}
?>
