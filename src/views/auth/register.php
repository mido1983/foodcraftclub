<?php
/** @var $this \App\Core\View */
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Create New User</h4>
                </div>
                <div class="card-body">
                    <form action="/admin/users/create" method="post">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">User Role</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="1" id="role_admin">
                                <label class="form-check-label" for="role_admin">Administrator</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="2" id="role_seller">
                                <label class="form-check-label" for="role_seller">Seller</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="3" id="role_client">
                                <label class="form-check-label" for="role_client">Client</label>
                            </div>
                            <div class="form-text text-muted">Select at least one role. If none selected, Client will be assigned by default.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" selected>Active</option>
                                <option value="pending">Pending</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Create User</button>
                            <a href="/admin/users" class="btn btn-outline-secondary">Back to Users</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
