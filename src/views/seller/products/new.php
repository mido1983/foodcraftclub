<?php
/**
 * @var App\Models\User $user
 * @var array $sellerProfile
 * @var array $categories
 * @var array $ingredients
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
                                <label for="productPrice" class="form-label">Цена (₪)</label>
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
                        
                        <div class="mb-3">
                            <label class="form-label">Ингредиенты</label>
                            <div class="alert alert-info">Выберите минимум 2 ингредиента для вашего продукта</div>
                            <div class="row">
                                <?php 
                                $categories = [];
                                foreach ($ingredients as $ingredient) {
                                    if (!isset($categories[$ingredient['category']])) {
                                        $categories[$ingredient['category']] = [];
                                    }
                                    $categories[$ingredient['category']][] = $ingredient;
                                }
                                
                                foreach ($categories as $category => $categoryIngredients): ?>
                                    <div class="col-md-4 mb-3">
                                        <h5><?= htmlspecialchars($category) ?></h5>
                                        <?php foreach ($categoryIngredients as $ingredient): ?>
                                            <div class="form-check">
                                                <input class="form-check-input ingredient-checkbox" type="checkbox" 
                                                       name="ingredients[]" value="<?= $ingredient['id'] ?>" 
                                                       id="ingredient-<?= $ingredient['id'] ?>">
                                                <label class="form-check-label" for="ingredient-<?= $ingredient['id'] ?>">
                                                    <?= htmlspecialchars($ingredient['name']) ?>
                                                    <?php if ($ingredient['allergen']): ?>
                                                        <span class="badge bg-warning text-dark">Аллерген</span>
                                                    <?php endif; ?>
                                                    <?php if ($ingredient['kosher']): ?>
                                                        <span class="badge bg-info">Кошерный</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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
// Добавляем JavaScript для валидации формы на стороне клиента и отправки через AJAX
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addProductForm');
    const ingredientCheckboxes = document.querySelectorAll('.ingredient-checkbox');
    
    // Восстановление данных формы из localStorage при загрузке страницы
    restoreFormData();
    
    // Сохранение данных формы при изменении полей
    const formInputs = form.querySelectorAll('input, textarea, select');
    formInputs.forEach(input => {
        input.addEventListener('change', function() {
            saveFormData();
        });
    });
    
    form.addEventListener('submit', function(event) {
        // Предотвращаем стандартную отправку формы
        event.preventDefault();
        
        // Проверка выбора минимум 2 ингредиентов
        let selectedIngredients = 0;
        ingredientCheckboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                selectedIngredients++;
            }
        });
        
        if (selectedIngredients < 1) {
            alert('Необходимо выбрать минимум 1 ингредиент для продукта');
            return false;
        }
        
        // Отправка формы через AJAX
        submitFormAjax();
    });
    
    // Функция для сохранения данных формы в localStorage
    function saveFormData() {
        const formData = {};
        
        // Сохраняем значения текстовых полей, select и чекбоксов
        form.querySelectorAll('input[type="text"], input[type="number"], textarea, select').forEach(input => {
            formData[input.name] = input.value;
        });
        
        // Сохраняем состояние чекбоксов
        form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            if (checkbox.name === 'ingredients[]') {
                if (!formData['ingredients']) {
                    formData['ingredients'] = [];
                }
                if (checkbox.checked) {
                    formData['ingredients'].push(checkbox.value);
                }
            } else {
                formData[checkbox.name] = checkbox.checked;
            }
        });
        
        localStorage.setItem('addProductFormData', JSON.stringify(formData));
    }
    
    // Функция для восстановления данных формы из localStorage
    function restoreFormData() {
        const savedData = localStorage.getItem('addProductFormData');
        if (!savedData) return;
        
        const formData = JSON.parse(savedData);
        
        // Восстанавливаем значения текстовых полей, select и чекбоксов
        Object.keys(formData).forEach(key => {
            if (key === 'ingredients') return; // Ингредиенты обрабатываем отдельно
            
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = formData[key];
                } else {
                    input.value = formData[key];
                }
            }
        });
        
        // Восстанавливаем выбранные ингредиенты
        if (formData['ingredients'] && Array.isArray(formData['ingredients'])) {
            formData['ingredients'].forEach(ingredientId => {
                const checkbox = form.querySelector(`input[name="ingredients[]"][value="${ingredientId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }
    }
    
    // Функция для отправки формы через AJAX
    function submitFormAjax() {
        // Показываем индикатор загрузки
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохранение...';
        
        // Создаем объект FormData для отправки данных формы, включая файлы
        const formData = new FormData(form);
        
        // Отправляем запрос на сервер
        fetch('/seller/products/add', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Восстанавливаем кнопку
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            
            if (data.success) {
                // Очищаем сохраненные данные формы
                localStorage.removeItem('addProductFormData');
                
                // Показываем сообщение об успехе
                const alertContainer = document.createElement('div');
                alertContainer.className = 'alert alert-success';
                alertContainer.textContent = data.message || 'Продукт успешно добавлен';
                
                // Вставляем сообщение перед формой
                const cardBody = form.closest('.card-body');
                cardBody.insertBefore(alertContainer, form);
                
                // Перенаправляем на страницу продуктов через 2 секунды
                setTimeout(() => {
                    window.location.href = '/seller/products';
                }, 2000);
            } else {
                // Показываем сообщение об ошибке
                const alertContainer = document.createElement('div');
                alertContainer.className = 'alert alert-danger';
                alertContainer.textContent = data.message || 'Произошла ошибка при добавлении продукта';
                
                // Если есть конкретные ошибки валидации, показываем их
                if (data.errors) {
                    const errorList = document.createElement('ul');
                    Object.keys(data.errors).forEach(key => {
                        const errorItem = document.createElement('li');
                        errorItem.textContent = data.errors[key];
                        errorList.appendChild(errorItem);
                    });
                    alertContainer.appendChild(errorList);
                }
                
                // Вставляем сообщение перед формой
                const cardBody = form.closest('.card-body');
                cardBody.insertBefore(alertContainer, form);
                
                // Удаляем сообщение через 5 секунд
                setTimeout(() => {
                    alertContainer.remove();
                }, 5000);
            }
        })
        .catch(error => {
            console.error('Ошибка при отправке формы:', error);
            
            // Восстанавливаем кнопку
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            
            // Показываем сообщение об ошибке
            const alertContainer = document.createElement('div');
            alertContainer.className = 'alert alert-danger';
            alertContainer.textContent = 'Произошла ошибка при отправке формы. Пожалуйста, попробуйте еще раз.';
            
            // Вставляем сообщение перед формой
            const cardBody = form.closest('.card-body');
            cardBody.insertBefore(alertContainer, form);
            
            // Удаляем сообщение через 5 секунд
            setTimeout(() => {
                alertContainer.remove();
            }, 5000);
        });
    }
});
</script>
