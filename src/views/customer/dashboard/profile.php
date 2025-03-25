<?php
/** @var $this \App\Core\View */
/** @var $user \App\Models\User */
/** @var $addresses array */

$this->title = 'Профиль - Food Craft Club';
$this->route = 'customer/dashboard/profile';
?>

<div class="container py-4">
    <div class="row">
        <!-- Боковая панель -->
        <div class="col-lg-3 mb-4">
            <?php include_once __DIR__ . '/sidebar.php'; ?>
        </div>
        
        <!-- Основной контент -->
        <div class="col-lg-9">
            <div class="row">
                <!-- Секция с личной информацией -->
                <div class="col-12 mb-4">
                    <div class="bg-white shadow-sm rounded p-4">
                        <h2 class="h4 mb-4">Личная информация</h2>
                        
                        <form id="profileForm" method="post" action="/customer/profile/update" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= $this->csrf_token ?>">
                            
                            <div class="row mb-4">
                                <!-- Аватар пользователя -->
                                <div class="col-md-3 text-center mb-4 mb-md-0">
                                    <div class="position-relative d-inline-block">
                                        <div class="avatar-container rounded-circle overflow-hidden mb-3" style="width: 150px; height: 150px; background-color: #f8f9fa;">
                                            <?php if (isset($user->avatar) && $user->avatar): ?>
                                                <img src="/uploads/avatars/<?= $this->escape($user->avatar) ?>" alt="Аватар" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                                    <i class="bi bi-person-circle text-secondary" style="font-size: 4rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <label for="avatarUpload" class="btn btn-sm btn-primary position-absolute bottom-0 start-50 translate-middle-x">
                                            <i class="bi bi-camera me-1"></i> Изменить
                                        </label>
                                        <input type="file" id="avatarUpload" name="avatar" class="d-none" accept="image/jpeg,image/png,image/gif">
                                    </div>
                                    <small class="text-muted d-block mt-2">JPG, PNG или GIF. Макс. 2MB</small>
                                </div>
                                
                                <!-- Форма с личными данными -->
                                <div class="col-md-9">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Имя</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?= $this->escape($user->full_name ?? '') ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?= $this->escape($user->email) ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="user_phone" class="form-label">Телефон</label>
                                            <input type="tel" class="form-control" id="user_phone" name="phone" value="<?= $this->escape($user->phone ?? '') ?>">
                                        </div>
                                        
                                        <div class="col-12 mt-4">
                                            <h5 class="h6 mb-3">Настройки уведомлений</h5>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="notification_order" name="notification_order" <?= ($user->notification_order ?? true) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="notification_order">
                                                    Уведомления о заказах (статус, доставка)
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="notification_promo" name="notification_promo" <?= ($user->notification_promo ?? false) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="notification_promo">
                                                    Акции и специальные предложения
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="notification_system" name="notification_system" <?= ($user->notification_system ?? true) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="notification_system">
                                                    Системные уведомления
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Кнопка сохранения данных -->
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                                    <i class="bi bi-check-lg me-1"></i> Сохранить изменения
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Секция для изменения пароля -->
                <div class="col-md-6 mb-4">
                    <div class="bg-white shadow-sm rounded p-4 h-100">
                        <h2 class="h4 mb-4">Изменение пароля</h2>
                        
                        <form id="passwordForm" method="post" action="/customer/profile/update">
                            <input type="hidden" name="csrf_token" value="<?= $this->csrf_token ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Новый пароль</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password_confirm" class="form-label">Подтверждение пароля</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirm">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary" id="savePasswordBtn">
                                    <i class="bi bi-lock me-1"></i> Обновить пароль
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Секция с информацией о безопасности -->
                <div class="col-md-6 mb-4">
                    <div class="bg-white shadow-sm rounded p-4 h-100">
                        <h2 class="h4 mb-4">Безопасность аккаунта</h2>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3 text-success">
                                    <i class="bi bi-shield-check fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="h6 mb-1">Двухфакторная аутентификация</h5>
                                    <p class="text-muted small mb-0">Повысьте безопасность вашего аккаунта</p>
                                </div>
                                <div class="form-check form-switch ms-auto">
                                    <input class="form-check-input" type="checkbox" id="twoFactorAuth" disabled>
                                </div>
                            </div>
                            <div class="alert alert-light small">
                                <i class="bi bi-info-circle me-1"></i> Функция будет доступна в ближайшее время
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3 text-primary">
                                    <i class="bi bi-clock-history fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="h6 mb-1">Последний вход в аккаунт</h5>
                                    <p class="text-muted small mb-0">25.03.2025, 18:45</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Блок с адресами -->
                <?php include_once __DIR__ . '/profile_addresses.php'; ?>
            </div>
        </div>
    </div>
</div>

<!-- Подключение JavaScript-файла для страницы профиля -->
<script src="/js/customer/profile.js"></script>
