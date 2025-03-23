<?php
/** 
 * @var string|null $reason
 */
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="bi bi-x-circle-fill me-2"></i>Order Cancelled</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="display-1 text-danger mb-3">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <h3>Your order has been cancelled</h3>
                        <?php if (!empty($reason)): ?>
                            <p class="lead"><?= htmlspecialchars($reason) ?></p>
                        <?php else: ?>
                            <p class="lead">Your order has been cancelled.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="alert alert-light border mb-4">
                        <h5><i class="bi bi-info-circle me-2"></i>What happened?</h5>
                        <p class="mb-0">Your order was not completed. This could be due to:</p>
                        <ul class="mb-0 mt-2">
                            <li>Payment was cancelled or declined</li>
                            <li>You chose to cancel the order</li>
                            <li>There was an issue with the delivery address</li>
                            <li>A technical error occurred during checkout</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-info mb-4">
                        <h5><i class="bi bi-cart me-2"></i>Your Cart</h5>
                        <p class="mb-0">Your cart items are still saved. You can return to your cart and try again.</p>
                    </div>
                    
                    <div class="text-center">
                        <a href="/cart" class="btn btn-primary me-2">
                            <i class="bi bi-cart me-2"></i>Return to Cart
                        </a>
                        <a href="/catalog" class="btn btn-outline-primary">
                            <i class="bi bi-basket me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
