<?php
/**
 * @var App\Models\User $user
 * @var array $sellerProfile
 * @var array $categories
 */
?>

<div class="container-fluid">
    <div class="row">
        <!-- Боковое меню -->
        <?php include_once __DIR__ . '/../dashboard/sidebar.php'; ?>
        
        <!-- Основной контент -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Добавление нового продукта</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/seller/products" class="btn btn-sm btn-outline-secondary">Вернуться к списку</a>
                </div>
            </div>
            
            <?php if (App\Core\Application::$app->session->getFlash('error')): ?>
                <div class="alert alert-danger">
                    <?= App\Core\Application::$app->session->getFlash('error') ?>
                </div>
            <?php endif; ?>
            
            <?php if (App\Core\Application::$app->session->getFlash('success')): ?>
                <div class="alert alert-success">
                    <?= App\Core\Application::$app->session->getFlash('success') ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form id="addProductForm" action="/seller/products/add" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="productName" class="form-label">Название продукта</label>
                            <input type="text" class="form-control" id="productName" name="product_name" required>
                            <div class="form-text">Введите название вашего продукта (до 100 символов)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="productDescription" class="form-label">Описание</label>
                            <textarea class="form-control" id="productDescription" name="description" rows="5" required></textarea>
                            <div class="form-text">Подробно опишите ваш продукт, его особенности и преимущества</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="productPrice" class="form-label">Цена (₽)</label>
                                <input type="number" class="form-control" id="productPrice" name="price" step="0.01" min="0" required>
                                <div class="form-text">Укажите цену в рублях</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="productCategory" class="form-label">Категория</label>
                                <select class="form-select" id="productCategory" name="category_id">
                                    <option value="">Выберите категорию</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Выберите категорию, к которой относится ваш продукт</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="productQuantity" class="form-label">Количество</label>
                                <input type="number" class="form-control" id="productQuantity" name="quantity" min="1" value="1" required>
                                <div class="form-text">Укажите доступное количество товара</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="productWeight" class="form-label">Вес (г)</label>
                                <input type="number" class="form-control" id="productWeight" name="weight" min="0" value="0">
                                <div class="form-text">Укажите вес товара в граммах</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="productImage" class="form-label">Изображение продукта</label>
                            <input type="file" class="form-control" id="productImage" name="image" accept="image/avif,image/webp">
                            <div class="form-text">Загрузите изображение продукта (только AVIF или WebP, макс. размер 100KB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="productStatus" class="form-label">Статус продукта</label>
                            <select class="form-select" id="productStatus" name="product_status">
                                <option value="active">Активен (доступен для покупки)</option>
                                <option value="draft">Черновик (не отображается в каталоге)</option>
                                <option value="sold_out">Распродано (временно недоступен)</option>
                            </select>
                            <div class="form-text">Выберите текущий статус продукта</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="availableForPreorder" name="available_for_preorder" value="1">
                            <label class="form-check-label" for="availableForPreorder">Доступен для предзаказа</label>
                            <div class="form-text">Отметьте, если продукт доступен для предзаказа</div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="/seller/products" class="btn btn-secondary me-md-2">Отмена</a>
                            <button type="submit" class="btn btn-primary">Сохранить продукт</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Добавляем JavaScript для валидации формы на стороне клиента
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addProductForm');
    
    form.addEventListener('submit', function(event) {
        let isValid = true;
        const name = document.getElementById('productName').value.trim();
        const description = document.getElementById('productDescription').value.trim();
        const price = document.getElementById('productPrice').value;
        const image = document.getElementById('productImage').files[0];
        const quantity = document.getElementById('productQuantity').value;
        const weight = document.getElementById('productWeight').value;
        
        // Проверка названия
        if (name.length === 0 || name.length > 100) {
            isValid = false;
            alert('Название продукта должно содержать от 1 до 100 символов');
        }
        
        // Проверка описания
        if (description.length === 0) {
            isValid = false;
            alert('Пожалуйста, добавьте описание продукта');
        }
        
        // Проверка цены
        if (price <= 0) {
            isValid = false;
            alert('Цена должна быть больше нуля');
        }
        
        // Проверка количества
        if (quantity < 1) {
            isValid = false;
            alert('Количество должно быть больше нуля');
        }
        
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
