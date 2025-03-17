<?php
/** @var $this \App\Core\View */
/** @var $user \App\Models\User */
/** @var $userRoles array */
/** @var $allRoles array */

use App\Core\Application;
?>

<div class="container admin-dashboard py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Edit User</h1>
        <a href="/admin/users" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Users
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="/admin/users/edit/<?= $user->id ?>" method="post">
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user->full_name) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user->email) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password (leave blank to keep current)</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                    <div class="form-text">Minimum 6 characters</div>
                </div>

                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirm new password">
                </div>

                <div class="mb-3">
                    <label class="form-label">User Role</label>
                    <?php 
                    // Debug user roles
                    error_log('User roles in view: ' . print_r($userRoles, true));
                    error_log('All roles in view: ' . print_r($allRoles, true));
                    ?>
                    
                    <!-- Отладочная информация -->
                    <div class="alert alert-info mb-2">
                        <strong>Отладка:</strong> Текущие роли: 
                        <?php foreach ($userRoles as $role): ?>
                            <span class="badge bg-primary"><?= htmlspecialchars($role['name']) ?> (ID: <?= $role['id'] ?>)</span>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Радио-кнопки вместо чекбоксов -->
                    <?php foreach ($allRoles as $role): 
                        $roleId = (int)$role['id'];
                        
                        // Check if user has this role
                        $isChecked = false;
                        foreach ($userRoles as $userRole) {
                            if ((int)$userRole['id'] === $roleId) {
                                $isChecked = true;
                                break;
                            }
                        }
                    ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" 
                                   value="<?= $roleId ?>" id="role_<?= $roleId ?>" 
                                   <?= $isChecked ? 'checked' : '' ?>>
                            <label class="form-check-label" for="role_<?= $roleId ?>">
                                <?= htmlspecialchars($role['name']) ?> (ID: <?= $roleId ?>)
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <div class="form-text text-muted">Выберите основную роль пользователя.</div>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    
                    <!-- Отладочная информация о статусе пользователя -->
                    <div class="alert alert-info mb-2">
                        <strong>Текущий статус:</strong> 
                        <span class="badge <?= $user->status === 'active' ? 'bg-success' : ($user->status === 'pending' ? 'bg-warning' : 'bg-danger') ?>">
                            <?= ucfirst(htmlspecialchars($user->status)) ?>
                        </span>
                    </div>
                    
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= $user->status === 'active' ? 'selected' : '' ?>>Активный</option>
                        <option value="pending" <?= $user->status === 'pending' ? 'selected' : '' ?>>Ожидает</option>
                        <option value="suspended" <?= $user->status === 'suspended' ? 'selected' : '' ?>>Заблокирован</option>
                    </select>
                    <div class="form-text text-muted">Выберите статус пользователя. Только пользователи со статусом "Активный" могут войти в систему.</div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                    
                    <a href="/admin/users" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Account Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>User ID:</strong> <?= $user->id ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge <?= $user->status === 'active' ? 'bg-success' : ($user->status === 'pending' ? 'bg-warning' : 'bg-danger') ?>">
                            <?= ucfirst($user->status) ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Roles:</strong> 
                        <?php foreach ($user->getRoles() as $role): ?>
                            <span class="badge bg-primary"><?= htmlspecialchars($role['name']) ?></span>
                        <?php endforeach; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
