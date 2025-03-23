<?php
/** 
 * @var array $cartItems
 * @var float $totalAmount
 */
?>

<div class="container mt-4">
    <h1 class="mb-4">Shopping Cart</h1>
    
    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info">
            <p>Your cart is empty. <a href="/catalog" class="alert-link">Continue shopping</a></p>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Cart items -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Cart Items (<?= count($cartItems) ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="py-3">Product</th>
                                        <th scope="col" class="py-3 text-center">Price</th>
                                        <th scope="col" class="py-3 text-center">Quantity</th>
                                        <th scope="col" class="py-3 text-end">Total</th>
                                        <th scope="col" class="py-3 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                        <tr class="cart-item" data-product-id="<?= $item['product_id'] ?>">
                                            <td class="py-3">
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($item['image_url'])): ?>
                                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-light me-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                            <i class="bi bi-image text-secondary" style="font-size: 2rem;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($item['product_name']) ?></h6>
                                                        <small class="text-muted">Seller: <?= htmlspecialchars($item['seller_name']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 text-center">
                                                <span class="item-price"><?= number_format($item['price'], 2) ?> ₪</span>
                                            </td>
                                            <td class="py-3 text-center">
                                                <div class="input-group input-group-sm quantity-control" style="width: 120px;">
                                                    <button class="btn btn-outline-secondary decrease-quantity" type="button">
                                                        <i class="bi bi-dash"></i>
                                                    </button>
                                                    <input type="number" class="form-control text-center item-quantity" value="<?= $item['quantity'] ?>" min="1" max="99">
                                                    <button class="btn btn-outline-secondary increase-quantity" type="button">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="py-3 text-end">
                                                <span class="item-total"><?= number_format($item['price'] * $item['quantity'], 2) ?> ₪</span>
                                            </td>
                                            <td class="py-3 text-center">
                                                <button class="btn btn-sm btn-outline-danger remove-item">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mb-4">
                    <a href="/catalog" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                    </a>
                    <button id="clear-cart" class="btn btn-outline-danger">
                        <i class="bi bi-trash me-2"></i>Clear Cart
                    </button>
                </div>
            </div>
            
            <!-- Order summary -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal</span>
                            <span id="cart-subtotal"><?= number_format($totalAmount, 2) ?> ₪</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Delivery</span>
                            <span>Calculated at checkout</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total</strong>
                            <strong id="cart-total"><?= number_format($totalAmount, 2) ?> ₪</strong>
                        </div>
                        <a href="/checkout" class="btn btn-primary w-100">
                            <i class="bi bi-credit-card me-2"></i>Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update cart item quantity
        function updateCartItemQuantity(productId, quantity) {
            fetch('/cart/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update item total
                    const itemRow = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                    const itemTotalElement = itemRow.querySelector('.item-total');
                    itemTotalElement.textContent = `${parseFloat(data.item_total).toFixed(2)} ₪`;
                    
                    // Update cart subtotal and total
                    document.getElementById('cart-subtotal').textContent = `${parseFloat(data.cart_total).toFixed(2)} ₪`;
                    document.getElementById('cart-total').textContent = `${parseFloat(data.cart_total).toFixed(2)} ₪`;
                    
                    // Update cart count in header
                    updateCartCountInHeader(data.cart_count);
                } else {
                    showNotification(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error updating cart:', error);
                showNotification('An error occurred while updating the cart', 'danger');
            });
        }
        
        // Remove item from cart
        function removeCartItem(productId) {
            fetch('/cart/remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    product_id: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove item row
                    const itemRow = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                    itemRow.remove();
                    
                    // Update cart subtotal and total
                    document.getElementById('cart-subtotal').textContent = `${parseFloat(data.cart_total).toFixed(2)} ₪`;
                    document.getElementById('cart-total').textContent = `${parseFloat(data.cart_total).toFixed(2)} ₪`;
                    
                    // Update cart count in header
                    updateCartCountInHeader(data.cart_count);
                    
                    // If cart is empty, reload the page
                    if (data.cart_count === 0) {
                        window.location.reload();
                    }
                    
                    showNotification('Item removed from cart', 'success');
                } else {
                    showNotification(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error removing item:', error);
                showNotification('An error occurred while removing the item', 'danger');
            });
        }
        
        // Update cart count in header
        function updateCartCountInHeader(count) {
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = count;
                
                if (count > 0) {
                    cartCountElement.classList.remove('d-none');
                } else {
                    cartCountElement.classList.add('d-none');
                }
            }
        }
        
        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `toast align-items-center text-white bg-${type}`;
            notification.setAttribute('role', 'alert');
            notification.setAttribute('aria-live', 'assertive');
            notification.setAttribute('aria-atomic', 'true');
            
            notification.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            const container = document.createElement('div');
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.appendChild(notification);
            document.body.appendChild(container);
            
            const toast = new bootstrap.Toast(notification, { delay: 3000 });
            toast.show();
            
            notification.addEventListener('hidden.bs.toast', function() {
                container.remove();
            });
        }
        
        // Event listeners for quantity controls
        document.querySelectorAll('.decrease-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.item-quantity');
                const currentValue = parseInt(input.value);
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                    const productId = this.closest('.cart-item').dataset.productId;
                    updateCartItemQuantity(productId, currentValue - 1);
                }
            });
        });
        
        document.querySelectorAll('.increase-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.item-quantity');
                const currentValue = parseInt(input.value);
                if (currentValue < 99) {
                    input.value = currentValue + 1;
                    const productId = this.closest('.cart-item').dataset.productId;
                    updateCartItemQuantity(productId, currentValue + 1);
                }
            });
        });
        
        document.querySelectorAll('.item-quantity').forEach(input => {
            input.addEventListener('change', function() {
                let value = parseInt(this.value);
                
                if (isNaN(value) || value < 1) {
                    value = 1;
                    this.value = 1;
                } else if (value > 99) {
                    value = 99;
                    this.value = 99;
                }
                
                const productId = this.closest('.cart-item').dataset.productId;
                updateCartItemQuantity(productId, value);
            });
        });
        
        // Event listeners for remove buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.closest('.cart-item').dataset.productId;
                removeCartItem(productId);
            });
        });
        
        // Event listener for clear cart button
        document.getElementById('clear-cart')?.addEventListener('click', function() {
            if (confirm('Are you sure you want to clear your cart?')) {
                window.location.href = '/cart/clear';
            }
        });
    });
</script>
