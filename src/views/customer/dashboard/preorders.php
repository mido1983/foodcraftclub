<?php
/** @var $this \App\Core\View */
/** @var $user \App\Models\User */
/** @var $products array */

$this->title = 'u041fu0440u0435u0434u0437u0430u043au0430u0437u044b - Food Craft Club';
$this->route = 'customer/dashboard/preorders';
?>

<div class="container py-4">
    <div class="row">
        <!-- u0411u043eu043au043eu0432u0430u044f u043fu0430u043du0435u043bu044c -->
        <div class="col-lg-3 mb-4">
            <?php include_once __DIR__ . '/sidebar.php'; ?>
        </div>
        
        <!-- u041eu0441u043du043eu0432u043du043eu0439 u043au043eu043du0442u0435u043du0442 -->
        <div class="col-lg-9">
            <div class="bg-white shadow-sm rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0">u041cu043eu0438 u043fu0440u0435u0434u0437u0430u043au0430u0437u044b</h2>
                    <a href="/catalog" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> u0412 u043au0430u0442u0430u043bu043eu0433
                    </a>
                </div>
                
                <?php if (empty($products)): ?>
                    <div class="alert alert-light text-center py-5">
                        <i class="bi bi-clock-history fs-1 d-block mb-3 text-muted"></i>
                        <p class="mb-3">u0423 u0432u0430u0441 u0435u0449u0435 u043du0435u0442 u043fu0440u0435u0434u0437u0430u043au0430u0437u043eu0432</p>
                        <p class="text-muted mb-3">u041fu0440u0435u0434u0437u0430u043au0430u0437u044b u043fu043eu0437u0432u043eu043bu044fu044eu0442 u0432u0430u043c u0437u0430u0440u0435u0437u0435u0440u0432u0438u0440u043eu0432u0430u0442u044c u0442u043eu0432u0430u0440u044b, u043au043eu0442u043eu0440u044bu0435 u0432u0440u0435u043cu0435u043du043du043e u043eu0442u0441u0443u0442u0441u0442u0432u0443u044eu0442 u0432 u043du0430u043bu0438u0447u0438u0438.</p>
                        <a href="/catalog" class="btn btn-primary">u041fu0435u0440u0435u0439u0442u0438 u0432 u043au0430u0442u0430u043bu043eu0433</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        u041au043eu0433u0434u0430 u043fu0440u0435u0434u0437u0430u043au0430u0437u0430u043du043du044bu0439 u0442u043eu0432u0430u0440 u043fu043eu044fu0432u0438u0442u0441u044f u0432 u043du0430u043bu0438u0447u0438u0438, u043cu044b u043eu0442u043fu0440u0430u0432u0438u043c u0432u0430u043c u0443u0432u0435u0434u043eu043cu043bu0435u043du0438u0435 u043fu043e u044du043bu0435u043au0442u0440u043eu043du043du043eu0439 u043fu043eu0447u0442u0435.
                    </div>
                    
                    <!-- u0422u0430u0431u043bu0438u0446u0430 u043fu0440u0435u0434u0437u0430u043au0430u0437u043eu0432 -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="preordersTable">
                            <thead class="table-light">
                                <tr>
                                    <th>u0422u043eu0432u0430u0440</th>
                                    <th>u041au043eu043bu0438u0447u0435u0441u0442u0432u043e</th>
                                    <th>u0421u0442u0430u0442u0443u0441</th>
                                    <th>u0414u0430u0442u0430 u0437u0430u043au0430u0437u0430</th>
                                    <th>u041eu0436u0438u0434u0430u0435u043cu0430u044f u0434u0430u0442u0430</th>
                                    <th>u0414u0435u0439u0441u0442u0432u0438u044f</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $item): ?>
                                    <?php 
                                        $product = $item['product'];
                                        $preorder = $item['preorder'];
                                    ?>
                                    <tr data-preorder-id="<?= $preorder->id ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= $product->image ?? '/assets/img/no-image.jpg' ?>" alt="<?= $this->escape($product->name) ?>" class="rounded" width="50" height="50" style="object-fit: cover;">
                                                <div class="ms-3">
                                                    <h6 class="mb-0"><?= $this->escape($product->name) ?></h6>
                                                    <small class="text-muted">u0410u0440u0442u0438u043au0443u043b: <?= $product->sku ?? 'N/A' ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $preorder->quantity ?> u0448u0442.</td>
                                        <td>
                                            <span class="badge bg-<?= $this->getPreorderStatusBadgeClass($preorder->status) ?>">
                                                <?= $preorder->getStatusText() ?>
                                            </span>
                                        </td>
                                        <td><?= $preorder->getFormattedDate() ?></td>
                                        <td><?= $product->expected_date ?? 'u0423u0442u043eu0447u043du044fu0435u0442u0441u044f' ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/product/<?= $product->id ?>" class="btn btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($preorder->status === 'waiting' || $preorder->status === 'notified'): ?>
                                                    <button type="button" class="btn btn-outline-danger cancel-preorder" data-preorder-id="<?= $preorder->id ?>">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- u0418u043du0444u043eu0440u043cu0430u0446u0438u044f u043e u043fu0440u0435u0434u0437u0430u043au0430u0437u0430u0445 -->
            <div class="bg-white shadow-sm rounded p-4">
                <h3 class="h5 mb-3">u041au0430u043a u0440u0430u0431u043eu0442u0430u044eu0442 u043fu0440u0435u0434u0437u0430u043au0430u0437u044b?</h3>
                
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card border-0 h-100">
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <i class="bi bi-calendar-plus fs-3 text-primary"></i>
                                    </div>
                                </div>
                                <h5 class="card-title text-center">1. u0421u043eu0437u0434u0430u043du0438u0435 u043fu0440u0435u0434u0437u0430u043au0430u0437u0430</h5>
                                <p class="card-text text-muted small">u0412u044b u0440u0435u0437u0435u0440u0432u0438u0440u0443u0435u0442u0435 u0442u043eu0432u0430u0440, u043au043eu0442u043eu0440u044bu0439 u0432u0440u0435u043cu0435u043du043du043e u043eu0442u0441u0443u0442u0441u0442u0432u0443u0435u0442 u0432 u043du0430u043bu0438u0447u0438u0438, u043du0430u0436u0430u0432 u043au043du043eu043fu043au0443 "u041fu0440u0435u0434u0437u0430u043au0430u0437" u043du0430 u0441u0442u0440u0430u043du0438u0446u0435 u0442u043eu0432u0430u0440u0430.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-0 h-100">
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <i class="bi bi-bell fs-3 text-primary"></i>
                                    </div>
                                </div>
                                <h5 class="card-title text-center">2. u0423u0432u0435u0434u043eu043cu043bu0435u043du0438u0435</h5>
                                <p class="card-text text-muted small">u041au043eu0433u0434u0430 u0442u043eu0432u0430u0440 u043fu043eu044fu0432u0438u0442u0441u044f u0432 u043du0430u043bu0438u0447u0438u0438, u043cu044b u043eu0442u043fu0440u0430u0432u0438u043c u0432u0430u043c u0443u0432u0435u0434u043eu043cu043bu0435u043du0438u0435 u043fu043e u044du043bu0435u043au0442u0440u043eu043du043du043eu0439 u043fu043eu0447u0442u0435 u0438 u0437u0430u0440u0435u0437u0435u0440u0432u0438u0440u0443u0435u043c u0442u043eu0432u0430u0440 u0434u043bu044f u0432u0430u0441 u043du0430 48 u0447u0430u0441u043eu0432.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-0 h-100">
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <i class="bi bi-cart-check fs-3 text-primary"></i>
                                    </div>
                                </div>
                                <h5 class="card-title text-center">3. u041fu043eu043au0443u043fu043au0430</h5>
                                <p class="card-text text-muted small">u041fu0435u0440u0435u0439u0434u0438u0442u0435 u043fu043e u0441u0441u044bu043bu043au0435 u0438u0437 u0443u0432u0435u0434u043eu043cu043bu0435u043du0438u044f u0438 u043eu0444u043eu0440u043cu0438u0442u0435 u0437u0430u043au0430u0437. u0415u0441u043bu0438 u0432u044b u043du0435 u0441u0434u0435u043bu0430u0435u0442u0435 u044du0442u043eu0433u043e u0432 u0442u0435u0447u0435u043du0438u0435 48 u0447u0430u0441u043eu0432, u0442u043eu0432u0430u0440 u0441u0442u0430u043du0435u0442 u0434u043eu0441u0442u0443u043fu0435u043d u0434u043bu044f u0432u0441u0435u0445.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // u041eu0442u043cu0435u043du0430 u043fu0440u0435u0434u0437u0430u043au0430u0437u0430
        const cancelButtons = document.querySelectorAll('.cancel-preorder');
        cancelButtons.forEach(button => {
            button.addEventListener('click', function() {
                const preorderId = this.dataset.preorderId;
                const row = this.closest('tr');
                
                if (confirm('u0412u044b u0443u0432u0435u0440u0435u043du044b, u0447u0442u043e u0445u043eu0442u0438u0442u0435 u043eu0442u043cu0435u043du0438u0442u044c u044du0442u043eu0442 u043fu0440u0435u0434u0437u0430u043au0430u0437?')) {
                    // AJAX u0437u0430u043fu0440u043eu0441 u043du0430 u043eu0442u043cu0435u043du0443 u043fu0440u0435u0434u0437u0430u043au0430u0437u0430
                    fetch('/customer/preorders/cancel', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?= $this->csrf_token ?>'
                        },
                        body: JSON.stringify({
                            preorder_id: preorderId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // u0410u043du0438u043cu0430u0446u0438u044f u0443u0434u0430u043bu0435u043du0438u044f u0441u0442u0440u043eu043au0438
                            row.style.opacity = '0';
                            row.style.transform = 'scale(0.8)';
                            row.style.transition = 'all 0.3s ease';
                            
                            setTimeout(() => {
                                row.remove();
                                
                                // u041fu0440u043eu0432u0435u0440u043au0430, u043eu0441u0442u0430u043bu0438u0441u044c u043bu0438 u0435u0449u0435 u043fu0440u0435u0434u0437u0430u043au0430u0437u044b
                                const remainingRows = document.querySelectorAll('#preordersTable tbody tr');
                                if (remainingRows.length === 0) {
                                    // u0415u0441u043bu0438 u043fu0440u0435u0434u0437u0430u043au0430u0437u043eu0432 u043du0435 u043eu0441u0442u0430u043bu043eu0441u044c, u043fu043eu043au0430u0437u044bu0432u0430u0435u043c u0441u043eu043eu0431u0449u0435u043du0438u0435
                                    const container = document.querySelector('.col-lg-9 > div:first-child');
                                    container.innerHTML = `
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h2 class="h4 mb-0">u041cu043eu0438 u043fu0440u0435u0434u0437u0430u043au0430u0437u044b</h2>
                                            <a href="/catalog" class="btn btn-primary btn-sm">
                                                <i class="bi bi-plus-lg me-1"></i> u0412 u043au0430u0442u0430u043bu043eu0433
                                            </a>
                                        </div>
                                        <div class="alert alert-light text-center py-5">
                                            <i class="bi bi-clock-history fs-1 d-block mb-3 text-muted"></i>
                                            <p class="mb-3">u0423 u0432u0430u0441 u0435u0449u0435 u043du0435u0442 u043fu0440u0435u0434u0437u0430u043au0430u0437u043eu0432</p>
                                            <p class="text-muted mb-3">u041fu0440u0435u0434u0437u0430u043au0430u0437u044b u043fu043eu0437u0432u043eu043bu044fu044eu0442 u0432u0430u043c u0437u0430u0440u0435u0437u0435u0440u0432u0438u0440u043eu0432u0430u0442u044c u0442u043eu0432u0430u0440u044b, u043au043eu0442u043eu0440u044bu0435 u0432u0440u0435u043cu0435u043du043du043e u043eu0442u0441u0443u0442u0441u0442u0432u0443u044eu0442 u0432 u043du0430u043bu0438u0447u0438u0438.</p>
                                            <a href="/catalog" class="btn btn-primary">u041fu0435u0440u0435u0439u0442u0438 u0432 u043au0430u0442u0430u043bu043eu0433</a>
                                        </div>
                                    `;
                                }
                            }, 300);
                        } else {
                            alert('u041eu0448u0438u0431u043au0430 u043fu0440u0438 u043eu0442u043cu0435u043du0435 u043fu0440u0435u0434u0437u0430u043au0430u0437u0430: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('u041fu0440u043eu0438u0437u043eu0448u043bu0430 u043eu0448u0438u0431u043au0430 u043fu0440u0438 u043eu0442u043cu0435u043du0435 u043fu0440u0435u0434u0437u0430u043au0430u0437u0430');
                    });
                }
            });
        });
    });
</script>

<?php
// u0414u043eu0431u0430u0432u043bu0435u043du0438u0435 u0445u0435u043bu043fu0435u0440u0430 u0434u043bu044f u043eu043fu0440u0435u0434u0435u043bu0435u043du0438u044f u043au043bu0430u0441u0441u0430 u0431u0435u0439u0434u0436u0430 u0441u0442u0430u0442u0443u0441u0430 u043fu0440u0435u0434u0437u0430u043au0430u0437u0430
if (!method_exists($this, 'getPreorderStatusBadgeClass')) {
    $this->getPreorderStatusBadgeClass = function ($status) {
        $classes = [
            'waiting' => 'warning',
            'notified' => 'info',
            'converted' => 'success',
            'canceled' => 'danger'
        ];
        
        return $classes[$status] ?? 'secondary';
    };
}
?>
