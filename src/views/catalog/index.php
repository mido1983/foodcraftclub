<?php
/** 
 * @var array $categories
 * @var array $sellers
 */
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Фильтры (левая колонка) -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Фильтры</h5>
                </div>
                <div class="card-body">
                    <!-- Фильтр по категориям -->
                    <div class="mb-4">
                        <h6 class="mb-3">Категории</h6>
                        <div class="form-check">
                            <input class="form-check-input category-filter" type="radio" name="category" id="category-all" value="" checked>
                            <label class="form-check-label" for="category-all">
                                Все категории
                            </label>
                        </div>
                        <?php foreach ($categories as $category): ?>
                            <div class="form-check">
                                <input class="form-check-input category-filter" type="radio" name="category" id="category-<?= $category['id'] ?>" value="<?= $category['id'] ?>">
                                <label class="form-check-label" for="category-<?= $category['id'] ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Фильтр по продавцам -->
                    <div class="mb-4">
                        <h6 class="mb-3">Продавцы</h6>
                        <div class="form-check">
                            <input class="form-check-input seller-filter" type="radio" name="seller" id="seller-all" value="" checked>
                            <label class="form-check-label" for="seller-all">
                                Все продавцы
                            </label>
                        </div>
                        <?php foreach ($sellers as $seller): ?>
                            <div class="form-check">
                                <input class="form-check-input seller-filter" type="radio" name="seller" id="seller-<?= $seller['id'] ?>" value="<?= $seller['id'] ?>">
                                <label class="form-check-label" for="seller-<?= $seller['id'] ?>">
                                    <?= htmlspecialchars($seller['name'] ?? $seller['full_name'] ?? '') ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Фильтр по доступности -->
                    <div class="mb-4">
                        <h6 class="mb-3">Доступность</h6>
                        <div class="form-check">
                            <input class="form-check-input availability-filter" type="radio" name="availability" id="availability-all" value="all" checked>
                            <label class="form-check-label" for="availability-all">
                                Все товары
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input availability-filter" type="radio" name="availability" id="availability-in-stock" value="in_stock">
                            <label class="form-check-label" for="availability-in-stock">
                                В наличии
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input availability-filter" type="radio" name="availability" id="availability-preorder" value="preorder">
                            <label class="form-check-label" for="availability-preorder">
                                Предзаказ
                            </label>
                        </div>
                    </div>
                    
                    <button id="reset-filters" class="btn btn-outline-secondary w-100">Сбросить фильтры</button>
                </div>
            </div>
        </div>
        
        <!-- Каталог товаров (правая колонка) -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Каталог товаров</h2>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span id="total-products">0</span> товаров
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Сортировка
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item sort-option" data-sort="newest" href="#">Сначала новые</a></li>
                            <li><a class="dropdown-item sort-option" data-sort="rating" href="#">По рейтингу</a></li>
                            <li><a class="dropdown-item sort-option" data-sort="price_asc" href="#">Цена: по возрастанию</a></li>
                            <li><a class="dropdown-item sort-option" data-sort="price_desc" href="#">Цена: по убыванию</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Контейнер для товаров -->
            <div id="products-container" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <!-- Товары будут загружены через JavaScript -->
                <div class="col-12 text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-2">Загрузка товаров...</p>
                </div>
            </div>
            
            <!-- Кнопка "Показать еще" -->
            <div id="load-more-container" class="text-center mt-4 mb-5 d-none">
                <button id="load-more" class="btn btn-primary">Показать еще</button>
            </div>
            
            <!-- Сообщение, если товары не найдены -->
            <div id="no-products-message" class="alert alert-info text-center d-none">
                <p class="mb-0">По вашему запросу товары не найдены. Попробуйте изменить параметры фильтрации.</p>
            </div>
        </div>
    </div>
</div>

