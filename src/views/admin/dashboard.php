<?php
/** @var $this \App\Core\View */
/** @var $userCount int */
/** @var $sellerCount int */
/** @var $clientCount int */

use App\Core\Application;
?>

<div class="container admin-dashboard py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Admin Dashboard</h1>
        <div>
            <a href="/admin/users" class="btn btn-outline-primary me-2">
                <i class="bi bi-people"></i> Manage Users
            </a>
            <a href="/admin/users/create" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Create User
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase">Total Users</h6>
                            <h2 class="display-4"><?= $userCount ?></h2>
                        </div>
                        <i class="bi bi-people-fill display-4"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="/admin/users" class="text-white text-decoration-none">View Details</a>
                    <i class="bi bi-arrow-right text-white"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase">Sellers</h6>
                            <h2 class="display-4"><?= $sellerCount ?></h2>
                        </div>
                        <i class="bi bi-shop display-4"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="/admin/users?role=seller" class="text-white text-decoration-none">View Details</a>
                    <i class="bi bi-arrow-right text-white"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase">Clients</h6>
                            <h2 class="display-4"><?= $clientCount ?></h2>
                        </div>
                        <i class="bi bi-person-fill display-4"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="/admin/users?role=client" class="text-white text-decoration-none">View Details</a>
                    <i class="bi bi-arrow-right text-white"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="/admin/users/create" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-person-plus"></i> Create New User
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/admin/delivery-zones" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-geo-alt"></i> Manage Delivery Zones
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/admin/products" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-box"></i> Manage Products
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/admin/orders" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-cart"></i> View Orders
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="/admin/settings" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-gear"></i> System Settings
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">System Information</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            PHP Version
                            <span class="badge bg-primary rounded-pill"><?= phpversion() ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            MySQL Version
                            <span class="badge bg-primary rounded-pill"><?= Application::$app->db->getAttribute(\PDO::ATTR_SERVER_VERSION) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Environment
                            <span class="badge bg-<?= $_ENV['APP_ENV'] === 'production' ? 'danger' : 'success' ?> rounded-pill">
                                <?= ucfirst($_ENV['APP_ENV']) ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Debug Mode
                            <span class="badge bg-<?= $_ENV['APP_DEBUG'] ? 'warning' : 'secondary' ?> rounded-pill">
                                <?= $_ENV['APP_DEBUG'] ? 'Enabled' : 'Disabled' ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
