<?php
/** @var $this \App\Core\View */
/** @var $user \App\Models\User */
/** @var $products array */

$this->title = 'Избранные товары - Food Craft Club';
$this->route = 'customer/dashboard/wishlist';
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
                    <h2 class="h4 mb-0">Избранные товары</h2>
                    <a href="/catalog" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> Добавить товары
                    </a>
                </div>
                
                <?php if (empty($products)): ?>
                    <div class="alert alert-light text-center py-5">
                        <i class="bi bi-heart fs-1 d-block mb-3 text-muted"></i>
                        <p class="mb-3">У вас еще нет избранных товаров</p>
                        <a href="/catalog" class="btn btn-primary">Перейти в каталог</a>
                    </div>
                <?php else: ?>
                    <!-- Фильтры и поиск -->
                    <div class="wishlist-filters d-flex flex-wrap gap-2 mb-4">
                        <div class="input-group input-group-sm me-2" style="max-width: 250px;">
                            <input type="text" class="form-control" id="productSearch" placeholder="Поиск товаров">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        
                        <select class="form-select form-select-sm" id="sortOrder" style="max-width: 200px;">
                            <option value="name_asc">По названию (А-Я)</option>
                            <option value="name_desc">По названию (Я-А)</option>
                            <option value="price_low">По цене (возр.)</option>
                            <option value="price_high">По цене (убыв.)</option>
                            <option value="date_new">Сначала новые</option>
                            <option value="date_old">Сначала старые</option>
                        </select>
                    </div>
                    
                    <!-- Сетка товаров -->
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="wishlistGrid">
                        <?php foreach ($products as $product): ?>
                            <div class="col wishlist-item" data-product-name="<?= strtolower($this->escape($product->name)) ?>" data-product-price="<?= $product->price ?>" data-product-date="<?= strtotime($product->created_at) ?>">
                                <div class="card h-100 position-relative border-0 shadow-sm product-card">
                                    <!-- Кнопка удаления из избранного -->
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle remove-from-wishlist" data-product-id="<?= $product->id ?>">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    
                                    <!-- Изображение товара -->
                                    <div class="product-image-container" style="height: 200px; overflow: hidden;">
                                        <img src="<?= $product->image ?? '/assets/img/no-image.jpg' ?>" class="card-img-top" alt="<?= $this->escape($product->name) ?>" style="object-fit: cover; height: 100%; width: 100%;">
                                    </div>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title product-name"><?= $this->escape($product->name) ?></h5>
                                        <p class="card-text small text-muted product-description"><?= $this->escape(substr($product->description, 0, 80)) ?>...</p>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <span class="fw-bold text-primary product-price"><?= number_format($product->price, 2, '.', ' ') ?> руб.</span>
                                            
                                            <div class="btn-group">
                                                <a href="/product/<?= $product->id ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-primary add-to-cart" data-product-id="<?= $product->id ?>">
                                                    <i class="bi bi-cart-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Поиск товаров
        const productSearch = document.getElementById('productSearch');
        if (productSearch) {
            productSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const items = document.querySelectorAll('.wishlist-item');
                
                items.forEach(item => {
                    const productName = item.dataset.productName;
                    if (productName.includes(searchTerm)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
        
        // Сортировка товаров
        const sortOrder = document.getElementById('sortOrder');
        if (sortOrder) {
            sortOrder.addEventListener('change', function() {
                const value = this.value;
                const grid = document.getElementById('wishlistGrid');
                const items = Array.from(grid.querySelectorAll('.wishlist-item'));
                
                items.sort((a, b) => {
                    if (value === 'name_asc') {
                        return a.dataset.productName.localeCompare(b.dataset.productName);
                    } else if (value === 'name_desc') {
                        return b.dataset.productName.localeCompare(a.dataset.productName);
                    } else if (value === 'price_low') {
                        return parseFloat(a.dataset.productPrice) - parseFloat(b.dataset.productPrice);
                    } else if (value === 'price_high') {
                        return parseFloat(b.dataset.productPrice) - parseFloat(a.dataset.productPrice);
                    } else if (value === 'date_new') {
                        return parseInt(b.dataset.productDate) - parseInt(a.dataset.productDate);
                    } else if (value === 'date_old') {
                        return parseInt(a.dataset.productDate) - parseInt(b.dataset.productDate);
                    }
                    return 0;
                });
                
                items.forEach(item => grid.appendChild(item));
            });
        }
        
        // Удаление из избранного
        const removeButtons = document.querySelectorAll('.remove-from-wishlist');
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const productCard = this.closest('.wishlist-item');
                
                if (confirm('Вы уверены, что хотите удалить этот товар из избранного?')) {
                    // AJAX запрос на удаление из избранного
                    fetch('/customer/wishlist/remove', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?= $this->csrf_token ?>'
                        },
                        body: JSON.stringify({
                            product_id: productId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Анимация удаления карточки товара
                            productCard.style.opacity = '0';
                            productCard.style.transform = 'scale(0.8)';
                            productCard.style.transition = 'all 0.3s ease';
                            
                            setTimeout(() => {
                                productCard.remove();
                                
                                // Проверка, остались ли еще товары
                                const remainingItems = document.querySelectorAll('.wishlist-item');
                                if (remainingItems.length === 0) {
                                    // Если товаров не осталось, показываем сообщение
                                    const container = document.querySelector('.col-lg-9 > div');
                                    container.innerHTML = `
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h2 class="h4 mb-0">Избранные товары</h2>
                                            <a href="/catalog" class="btn btn-primary btn-sm">
                                                <i class="bi bi-plus-lg me-1"></i> Добавить товары
                                            </a>
                                        </div>
                                        <div class="alert alert-light text-center py-5">
                                            <i class="bi bi-heart fs-1 d-block mb-3 text-muted"></i>
                                            <p class="mb-3">У вас еще нет избранных товаров</p>
                                            <a href="/catalog" class="btn btn-primary">Перейти в каталог</a>
                                        </div>
                                    `;
                                }
                            }, 300);
                        } else {
                            alert('Ошибка при удалении товара из избранного: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Произошла ошибка при удалении товара из избранного');
                    });
                }
            });
        });
        
        // Добавление в корзину
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                
                // AJAX запрос на добавление в корзину
                fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= $this->csrf_token ?>'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Анимация успешного добавления
                        const originalText = button.innerHTML;
                        button.innerHTML = '<i class="bi bi-check-lg"></i>';
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-success');
                        
                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.classList.remove('btn-success');
                            button.classList.add('btn-primary');
                        }, 2000);
                        
                        // Обновление счетчика корзины, если он есть
                        const cartCounter = document.querySelector('.cart-counter');
                        if (cartCounter) {
                            cartCounter.textContent = data.data.cart_count;
                            cartCounter.style.display = 'inline-block';
                        }
                    } else {
                        alert('Ошибка при добавлении товара в корзину: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка при добавлении товара в корзину');
                });
            });
        });
    });
</script>
