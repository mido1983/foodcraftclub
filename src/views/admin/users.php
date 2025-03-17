<?php
/** @var $this \App\Core\View */
/** @var $users array */

use App\Core\Application;
?>

<div class="container admin-dashboard py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Manage Users</h1>
        <a href="/admin/users/create" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Create User
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <?php 
                                        $roles = explode(',', $user['roles'] ?? '');
                                        foreach ($roles as $role): 
                                            $badgeClass = 'bg-secondary';
                                            if ($role === 'admin') $badgeClass = 'bg-danger';
                                            if ($role === 'seller') $badgeClass = 'bg-success';
                                            if ($role === 'client') $badgeClass = 'bg-info';
                                        ?>
                                            <span class="badge <?= $badgeClass ?> me-1"><?= ucfirst($role) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif ($user['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif ($user['status'] === 'suspended'): ?>
                                            <span class="badge bg-danger">Suspended</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= ucfirst($user['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/admin/users/edit/<?= $user['id'] ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ((int)$user['id'] !== Application::$app->session->getUserId()): ?>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteUserModal"
                                                        data-user-id="<?= $user['id'] ?>"
                                                        data-user-name="<?= htmlspecialchars($user['full_name']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete user <span id="userName"></span>?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteUserForm" action="/admin/users/delete" method="post">
                    <input type="hidden" id="deleteUserId" name="id" value="">
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set up delete user modal
        const deleteUserModal = document.getElementById('deleteUserModal');
        if (deleteUserModal) {
            deleteUserModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const userName = button.getAttribute('data-user-name');
                
                document.getElementById('deleteUserId').value = userId;
                document.getElementById('userName').textContent = userName;
            });
        }
    });
</script>
