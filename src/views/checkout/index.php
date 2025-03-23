<?php
/** 
 * @var array $cartItems
 * @var array $itemsBySeller
 * @var float $totalAmount
 * @var array $cities
 * @var array $districts
 * @var array $paymentMethods
 * @var array $sellerDeliveryAreas
 * @var array $sellerPaymentOptions
 * @var array $belowMinimumSellers
 */
?>

<div class="container mt-4">
    <h1 class="mb-4">Checkout</h1>
    
    <?php if (!empty($belowMinimumSellers)): ?>
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Minimum Order Requirements</h5>
            <p>The following sellers have minimum order requirements that have not been met:</p>
            <ul>
                <?php foreach ($belowMinimumSellers as $seller): ?>
                    <li>
                        <strong><?= htmlspecialchars($seller['seller_name']) ?></strong>: 
                        Current order amount: <?= number_format($seller['current_amount'], 2) ?> ₪, 
                        Minimum required: <?= number_format($seller['min_amount'], 2) ?> ₪
                    </li>
                <?php endforeach; ?>
            </ul>
            <p>Please <a href="/cart" class="alert-link">return to your cart</a> and add more items to meet the minimum requirements.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Checkout form -->
            <div class="col-md-8">
                <form id="checkout-form" action="/checkout/process" method="post">
                    <!-- Delivery address -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Delivery Address</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="city_id" class="form-label">City</label>
                                    <select class="form-select" id="city_id" name="city_id" required>
                                        <option value="">Select City</option>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= $city['id'] ?>"><?= htmlspecialchars($city['city_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="address_line" class="form-label">Street Address</label>
                                <input type="text" class="form-control" id="address_line" name="address_line" placeholder="Street, Building, Apartment" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="For delivery coordination" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="delivery_notes" class="form-label">Delivery Notes (Optional)</label>
                                        <input type="text" class="form-control" id="delivery_notes" name="delivery_notes" placeholder="Special instructions for delivery">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment method -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($paymentMethods as $method): ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method_id" id="payment_method_<?= $method['id'] ?>" value="<?= $method['id'] ?>" required>
                                    <label class="form-check-label" for="payment_method_<?= $method['id'] ?>">
                                        <?php if ($method['method_code'] === 'CASH'): ?>
                                            <i class="bi bi-cash me-2"></i>
                                        <?php elseif ($method['method_code'] === 'CARD'): ?>
                                            <i class="bi bi-credit-card me-2"></i>
                                        <?php elseif ($method['method_code'] === 'BANK'): ?>
                                            <i class="bi bi-bank me-2"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($method['method_name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Credit card form (will be shown/hidden based on payment method) -->
                            <div id="credit-card-form" class="mt-3 d-none">
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="card_number" class="form-label">Card Number</label>
                                        <input type="text" class="form-control" id="card_number" placeholder="1234 5678 9012 3456">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="expiry_date" class="form-label">Expiry Date</label>
                                        <input type="text" class="form-control" id="expiry_date" placeholder="MM/YY">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <input type="text" class="form-control" id="cvv" placeholder="123">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="card_holder" class="form-label">Card Holder Name</label>
                                    <input type="text" class="form-control" id="card_holder" placeholder="Name on card">
                                </div>
                            </div>
                            
                            <!-- Bank transfer details (will be shown/hidden based on payment method) -->
                            <div id="bank-transfer-info" class="mt-3 d-none">
                                <div class="alert alert-info">
                                    <h6>Bank Transfer Details</h6>
                                    <p class="mb-1">Please transfer the total amount to the following account:</p>
                                    <p class="mb-1"><strong>Bank:</strong> Food Craft Bank</p>
                                    <p class="mb-1"><strong>Account Number:</strong> 123456789</p>
                                    <p class="mb-1"><strong>Reference:</strong> Your order number will be provided after checkout</p>
                                    <p class="mb-0">Your order will be processed once payment is confirmed.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-4">
                        <a href="/cart" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>Return to Cart
                        </a>
                        <button type="submit" class="btn btn-primary" id="place-order-btn">
                            <i class="bi bi-check-circle me-2"></i>Place Order
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Order summary -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($itemsBySeller as $sellerId => $sellerData): ?>
                                <li class="list-group-item">
                                    <h6 class="mb-3"><?= htmlspecialchars($sellerData['seller_name']) ?></h6>
                                    <?php foreach ($sellerData['items'] as $item): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <span><?= htmlspecialchars($item['product_name']) ?></span>
                                                <small class="text-muted d-block">Qty: <?= $item['quantity'] ?></small>
                                            </div>
                                            <span><?= number_format($item['price'] * $item['quantity'], 2) ?> ₪</span>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                        <span>Subtotal</span>
                                        <span><?= number_format($sellerData['subtotal'], 2) ?> ₪</span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span>Delivery</span>
                                        <span id="delivery-fee-<?= $sellerId ?>">Calculated based on address</span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="card-body border-top">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span><?= number_format($totalAmount, 2) ?> ₪</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery</span>
                                <span id="total-delivery-fee">Calculated based on address</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total</strong>
                                <strong id="order-total"><?= number_format($totalAmount, 2) ?> ₪</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Store delivery areas data
        const sellerDeliveryAreas = <?= json_encode($sellerDeliveryAreas) ?>;
        
        // Store payment options data
        const sellerPaymentOptions = <?= json_encode($sellerPaymentOptions) ?>;
        
        // Store seller IDs
        const sellerIds = Object.keys(<?= json_encode($itemsBySeller) ?>);
        
        // Filter districts based on selected city
        document.getElementById('city_id').addEventListener('change', function() {
            const cityId = this.value;
            
            // Calculate delivery fees
            calculateDeliveryFees(cityId, null);
        });
        
        // Calculate delivery fees based on selected city and district
        function calculateDeliveryFees(cityId, districtId) {
            let totalDeliveryFee = 0;
            
            sellerIds.forEach(sellerId => {
                let sellerDeliveryFee = 0;
                let freeDeliveryThreshold = null;
                let sellerSubtotal = parseFloat(document.querySelector(`#delivery-fee-${sellerId}`).closest('li').querySelector('.d-flex.justify-content-between.mt-2.pt-2 span:last-child').textContent.replace('₪', '').trim());
                
                // Check if seller has delivery areas for selected city/district
                if (sellerDeliveryAreas[sellerId]) {
                    const areas = sellerDeliveryAreas[sellerId];
                    
                    // First try to find exact city+district match
                    let exactMatch = areas.find(area => 
                        area.city_id == cityId && 
                        (districtId ? area.district_id == districtId : true)
                    );
                    
                    // If no exact match, try to find city-only match (null district_id means all districts)
                    if (!exactMatch) {
                        exactMatch = areas.find(area => 
                            area.city_id == cityId && 
                            area.district_id === null
                        );
                    }
                    
                    if (exactMatch) {
                        sellerDeliveryFee = parseFloat(exactMatch.delivery_fee);
                        freeDeliveryThreshold = exactMatch.free_from_amount !== null ? parseFloat(exactMatch.free_from_amount) : null;
                        
                        // Check if order qualifies for free delivery
                        if (freeDeliveryThreshold !== null && sellerSubtotal >= freeDeliveryThreshold) {
                            sellerDeliveryFee = 0;
                        }
                    } else {
                        // No delivery to this area
                        sellerDeliveryFee = 'Not available';
                    }
                } else {
                    // Seller has no delivery areas defined
                    sellerDeliveryFee = 'Not available';
                }
                
                // Update seller delivery fee display
                const deliveryFeeElement = document.getElementById(`delivery-fee-${sellerId}`);
                if (typeof sellerDeliveryFee === 'number') {
                    deliveryFeeElement.textContent = sellerDeliveryFee > 0 ? `${sellerDeliveryFee.toFixed(2)} ₪` : 'Free';
                    totalDeliveryFee += sellerDeliveryFee;
                } else {
                    deliveryFeeElement.textContent = sellerDeliveryFee;
                    deliveryFeeElement.classList.add('text-danger');
                }
            });
            
            // Update total delivery fee
            const totalDeliveryFeeElement = document.getElementById('total-delivery-fee');
            totalDeliveryFeeElement.textContent = `${totalDeliveryFee.toFixed(2)} ₪`;
            
            // Update order total
            const subtotal = parseFloat(<?= $totalAmount ?>);
            const orderTotalElement = document.getElementById('order-total');
            orderTotalElement.textContent = `${(subtotal + totalDeliveryFee).toFixed(2)} ₪`;
        }
        
        // Reset delivery fees when no city/district selected
        function resetDeliveryFees() {
            sellerIds.forEach(sellerId => {
                const deliveryFeeElement = document.getElementById(`delivery-fee-${sellerId}`);
                deliveryFeeElement.textContent = 'Calculated based on address';
                deliveryFeeElement.classList.remove('text-danger');
            });
            
            document.getElementById('total-delivery-fee').textContent = 'Calculated based on address';
            document.getElementById('order-total').textContent = `${parseFloat(<?= $totalAmount ?>).toFixed(2)} ₪`;
        }
        
        // Validate if selected payment method is available for all sellers
        function validatePaymentMethod(methodId) {
            let allSellersAcceptMethod = true;
            let sellersNotAccepting = [];
            
            sellerIds.forEach(sellerId => {
                if (sellerPaymentOptions[sellerId]) {
                    const acceptsMethod = sellerPaymentOptions[sellerId].some(option => 
                        option.payment_method_id == methodId && option.enabled == 1
                    );
                    
                    if (!acceptsMethod) {
                        allSellersAcceptMethod = false;
                        const sellerName = document.querySelector(`#delivery-fee-${sellerId}`).closest('li').querySelector('h6').textContent;
                        sellersNotAccepting.push(sellerName);
                    }
                }
            });
            
            const placeOrderBtn = document.getElementById('place-order-btn');
            
            if (!allSellersAcceptMethod && sellersNotAccepting.length > 0) {
                // Create or update warning message
                let warningElement = document.getElementById('payment-method-warning');
                
                if (!warningElement) {
                    warningElement = document.createElement('div');
                    warningElement.id = 'payment-method-warning';
                    warningElement.className = 'alert alert-warning mt-3';
                    document.querySelector('input[name="payment_method_id"]').closest('.card-body').appendChild(warningElement);
                }
                
                const methodName = document.querySelector(`#payment_method_${methodId}`).nextElementSibling.textContent.trim();
                warningElement.innerHTML = `
                    <p class="mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>The following sellers do not accept ${methodName}:</p>
                    <ul class="mb-0">
                        ${sellersNotAccepting.map(seller => `<li>${seller}</li>`).join('')}
                    </ul>
                    <p class="mt-2 mb-0">Please select a different payment method or remove items from these sellers.</p>
                `;
                
                // Disable place order button
                placeOrderBtn.disabled = true;
                placeOrderBtn.title = 'Cannot place order with current payment method';
            } else {
                // Remove warning if exists
                const warningElement = document.getElementById('payment-method-warning');
                if (warningElement) {
                    warningElement.remove();
                }
                
                // Enable place order button
                placeOrderBtn.disabled = false;
                placeOrderBtn.title = '';
            }
        }
        
        // Toggle payment method details
        const paymentMethodInputs = document.querySelectorAll('input[name="payment_method_id"]');
        const creditCardForm = document.getElementById('credit-card-form');
        const bankTransferInfo = document.getElementById('bank-transfer-info');
        
        paymentMethodInputs.forEach(input => {
            input.addEventListener('change', function() {
                const methodId = parseInt(this.value);
                
                // Hide all payment details first
                creditCardForm.classList.add('d-none');
                bankTransferInfo.classList.add('d-none');
                
                // Show relevant payment details based on selection
                if (methodId === 2) { // Credit/Debit Card
                    creditCardForm.classList.remove('d-none');
                } else if (methodId === 3) { // Bank Transfer
                    bankTransferInfo.classList.remove('d-none');
                }
                
                // Check if selected payment method is available for all sellers
                validatePaymentMethod(methodId);
            });
        });
        
        // Form validation before submission
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const cityId = document.getElementById('city_id').value;
            const paymentMethod = document.querySelector('input[name="payment_method_id"]:checked');
            
            if (!cityId) {
                e.preventDefault();
                alert('Please select a city for delivery.');
                return false;
            }
            
            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method.');
                return false;
            }
            
            // Check if credit card details are filled if card payment selected
            if (paymentMethod.value == '2' && !document.getElementById('credit-card-form').classList.contains('d-none')) {
                const cardNumber = document.getElementById('card_number').value;
                const expiryDate = document.getElementById('expiry_date').value;
                const cvv = document.getElementById('cvv').value;
                const cardHolder = document.getElementById('card_holder').value;
                
                if (!cardNumber || !expiryDate || !cvv || !cardHolder) {
                    e.preventDefault();
                    alert('Please fill in all credit card details.');
                    return false;
                }
            }
            
            return true;
        });
    });
</script>
