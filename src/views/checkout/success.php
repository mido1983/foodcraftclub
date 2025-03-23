<?php
/** 
 * @var array $order
 * @var array $orderItems
 */
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-success mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="bi bi-check-circle-fill me-2"></i>Order Placed Successfully</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="display-1 text-success mb-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <h3>Thank you for your order!</h3>
                        <p class="lead">Your order has been received and is being processed.</p>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Order Details</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Order Number:</strong> #<?= $order['id'] ?></p>
                                <p><strong>Date:</strong> <?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method_name']) ?></p>
                                <p><strong>Order Status:</strong> <span class="badge bg-warning text-dark">Processing</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Delivery Address</h5>
                        <p>
                            <?= htmlspecialchars($order['address_line']) ?><br>
                            <?= htmlspecialchars($order['district_name']) ?>, <?= htmlspecialchars($order['city_name']) ?>
                        </p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                        <?php if (!empty($order['delivery_notes'])): ?>
                            <p><strong>Notes:</strong> <?= htmlspecialchars($order['delivery_notes']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Order Summary</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $subtotal = 0;
                                    $itemsBySeller = [];
                                    
                                    foreach ($orderItems as $item): 
                                        $itemTotal = $item['price'] * $item['quantity'];
                                        $subtotal += $itemTotal;
                                        
                                        // Group items by seller
                                        if (!isset($itemsBySeller[$item['seller_id']])) {
                                            $itemsBySeller[$item['seller_id']] = [
                                                'seller_name' => $item['seller_name'],
                                                'items' => [],
                                                'delivery_fee' => $item['delivery_fee']
                                            ];
                                        }
                                        
                                        $itemsBySeller[$item['seller_id']]['items'][] = $item;
                                    ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                                    <div class="small text-muted">Seller: <?= htmlspecialchars($item['seller_name']) ?></div>
                                                </div>
                                            </td>
                                            <td class="text-center"><?= $item['quantity'] ?></td>
                                            <td class="text-end"><?= number_format($item['price'], 2) ?> u20aa</td>
                                            <td class="text-end"><?= number_format($itemTotal, 2) ?> u20aa</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end"><?= number_format($subtotal, 2) ?> u20aa</td>
                                    </tr>
                                    <?php 
                                    $totalDeliveryFee = 0;
                                    foreach ($itemsBySeller as $sellerData): 
                                        $totalDeliveryFee += $sellerData['delivery_fee'];
                                    ?>
                                        <tr>
                                            <td colspan="3" class="text-end">Delivery (<?= htmlspecialchars($sellerData['seller_name']) ?>):</td>
                                            <td class="text-end">
                                                <?= $sellerData['delivery_fee'] > 0 ? number_format($sellerData['delivery_fee'], 2) . ' u20aa' : 'Free' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong><?= number_format($subtotal + $totalDeliveryFee, 2) ?> u20aa</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($order['payment_method_code'] === 'BANK'): ?>
                        <div class="alert alert-info mb-4">
                            <h5><i class="bi bi-info-circle-fill me-2"></i>Bank Transfer Information</h5>
                            <p class="mb-1">Please transfer the total amount to the following account:</p>
                            <p class="mb-1"><strong>Bank:</strong> Food Craft Bank</p>
                            <p class="mb-1"><strong>Account Number:</strong> 123456789</p>
                            <p class="mb-1"><strong>Reference:</strong> Order #<?= $order['id'] ?></p>
                            <p class="mb-0">Your order will be processed once payment is confirmed.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-light border mb-4">
                        <h5><i class="bi bi-truck me-2"></i>Delivery Information</h5>
                        <p class="mb-1">Your order will be delivered within 1-3 business days.</p>
                        <p class="mb-0">You will receive an email with tracking information once your order is shipped.</p>
                    </div>
                    
                    <div class="text-center">
                        <a href="/catalog" class="btn btn-primary">
                            <i class="bi bi-basket me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
