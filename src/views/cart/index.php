<?php
/** 
 * @var array $cartItems
 * @var array $itemsBySeller
 * @var array $sellerProfiles
 * @var array $belowMinimumSellers
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
                <?php foreach ($itemsBySeller as $sellerId => $sellerData): ?>
                    <div class="card mb-4 seller-block" data-seller-id="<?= $sellerId ?>">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Seller: <?= htmlspecialchars($sellerData['seller_name']) ?></h5>
                            <a href="/chat/seller/<?= $sellerId ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-chat-dots"></i> Contact Seller
                            </a>
                        </div>
                        
                        <?php if (isset($belowMinimumSellers[$sellerId])): ?>
                            <div class="alert alert-warning m-3 mb-0 minimum-order-alert" data-seller-id="<?= $sellerId ?>" data-min-amount="<?= $belowMinimumSellers[$sellerId]['min_order_amount'] ?>">
                                <p class="mb-1"><strong>Minimum order amount: <?= number_format($belowMinimumSellers[$sellerId]['min_order_amount'], 2) ?> ₪</strong></p>
                                <p class="mb-0">Current total: <span class="current-amount"><?= number_format($belowMinimumSellers[$sellerId]['current_amount'], 2) ?></span> ₪ <br>
                                You need to add <span class="missing-amount"><?= number_format($belowMinimumSellers[$sellerId]['missing_amount'], 2) ?></span> ₪ more to proceed with checkout.</p>
                            </div>
                        <?php endif; ?>
                        
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
                                        <?php foreach ($sellerData['items'] as $item): ?>
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
                                        <tr class="border-top">
                                            <td colspan="3" class="text-end py-3"><strong>Subtotal:</strong></td>
                                            <td class="text-end py-3"><strong><?= number_format($sellerData['subtotal'], 2) ?> ₪</strong></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
                            <span><?= number_format($totalAmount, 2) ?> ₪</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Delivery</span>
                            <span>Calculated at checkout</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total</strong>
                            <strong><?= number_format($totalAmount, 2) ?> ₪</strong>
                        </div>
                        <a href="/checkout" class="btn btn-primary w-100" id="proceed-to-checkout" <?= !empty($belowMinimumSellers) ? 'disabled' : '' ?>>
                            Proceed to Checkout
                        </a>
                        <?php if (!empty($belowMinimumSellers)): ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                <small>Please meet the minimum order amount for all sellers before proceeding to checkout.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <a href="/catalog" class="btn btn-outline-secondary">Continue Shopping</a>
                    <button id="clear-cart" class="btn btn-outline-danger">Clear Cart</button>
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
                    const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                    const itemTotal = cartItem.querySelector('.item-total');
                    itemTotal.textContent = `${data.item_total.toFixed(2)} ₪`;
                    
                    // Update cart count
                    const cartCount = document.querySelector('#cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Update cart total
                    const cartTotal = document.querySelector('.justify-content-between.mb-4 strong:last-child');
                    cartTotal.textContent = `${data.cart_total.toFixed(2)} ₪`;
                    
                    // Update subtotal
                    const subtotal = document.querySelector('.justify-content-between.mb-3 span:last-child');
                    subtotal.textContent = `${data.cart_total.toFixed(2)} ₪`;
                    
                    // Update seller subtotal
                    const sellerBlock = cartItem.closest('.seller-block');
                    const sellerId = sellerBlock.dataset.sellerId;
                    let sellerSubtotal = 0;
                    
                    // Calculate new seller subtotal
                    sellerBlock.querySelectorAll('.cart-item').forEach(item => {
                        const itemTotalText = item.querySelector('.item-total').textContent;
                        const itemTotalValue = parseFloat(itemTotalText.replace(' ₪', '').replace(',', ''));
                        sellerSubtotal += itemTotalValue;
                    });
                    
                    // Update seller subtotal in UI
                    const sellerSubtotalElement = sellerBlock.querySelector('tbody tr:last-child td:nth-child(2)');
                    if (sellerSubtotalElement) {
                        sellerSubtotalElement.textContent = `${sellerSubtotal.toFixed(2)} ₪`;
                    }
                    
                    // Check and update minimum order alert
                    updateMinimumOrderAlert(sellerId, sellerSubtotal);
                    
                    // Check if all minimum orders are met
                    checkAllMinimumOrdersMet();
                    
                    showNotification('Cart updated');
                } else {
                    showNotification(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'danger');
            });
        }
        
        // Remove item from cart
        function removeCartItem(productId) {
            // Преобразуем productId в число, чтобы избежать ошибок при отправке запроса
            productId = parseInt(productId);
            console.log('Removing product:', productId);
            
            const requestData = {
                product_id: productId
            };
            console.log('Request data:', requestData);
            
            fetch('/cart/remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Remove item from DOM
                    const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                    const sellerBlock = cartItem.closest('.seller-block');
                    const sellerId = sellerBlock.dataset.sellerId;
                    cartItem.remove();
                    
                    // Calculate new seller subtotal
                    let sellerSubtotal = 0;
                    const remainingItems = sellerBlock.querySelectorAll('.cart-item');
                    
                    // Check if seller has no more items
                    if (remainingItems.length === 0) {
                        // Remove seller block if no items left
                        sellerBlock.remove();
                    } else {
                        // Recalculate seller subtotal
                        remainingItems.forEach(item => {
                            const itemTotalText = item.querySelector('.item-total').textContent;
                            const itemTotalValue = parseFloat(itemTotalText.replace(' ₪', '').replace(',', ''));
                            sellerSubtotal += itemTotalValue;
                        });
                        
                        // Update seller subtotal display
                        const sellerSubtotalElement = sellerBlock.querySelector('tbody tr:last-child td:nth-child(2)');
                        if (sellerSubtotalElement) {
                            sellerSubtotalElement.textContent = `${sellerSubtotal.toFixed(2)} ₪`;
                        }
                        
                        // Check and update minimum order alert
                        updateMinimumOrderAlert(sellerId, sellerSubtotal);
                    }
                    
                    // Update cart count
                    const cartCount = document.querySelector('#cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Update cart total
                    const cartTotal = document.querySelector('.justify-content-between.mb-4 strong:last-child');
                    cartTotal.textContent = `${data.cart_total.toFixed(2)} ₪`;
                    
                    // Update subtotal
                    const subtotal = document.querySelector('.justify-content-between.mb-3 span:last-child');
                    subtotal.textContent = `${data.cart_total.toFixed(2)} ₪`;
                    
                    // Check if all minimum orders are met
                    checkAllMinimumOrdersMet();
                    
                    // If cart is empty, reload page
                    if (data.cart_count === 0) {
                        location.reload();
                        return; // Прекращаем выполнение функции, чтобы предотвратить дальнейшие обновления страницы
                    }
                    
                    showNotification('Item removed from cart');
                } else {
                    showNotification(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'danger');
            });
        }
        
        // Event listeners for quantity controls
        document.querySelectorAll('.decrease-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.nextElementSibling;
                const productId = this.closest('.cart-item').dataset.productId;
                let quantity = parseInt(input.value) - 1;
                
                if (quantity < 1) quantity = 1;
                input.value = quantity;
                
                updateCartItemQuantity(productId, quantity);
            });
        });
        
        document.querySelectorAll('.increase-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const productId = this.closest('.cart-item').dataset.productId;
                let quantity = parseInt(input.value) + 1;
                
                if (quantity > 99) quantity = 99;
                input.value = quantity;
                
                updateCartItemQuantity(productId, quantity);
            });
        });
        
        document.querySelectorAll('.item-quantity').forEach(input => {
            input.addEventListener('change', function() {
                const productId = this.closest('.cart-item').dataset.productId;
                let quantity = parseInt(this.value);
                
                if (isNaN(quantity) || quantity < 1) quantity = 1;
                if (quantity > 99) quantity = 99;
                this.value = quantity;
                
                updateCartItemQuantity(productId, quantity);
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
        document.getElementById('clear-cart').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear your cart?')) {
                window.location.href = '/cart/clear';
            }
        });
        
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
            
            const toast = new bootstrap.Toast(notification);
            toast.show();
            
            // Remove notification after it's hidden
            notification.addEventListener('hidden.bs.toast', function () {
                container.remove();
            });
        }
        
        // Update minimum order alert
        function updateMinimumOrderAlert(sellerId, sellerSubtotal) {
            const minimumOrderAlert = document.querySelector(`.minimum-order-alert[data-seller-id="${sellerId}"]`);
            if (!minimumOrderAlert) {
                return; // Уведомление не найдено, возможно, продавец уже достиг минимального заказа
            }
            
            const minAmount = parseFloat(minimumOrderAlert.dataset.minAmount);
            const currentAmount = sellerSubtotal;
            const missingAmount = minAmount - currentAmount;
            
            if (missingAmount <= 0) {
                minimumOrderAlert.remove();
            } else {
                minimumOrderAlert.querySelector('.current-amount').textContent = `${currentAmount.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₪`;
                minimumOrderAlert.querySelector('.missing-amount').textContent = `${missingAmount.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₪`;
            }
        }
        
        // Check if all minimum orders are met
        function checkAllMinimumOrdersMet() {
            const minimumOrderAlerts = document.querySelectorAll('.minimum-order-alert');
            const proceedToCheckoutButton = document.querySelector('#proceed-to-checkout');
            
            if (!proceedToCheckoutButton) {
                return; // Кнопка не найдена, возможно, корзина пуста
            }
            
            if (minimumOrderAlerts.length === 0) {
                proceedToCheckoutButton.removeAttribute('disabled');
                const warningMessage = document.querySelector('.alert-warning.mt-3');
                if (warningMessage) {
                    warningMessage.style.display = 'none';
                }
            } else {
                proceedToCheckoutButton.setAttribute('disabled', 'disabled');
                const warningMessage = document.querySelector('.alert-warning.mt-3');
                if (warningMessage) {
                    warningMessage.style.display = 'block';
                }
            }
        }
    });
</script>
