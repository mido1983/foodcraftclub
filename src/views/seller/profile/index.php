<?php
/** 
 * @var \App\Models\User $user
 * @var array $sellerProfile
 * @var array $paymentMethods
 * @var array $sellerPaymentOptions
 */
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once __DIR__ . '/../dashboard/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Профиль продавца</h1>
            </div>

            <?php if (\App\Core\Application::$app->session->getFlash('success')): ?>
                <div class="alert alert-success">
                    <?= \App\Core\Application::$app->session->getFlash('success') ?>
                </div>
            <?php endif; ?>

            <?php if (\App\Core\Application::$app->session->getFlash('error')): ?>
                <div class="alert alert-danger">
                    <?= \App\Core\Application::$app->session->getFlash('error') ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Информация о магазине</h5>
                        </div>
                        <div class="card-body">
                            <form action="/seller/profile" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Название магазина *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($sellerProfile['name'] ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Описание магазина *</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($sellerProfile['description'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Контактный email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($sellerProfile['email'] ?? '') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Контактный телефон</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($sellerProfile['phone'] ?? '') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="avatar" class="form-label">Аватар магазина</label>
                                    <?php if (!empty($sellerProfile['avatar_url'])): ?>
                                        <div class="mb-2">
                                            <img src="<?= htmlspecialchars($sellerProfile['avatar_url']) ?>" alt="Avatar" class="img-thumbnail" style="max-width: 150px;">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" id="avatar" name="avatar" accept="image/avif,image/webp">
                                    <div class="form-text">Допустимые форматы: AVIF, WebP. Максимальный размер: 100KB.</div>
                                </div>
                                
                                <!-- Способы оплаты -->
                                <div class="mb-3">
                                    <label class="form-label">Способы оплаты</label>
                                    <?php foreach ($paymentMethods as $method): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="payment_methods[]" 
                                                   value="<?= $method['id'] ?>" 
                                                   id="payment_method_<?= $method['id'] ?>"
                                                   <?= in_array($method['id'], $sellerPaymentOptions) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="payment_method_<?= $method['id'] ?>">
                                                <?= htmlspecialchars($method['method_name']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Информация о пользователе</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Имя:</strong> <?= htmlspecialchars($user->full_name) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($user->email) ?></p>
                            <p><strong>Дата регистрации:</strong> <?= date('d.m.Y', strtotime($user->created_at)) ?></p>
                            <p><strong>Статус:</strong> <?= $user->status ? 'Активен' : 'Не активен' ?></p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Магазин</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>ID магазина:</strong> <?= htmlspecialchars($sellerProfile['id'] ?? 'Не указан') ?></p>
                            <p><strong>Дата создания:</strong> <?= isset($sellerProfile['created_at']) ? date('d.m.Y', strtotime($sellerProfile['created_at'])) : 'Не указана' ?></p>
                            <p><strong>Последнее обновление:</strong> <?= isset($sellerProfile['updated_at']) ? date('d.m.Y H:i', strtotime($sellerProfile['updated_at'])) : 'Не указано' ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
