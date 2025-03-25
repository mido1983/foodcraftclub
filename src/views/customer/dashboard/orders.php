<?php
/** @var $this \App\Core\View */
/** @var $user \App\Models\User */
/** @var $orders array */

$this->title = 'Мои заказы - Food Craft Club';
$this->route = 'customer/dashboard/orders';
?>

<div class="container py-4">
    <div class="row">
        <!-- Боковая панель -->
        <div class="col-lg-3 mb-4">
            <?php include_once __DIR__ . '/sidebar.php'; ?>
        </div>
        
        <!-- Основной контент -->
        <div class="col-lg-9">
            <div class="bg-white shadow-sm rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0">Мои заказы</h2>
                    <a href="/catalog" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> Новый заказ
                    </a>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="alert alert-light text-center py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
                        <p class="mb-3">У вас еще нет заказов</p>
                        <a href="/catalog" class="btn btn-primary">Перейти в каталог</a>
                    </div>
                <?php else: ?>
                    <!-- Фильтры и поиск -->
                    <div class="orders-filters d-flex flex-wrap gap-2 mb-4">
                        <div class="input-group input-group-sm me-2" style="max-width: 250px;">
                            <input type="text" class="form-control" id="orderSearch" placeholder="Поиск по номеру заказа">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        
                        <select class="form-select form-select-sm" id="statusFilter" style="max-width: 200px;">
                            <option value="all">Все статусы</option>
                            <option value="pending">Ожидает обработки</option>
                            <option value="processing">В обработке</option>
                            <option value="shipped">Отправлен</option>
                            <option value="delivered">Доставлен</option>
                            <option value="canceled">Отменен</option>
                        </select>
                        
                        <select class="form-select form-select-sm" id="sortOrder" style="max-width: 200px;">
                            <option value="newest">Сначала новые</option>
                            <option value="oldest">Сначала старые</option>
                            <option value="price_high">По цене (убыв.)</option>
                            <option value="price_low">По цене (возр.)</option>
                        </select>
                    </div>
                    
                    <!-- Таблица заказов -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="ordersTable">
                            <thead class="table-light">
                                <tr>
                                    <th>№ заказа</th>
                                    <th>Дата</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th>Товары</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr data-status="<?= $order->status ?>" data-order-id="<?= $order->id ?>" data-price="<?= $order->total_price ?>">
                                        <td class="fw-bold">#<?= $order->id ?></td>
                                        <td><?= $order->getFormattedDate() ?></td>
                                        <td class="fw-bold"><?= $order->getFormattedTotal() ?></td>
                                        <td>
                                            <span class="badge bg-<?= $this->getStatusBadgeClass($order->status) ?>">
                                                <?= $order->getStatusText() ?>
                                            </span>
                                        </td>
                                        <td><?= $order->getItemsCount() ?> шт.</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/customer/orders/<?= $order->id ?>" class="btn btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($order->status === 'pending'): ?>
                                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal" data-order-id="<?= $order->id ?>">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($order->status === 'delivered'): ?>
                                                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#reviewOrderModal" data-order-id="<?= $order->id ?>">
                                                        <i class="bi bi-star"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Пагинация -->
                    <?php if (count($orders) > 10): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Предыдущая</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Следующая</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно отмены заказа -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelOrderModalLabel">Отмена заказа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите отменить заказ #<span id="cancelOrderId"></span>?</p>
                <p class="text-danger">Это действие нельзя будет отменить.</p>
                <div class="mb-3">
                    <label for="cancelReason" class="form-label">Причина отмены (необязательно)</label>
                    <textarea class="form-control" id="cancelReason" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmCancelOrder">Подтвердить отмену</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно оставления отзыва -->