<!-- Шаблон карточки товара -->
<template id="product-card-template">
    <div class="col">
        <div class="card h-100 product-card" data-product-id="">
            <div class="position-relative">
                <img src="" class="card-img-top product-image" alt="Изображение товара">
                <div class="position-absolute top-0 end-0 p-2">
                    <span class="badge bg-success in-stock-badge">В наличии</span>
                    <span class="badge bg-warning text-dark preorder-badge">Предзаказ</span>
                </div>
            </div>
            <div class="card-body d-flex flex-column">
                <h5 class="card-title product-name"></h5>
                <p class="card-text text-muted mb-1 product-seller"></p>
                <p class="card-text text-muted mb-3 product-category"></p>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="me-2">
                        <span class="product-rating">0.0</span>
                        <i class="bi bi-star-fill text-warning"></i>
                    </div>
                    <small class="text-muted product-rating-count">(0 оценок)</small>
                </div>
                
                <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="product-price mb-0"></h5>
                        <button class="btn btn-sm btn-outline-primary add-to-cart-btn">В корзину</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Модальное окно с подробной информацией о товаре -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel">Информация о товаре</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <img id="modal-product-image" src="" class="img-fluid rounded" alt="Изображение товара">
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <span id="modal-product-rating">0.0</span>
                                <i class="bi bi-star-fill text-warning"></i>
                                <small class="text-muted" id="modal-product-rating-count">(0 оценок)</small>
                            </div>
                            <div>
                                <span class="badge bg-success" id="modal-in-stock-badge">В наличии</span>
                                <span class="badge bg-warning text-dark" id="modal-preorder-badge">Предзаказ</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4 id="modal-product-name"></h4>
                        <p class="text-muted mb-1">
                            Продавец: <span id="modal-product-seller"></span>
                        </p>
                        <p class="text-muted mb-3">
                            Категория: <span id="modal-product-category"></span>
                        </p>
                        
                        <h5 class="mt-4">Описание</h5>
                        <p id="modal-product-description"></p>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <h4 id="modal-product-price" class="mb-0"></h4>
                            <div>
                                <button id="modal-add-to-cart" class="btn btn-primary me-2">Добавить в корзину</button>
                                <button id="modal-save-for-later" class="btn btn-outline-secondary">Сохранить на потом</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript для каталога -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Состояние фильтров и пагинации
        const state = {
            page: 1,
            limit: 25,
            category: '',
            seller: '',
            availability: 'all',
            sort: 'newest',
            products: [],
            hasMore: false
        };
        
        // Загрузка товаров при загрузке страницы
        loadProducts();
        
        // Обработчики событий для фильтров
        document.querySelectorAll('.category-filter').forEach(filter => {
            filter.addEventListener('change', function() {
                console.log('Выбрана категория:', this.value);
                state.category = this.value;
                resetAndReload();
            });
        });
        
        document.querySelectorAll('.seller-filter').forEach(filter => {
            filter.addEventListener('change', function() {
                state.seller = this.value;
                resetAndReload();
            });
        });
        
        document.querySelectorAll('.availability-filter').forEach(filter => {
            filter.addEventListener('change', function() {
                state.availability = this.value;
                resetAndReload();
            });
        });
        
        // Обработчик для сортировки
        document.querySelectorAll('.sort-option').forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                state.sort = this.dataset.sort;
                resetAndReload();
                document.getElementById('sortDropdown').textContent = this.textContent;
            });
        });
        
        // Обработчик для кнопки "Показать еще"
        document.getElementById('load-more').addEventListener('click', function() {
            state.page++;
            loadProducts(false);
        });
        
        // Обработчик для кнопки сброса фильтров
        document.getElementById('reset-filters').addEventListener('click', function() {
            document.getElementById('category-all').checked = true;
            document.getElementById('seller-all').checked = true;
            document.getElementById('availability-all').checked = true;
            document.getElementById('sortDropdown').textContent = 'Сортировка';
            
            state.category = '';
            state.seller = '';
            state.availability = 'all';
            state.sort = 'newest';
            
            resetAndReload();
        });
        
        // Делегирование событий для карточек товаров
        document.getElementById('products-container').addEventListener('click', function(e) {
            // Находим ближайшую карточку товара
            const card = e.target.closest('.product-card');
            if (!card) return;
            
            // Если нажата кнопка "В корзину", не открываем модальное окно
            if (e.target.classList.contains('add-to-cart-btn')) {
                e.stopPropagation();
                addToCart(card.dataset.productId);
                return;
            }
            
            // Открываем модальное окно с информацией о товаре
            openProductModal(card.dataset.productId);
        });
        
        // Обработчики для кнопок в модальном окне
        document.getElementById('modal-add-to-cart').addEventListener('click', function() {
            const productId = this.dataset.productId;
            addToCart(productId);
        });
        
        document.getElementById('modal-save-for-later').addEventListener('click', function() {
            const productId = this.dataset.productId;
            saveForLater(productId);
        });
        
        // Функция для загрузки товаров
        function loadProducts(reset = true) {
            if (reset) {
                state.page = 1;
                state.products = [];
                document.getElementById('products-container').innerHTML = `
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                        <p class="mt-2">Загрузка товаров...</p>
                    </div>
                `;
            } else {
                // Добавляем индикатор загрузки в конец списка
                document.getElementById('load-more-container').innerHTML = `
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-2">Загрузка товаров...</p>
                `;
            }
            
            // Скрываем кнопку "Показать еще" и сообщение об отсутствии товаров
            const loadMoreContainer = document.getElementById('load-more-container');
            const noProductsMessage = document.getElementById('no-products-message');
            
            if (loadMoreContainer) loadMoreContainer.classList.add('d-none');
            if (noProductsMessage) noProductsMessage.classList.add('d-none');
            
            // Формируем данные для запроса
            const requestData = {
                page: state.page,
                limit: state.limit,
                category_id: state.category,
                seller_id: state.seller,
                availability: state.availability,
                sort: state.sort
            };
            
            // Отладочная информация
            console.log('Отправляемые данные:', requestData);
            
            // Отправляем запрос на сервер
            fetch('/catalog/getProducts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                // Обновляем состояние
                if (reset) {
                    state.products = data.products;
                } else {
                    state.products = [...state.products, ...data.products];
                }
                
                state.hasMore = data.has_more;
                
                // Обновляем счетчик товаров
                const totalProductsElement = document.getElementById('total-products');
                if (totalProductsElement) totalProductsElement.textContent = data.total;
                
                // Отображаем товары
                renderProducts(reset);
                
                // Показываем или скрываем кнопку "Показать еще"
                const loadMoreContainer = document.getElementById('load-more-container');
                if (loadMoreContainer) {
                    loadMoreContainer.innerHTML = `
                        <button id="load-more" class="btn btn-primary">Показать еще</button>
                    `;
                    
                    if (state.hasMore) {
                        loadMoreContainer.classList.remove('d-none');
                        const loadMoreButton = document.getElementById('load-more');
                        if (loadMoreButton) {
                            loadMoreButton.addEventListener('click', function() {
                                state.page++;
                                loadProducts(false);
                            });
                        }
                    } else {
                        loadMoreContainer.classList.add('d-none');
                    }
                }
                
                // Если товары не найдены, показываем сообщение
                const noProductsMessage = document.getElementById('no-products-message');
                if (noProductsMessage) {
                    if (data.total === 0) {
                        noProductsMessage.classList.remove('d-none');
                    } else {
                        noProductsMessage.classList.add('d-none');
                    }
                }
            })
            .catch(error => {
                console.error('Ошибка при загрузке товаров:', error);
                document.getElementById('products-container').innerHTML = `
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-danger" role="alert">
                            Произошла ошибка при загрузке товаров. Пожалуйста, попробуйте обновить страницу.
                        </div>
                    </div>
                `;
            });
        }
        
        // Функция для отображения товаров
        function renderProducts(reset = true) {
            const container = document.getElementById('products-container');
            
            if (reset) {
                container.innerHTML = '';
            } else {
                // Удаляем индикатор загрузки
                const loadingIndicator = container.querySelector('.col-12.text-center');
                if (loadingIndicator) {
                    loadingIndicator.remove();
                }
            }
            
            if (state.products.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <p>Товары не найдены.</p>
                    </div>
                `;
                return;
            }
            
            // Получаем шаблон карточки товара
            const template = document.getElementById('product-card-template');
            
            // Создаем и добавляем карточки товаров
            state.products.forEach(product => {
                // Клонируем шаблон
                const card = document.importNode(template.content, true);
                
                // Заполняем данными
                card.querySelector('.product-card').dataset.productId = product.id;
                card.querySelector('.product-image').src = product.main_image;
                card.querySelector('.product-image').alt = product.product_name;
                card.querySelector('.product-name').textContent = product.product_name;
                card.querySelector('.product-seller').textContent = `Продавец: ${product.seller_name || 'Не указан'}`;
                card.querySelector('.product-category').textContent = `Категория: ${product.category_name || 'Не указана'}`;
                card.querySelector('.product-rating').textContent = product.avg_rating;
                
                // Форматируем количество оценок
                const ratingCount = parseInt(product.rating_count);
                const ratingText = formatRatingCount(ratingCount);
                card.querySelector('.product-rating-count').textContent = ratingText;
                
                // Форматируем цену
                card.querySelector('.product-price').textContent = formatPrice(product.price);
                
                // Показываем или скрываем бейджи доступности
                const inStockBadge = card.querySelector('.in-stock-badge');
                const preorderBadge = card.querySelector('.preorder-badge');
                
                if (product.is_active == 1) {
                    inStockBadge.classList.remove('d-none');
                } else {
                    inStockBadge.classList.add('d-none');
                }
                
                if (product.available_for_preorder == 1 && product.is_active != 1) {
                    preorderBadge.classList.remove('d-none');
                } else {
                    preorderBadge.classList.add('d-none');
                }
                
                // Добавляем карточку в контейнер
                container.appendChild(card);
            });
        }
        
        // Функция для открытия модального окна с информацией о товаре
        function openProductModal(productId) {
            // Находим товар по ID
            const product = state.products.find(p => p.id === productId);
            if (!product) return;
            
            // Заполняем модальное окно данными о товаре
            document.getElementById('modal-product-image').src = product.main_image;
            document.getElementById('modal-product-image').alt = product.product_name;
            document.getElementById('modal-product-name').textContent = product.product_name;
            document.getElementById('modal-product-seller').textContent = product.seller_name || 'Не указан';
            document.getElementById('modal-product-category').textContent = product.category_name || 'Не указана';
            document.getElementById('modal-product-description').textContent = product.description;
            document.getElementById('modal-product-rating').textContent = product.avg_rating;
            document.getElementById('modal-product-rating-count').textContent = formatRatingCount(parseInt(product.rating_count));
            document.getElementById('modal-product-price').textContent = formatPrice(product.price);
            
            // Устанавливаем ID товара для кнопок
            document.getElementById('modal-add-to-cart').dataset.productId = product.id;
            document.getElementById('modal-save-for-later').dataset.productId = product.id;
            
            // Показываем или скрываем бейджи доступности
            const inStockBadge = document.getElementById('modal-in-stock-badge');
            const preorderBadge = document.getElementById('modal-preorder-badge');
            
            if (product.is_active == 1) {
                inStockBadge.classList.remove('d-none');
            } else {
                inStockBadge.classList.add('d-none');
            }
            
            if (product.available_for_preorder == 1 && product.is_active != 1) {
                preorderBadge.classList.remove('d-none');
            } else {
                preorderBadge.classList.add('d-none');
            }
            
            // Открываем модальное окно
            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        }
        
        // Функция для добавления товара в корзину
        function addToCart(productId) {
            // TODO: Реализовать добавление товара в корзину
            console.log('Добавление товара в корзину:', productId);
            
            // Показываем уведомление об успешном добавлении
            showNotification('Товар добавлен в корзину');
        }
        
        // Функция для сохранения товара на потом
        function saveForLater(productId) {
            // TODO: Реализовать сохранение товара на потом
            console.log('Сохранение товара на потом:', productId);
            
            // Показываем уведомление об успешном сохранении
            showNotification('Товар сохранен на потом');
        }
        
        // Функция для отображения уведомления
        function showNotification(message) {
            // Создаем элемент уведомления
            const notification = document.createElement('div');
            notification.className = 'toast align-items-center text-white bg-success';
            notification.setAttribute('role', 'alert');
            notification.setAttribute('aria-live', 'assertive');
            notification.setAttribute('aria-atomic', 'true');
            
            notification.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Закрыть"></button>
                </div>
            `;
            
            // Добавляем уведомление на страницу
            const container = document.createElement('div');
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.appendChild(notification);
            document.body.appendChild(container);
            
            // Показываем уведомление
            const toast = new bootstrap.Toast(notification, { delay: 3000 });
            toast.show();
            
            // Удаляем уведомление после скрытия
            notification.addEventListener('hidden.bs.toast', function() {
                container.remove();
            });
        }
        
        // Функция для форматирования цены
        function formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(price);
        }
        
        // Функция для форматирования количества оценок
        function formatRatingCount(count) {
            if (count === 0) {
                return '(нет оценок)';
            } else if (count === 1) {
                return '(1 оценка)';
            } else if (count >= 2 && count <= 4) {
                return `(${count} оценки)`;
            } else {
                return `(${count} оценок)`;
            }
        }
        
        // Функция для сброса и перезагрузки товаров
        function resetAndReload() {
            state.page = 1;
            loadProducts();
        }
    });
</script>
