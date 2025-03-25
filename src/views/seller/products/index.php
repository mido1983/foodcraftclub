<?php
/**
 * @var App\Models\User $user
 * @var array $sellerProfile
 * @var array $products
 * @var array $categories
 * @var array $notifications
 * @var int $unreadNotifications
 */
?>

<div class="container-fluid">
    <div class="row">
        <!-- Боковое меню -->
        <?php include_once __DIR__ . '/../dashboard/sidebar.php'; ?>
        
        <!-- Основной контент -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Мои продукты</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/seller/products/fix-images" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-image"></i> Исправить изображения
                    </a>
                    <a href="/seller/products/new" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> Добавить продукт
                    </a>
                </div>
            </div>
            
            <?php if (empty($products)): ?>
                <div class="alert alert-info">
                    <p>У вас пока нет продуктов. Добавьте свой первый продукт, нажав кнопку "Добавить продукт".</p>
                </div>
            <?php else: ?>
                <!-- Список продуктов -->
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Изображение</th>
                                <th>Название</th>
                                <th>Категория</th>
                                <th>Цена</th>
                                <th>Количество</th>
                                <th>Вес (г)</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= $product['id'] ?></td>
                                    <td>
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="<?= $product['image_url'] ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" width="50">
                                        <?php else: ?>
                                            <img src="/assets/images/default-products.svg" alt="<?= htmlspecialchars($product['product_name']) ?>" width="50">
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                                    <td><?= htmlspecialchars($product['category_name'] ?? 'Без категории') ?></td>
                                    <td><?= number_format($product['price'], 2) ?> ₪</td>
                                    <td><?= isset($product['quantity']) ? $product['quantity'] : '1' ?></td>
                                    <td><?= isset($product['weight']) ? $product['weight'] : '0' ?></td>
                                    <td>
                                        <?php if ($product['is_active']): ?>
                                            <span class="badge bg-success">Активен</span>
                                        <?php elseif (isset($product['available_for_preorder']) && $product['available_for_preorder']): ?>
                                            <span class="badge bg-warning text-dark">Предзаказ</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Неактивен</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary edit-product-btn" 
                                                    data-id="<?= $product['id'] ?>"
                                                    data-name="<?= htmlspecialchars($product['product_name']) ?>"
                                                    data-description="<?= htmlspecialchars($product['description']) ?>"
                                                    data-price="<?= $product['price'] ?>"
                                                    data-category="<?= $product['category_id'] ?>"
                                                    data-active="<?= $product['is_active'] ?>"
                                                    data-preorder="<?= $product['available_for_preorder'] ?? '0' ?>"
                                                    data-quantity="<?= isset($product['quantity']) ? $product['quantity'] : '1' ?>"
                                                    data-weight="<?= isset($product['weight']) ? $product['weight'] : '0' ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editProductModal">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger delete-product-btn" 
                                                    data-id="<?= $product['id'] ?>"
                                                    data-name="<?= htmlspecialchars($product['product_name']) ?>"
                                                    data-bs-toggle="modal" data-bs-target="#deleteProductModal">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Модальное окно редактирования продукта -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProductModalLabel">Редактировать продукт</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editProductForm" action="/seller/products/edit" method="post" enctype="multipart/form-data">
                    <input type="hidden" id="editProductId" name="id">
                    <div class="mb-3">
                        <label for="editProductName" class="form-label">Название продукта</label>
                        <input type="text" class="form-control" id="editProductName" name="product_name">
                    </div>
                    <div class="mb-3">
                        <label for="editProductDescription" class="form-label">Описание</label>
                        <textarea class="form-control" id="editProductDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editProductPrice" class="form-label">Цена (₪)</label>
                            <input type="number" class="form-control" id="editProductPrice" name="price" step="0.01" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editProductCategory" class="form-label">Категория</label>
                            <select class="form-select" id="editProductCategory" name="category_id">
                                <option value="">Выберите категорию</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editProductImage" class="form-label">Изображение продукта</label>
                        <input type="file" class="form-control" id="editProductImage" name="image" accept="image/avif,image/webp">
                        <div class="form-text">Загрузите новое изображение (типо AVIF или WebP, макс. размер 100KB) или оставьте пустым, если не хотите менять изображение.</div>
                    </div>
                    <div class="mb-3">
                        <label for="editProductQuantity" class="form-label">Количество</label>
                        <input type="number" class="form-control" id="editProductQuantity" name="quantity" min="1">
                    </div>
                    <div class="mb-3">
                        <label for="editProductWeight" class="form-label">Вес (г)</label>
                        <input type="number" class="form-control" id="editProductWeight" name="weight" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="editProductStatus" class="form-label">Статус продукта</label>
                        <select class="form-select" id="editProductStatus" name="product_status">
                            <option value="active">Активен (доступен для покупки)</option>
                            <option value="inactive">Не доступен (отсутствует)</option>
                            <option value="preorder">Доступен по предзаказу</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" form="editProductForm" class="btn btn-primary">Сохранить изменения</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно удаления продукта -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProductModalLabel">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить продукт <strong id="deleteProductName"></strong>?</p>
                <p class="text-danger">Это действие невозможно отменить.</p>
                <form id="deleteProductForm" action="/seller/products/delete/" method="post">
                    <input type="hidden" id="deleteProductId" name="id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" form="deleteProductForm" class="btn btn-danger">Удалить</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Инициализация кнопок редактирования
        document.querySelectorAll('.edit-product-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                document.getElementById('editProductId').value = this.dataset.id;
                document.getElementById('editProductName').value = this.dataset.name;
                document.getElementById('editProductDescription').value = this.dataset.description;
                document.getElementById('editProductPrice').value = this.dataset.price;
                document.getElementById('editProductCategory').value = this.dataset.category;
                
                // Устанавливаем статус продукта
                const isActive = this.dataset.active === '1';
                const availableForPreorder = this.hasAttribute('data-preorder') ? this.dataset.preorder === '1' : false;
                
                let status = 'inactive';
                if (isActive) {
                    status = 'active';
                } else if (availableForPreorder) {
                    status = 'preorder';
                }
                
                document.getElementById('editProductStatus').value = status;
                
                // Устанавливаем количество и вес
                document.getElementById('editProductQuantity').value = this.dataset.quantity;
                document.getElementById('editProductWeight').value = this.dataset.weight;
            });
        });
        
        // Обработчик для кнопок удаления
        document.querySelectorAll('.delete-product-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                
                // Обновляем текст в модальном окне
                document.getElementById('deleteProductName').textContent = name;
                
                // Обновляем URL формы
                const form = document.getElementById('deleteProductForm');
                form.action = `/seller/products/delete/${id}`;
                
                // Устанавливаем значение скрытого поля
                document.getElementById('deleteProductId').value = id;
            });
        });

        // Обработчик отправки формы
        document.getElementById('deleteProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const id = this.querySelector('input[name="id"]').value;
            
            // Отправляем AJAX-запрос
            fetch(`/seller/products/delete/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Обновляем список продуктов
                    window.location.reload();
                } else {
                    alert(data.message || 'Ошибка при удалении продукта');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при удалении продукта');
            });
        });
        
        // Валидация формы редактирования продукта
        document.getElementById('editProductForm').addEventListener('submit', function(event) {
            let isValid = true;
            const image = document.getElementById('editProductImage').files[0];
            
            // Проверка файла изображения, если он был выбран
            if (image) {
                const allowedTypes = ['image/avif', 'image/webp'];
                const maxSize = 100 * 1024; // 100KB
                
                if (!allowedTypes.includes(image.type)) {
                    isValid = false;
                    alert('Пожалуйста, загрузите изображение в формате AVIF или WebP');
                }
                
                if (image.size > maxSize) {
                    isValid = false;
                    alert('Размер изображения не должен превышать 100KB');
                }
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    });
</script>