<div class="modal fade" id="reviewOrderModal" tabindex="-1" aria-labelledby="reviewOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewOrderModalLabel">Оставить отзыв о заказе</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Оставьте отзыв о заказе #<span id="reviewOrderId"></span></p>
                <div class="mb-3">
                    <label class="form-label">Оценка</label>
                    <div class="rating">
                        <i class="bi bi-star fs-3 rating-star" data-rating="1"></i>
                        <i class="bi bi-star fs-3 rating-star" data-rating="2"></i>
                        <i class="bi bi-star fs-3 rating-star" data-rating="3"></i>
                        <i class="bi bi-star fs-3 rating-star" data-rating="4"></i>
                        <i class="bi bi-star fs-3 rating-star" data-rating="5"></i>
                    </div>
                    <input type="hidden" id="ratingValue" value="0">
                </div>
                <div class="mb-3">
                    <label for="reviewText" class="form-label">Ваш отзыв</label>
                    <textarea class="form-control" id="reviewText" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-success" id="submitReview">Отправить отзыв</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Фильтрация заказов по статусу
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                const status = this.value;
                const rows = document.querySelectorAll('#ordersTable tbody tr');
                
                rows.forEach(row => {
                    if (status === 'all' || row.dataset.status === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Сортировка заказов
        const sortOrder = document.getElementById('sortOrder');
        if (sortOrder) {
            sortOrder.addEventListener('change', function() {
                const value = this.value;
                const tbody = document.querySelector('#ordersTable tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                
                rows.sort((a, b) => {
                    if (value === 'newest') {
                        return parseInt(b.dataset.orderId) - parseInt(a.dataset.orderId);
                    } else if (value === 'oldest') {
                        return parseInt(a.dataset.orderId) - parseInt(b.dataset.orderId);
                    } else if (value === 'price_high') {
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    } else if (value === 'price_low') {
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    }
                    return 0;
                });
                
                rows.forEach(row => tbody.appendChild(row));
            });
        }
        
        // Поиск по номеру заказа
        const orderSearch = document.getElementById('orderSearch');
        if (orderSearch) {
            orderSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#ordersTable tbody tr');
                
                rows.forEach(row => {
                    const orderId = row.dataset.orderId;
                    if (orderId.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Модальное окно отмены заказа
        const cancelOrderModal = document.getElementById('cancelOrderModal');
        if (cancelOrderModal) {
            cancelOrderModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const orderId = button.getAttribute('data-order-id');
                document.getElementById('cancelOrderId').textContent = orderId;
            });
            
            document.getElementById('confirmCancelOrder').addEventListener('click', function() {
                const orderId = document.getElementById('cancelOrderId').textContent;
                const reason = document.getElementById('cancelReason').value;
                
                // AJAX запрос на отмену заказа
                fetch('/customer/orders/cancel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= $this->csrf_token ?>'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Обновление статуса заказа в таблице
                        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
                        if (row) {
                            row.dataset.status = 'canceled';
                            const statusCell = row.querySelector('td:nth-child(4)');
                            statusCell.innerHTML = '<span class="badge bg-danger">Отменен</span>';
                            
                            // Удаление кнопки отмены
                            const actionsCell = row.querySelector('td:last-child');
                            const cancelButton = actionsCell.querySelector('button[data-bs-target="#cancelOrderModal"]');
                            if (cancelButton) {
                                cancelButton.remove();
                            }
                        }
                        
                        // Закрытие модального окна
                        const modal = bootstrap.Modal.getInstance(cancelOrderModal);
                        modal.hide();
                        
                        // Уведомление пользователя
                        alert('Заказ успешно отменен');
                    } else {
                        alert('Ошибка при отмене заказа: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка при отмене заказа');
                });
            });
        }
        
        // Модальное окно оставления отзыва
        const reviewOrderModal = document.getElementById('reviewOrderModal');
        if (reviewOrderModal) {
            reviewOrderModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const orderId = button.getAttribute('data-order-id');
                document.getElementById('reviewOrderId').textContent = orderId;
            });
            
            // Обработка клика по звездам рейтинга
            const ratingStars = document.querySelectorAll('.rating-star');
            ratingStars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    document.getElementById('ratingValue').value = rating;
                    
                    // Визуальное отображение выбранного рейтинга
                    ratingStars.forEach(s => {
                        if (parseInt(s.dataset.rating) <= rating) {
                            s.classList.remove('bi-star');
                            s.classList.add('bi-star-fill', 'text-warning');
                        } else {
                            s.classList.remove('bi-star-fill', 'text-warning');
                            s.classList.add('bi-star');
                        }
                    });
                });
            });
            
            document.getElementById('submitReview').addEventListener('click', function() {
                const orderId = document.getElementById('reviewOrderId').textContent;
                const rating = document.getElementById('ratingValue').value;
                const reviewText = document.getElementById('reviewText').value;
                
                if (rating === '0') {
                    alert('Пожалуйста, выберите оценку');
                    return;
                }
                
                // AJAX запрос на отправку отзыва
                fetch('/customer/orders/review', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= $this->csrf_token ?>'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        rating: rating,
                        review: reviewText
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Закрытие модального окна
                        const modal = bootstrap.Modal.getInstance(reviewOrderModal);
                        modal.hide();
                        
                        // Уведомление пользователя
                        alert('Спасибо за ваш отзыв!');
                        
                        // Удаление кнопки отзыва
                        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
                        if (row) {
                            const actionsCell = row.querySelector('td:last-child');
                            const reviewButton = actionsCell.querySelector('button[data-bs-target="#reviewOrderModal"]');
                            if (reviewButton) {
                                reviewButton.remove();
                            }
                        }
                    } else {
                        alert('Ошибка при отправке отзыва: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка при отправке отзыва');
                });
            });
        }
    });
</script>

<?php
// Добавление хелпера для определения класса бейджа статуса
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
