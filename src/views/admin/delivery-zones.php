<?php
/** @var $this \App\Core\View */
/** @var $cities array */
/** @var $districts array */

use App\Core\Application;
?>

<div class="container admin-dashboard py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Manage Delivery Zones</h1>
        <div>
            <a href="/admin" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if (Application::$app->session->getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= Application::$app->session->getFlash('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (Application::$app->session->getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= Application::$app->session->getFlash('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Districts Management -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Districts</h5>
                </div>
                <div class="card-body">
                    <!-- Add District Form -->
                    <form action="/admin/districts/add" method="post" class="mb-4">
                        <div class="mb-3">
                            <label for="district_name" class="form-label">Add New District</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="district_name" name="district_name" placeholder="Enter district name" required>
                                <button type="submit" class="btn btn-primary">Add District</button>
                            </div>
                        </div>
                    </form>

                    <!-- Districts List -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>District Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($districts)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No districts found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($districts as $district): ?>
                                        <tr>
                                            <td><?= $district['id'] ?></td>
                                            <td>
                                                <span class="district-name" data-id="<?= $district['id'] ?>"><?= htmlspecialchars($district['district_name']) ?></span>
                                                <form action="/admin/districts/edit" method="post" class="edit-district-form d-none" data-id="<?= $district['id'] ?>">
                                                    <div class="input-group input-group-sm">
                                                        <input type="hidden" name="district_id" value="<?= $district['id'] ?>">
                                                        <input type="text" class="form-control" name="district_name" value="<?= htmlspecialchars($district['district_name']) ?>" required>
                                                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                                                        <button type="button" class="btn btn-secondary btn-sm cancel-edit-district">Cancel</button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary edit-district-btn" data-id="<?= $district['id'] ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form action="/admin/districts/delete" method="post" class="d-inline">
                                                        <input type="hidden" name="district_id" value="<?= $district['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this district? This cannot be undone.')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
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

        <!-- Cities Management -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Cities</h5>
                </div>
                <div class="card-body">
                    <!-- Add City Form -->
                    <form action="/admin/cities/add" method="post" class="mb-4">
                        <div class="mb-3">
                            <label for="city_district_id" class="form-label">Add New City</label>
                            <select class="form-select mb-2" id="city_district_id" name="district_id" required>
                                <option value="">Select District</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?= $district['id'] ?>"><?= htmlspecialchars($district['district_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="input-group">
                                <input type="text" class="form-control" id="city_name" name="city_name" placeholder="Enter city name" required>
                                <button type="submit" class="btn btn-primary">Add City</button>
                            </div>
                        </div>
                    </form>

                    <!-- Cities List -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>City Name</th>
                                    <th>District</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cities)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No cities found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cities as $city): ?>
                                        <tr>
                                            <td><?= $city['id'] ?></td>
                                            <td>
                                                <span class="city-name" data-id="<?= $city['id'] ?>"><?= htmlspecialchars($city['city_name']) ?></span>
                                                <form action="/admin/cities/edit" method="post" class="edit-city-form d-none" data-id="<?= $city['id'] ?>">
                                                    <div class="input-group input-group-sm">
                                                        <input type="hidden" name="city_id" value="<?= $city['id'] ?>">
                                                        <input type="text" class="form-control" name="city_name" value="<?= htmlspecialchars($city['city_name']) ?>" required>
                                                        <select class="form-select" name="district_id" required>
                                                            <?php foreach ($districts as $district): ?>
                                                                <option value="<?= $district['id'] ?>" <?= $district['id'] == $city['district_id'] ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($district['district_name']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                                                        <button type="button" class="btn btn-secondary btn-sm cancel-edit">Cancel</button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td><?= htmlspecialchars($city['district_name']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary edit-city-btn" data-id="<?= $city['id'] ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form action="/admin/cities/delete" method="post" class="d-inline">
                                                        <input type="hidden" name="city_id" value="<?= $city['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this city? This cannot be undone.')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
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
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // City editing functionality
        const editCityBtns = document.querySelectorAll('.edit-city-btn');
        const cityNames = document.querySelectorAll('.city-name');
        const editCityForms = document.querySelectorAll('.edit-city-form');
        const cancelEditBtns = document.querySelectorAll('.cancel-edit');
        
        editCityBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const cityId = this.getAttribute('data-id');
                
                // Hide all edit forms first
                editCityForms.forEach(form => form.classList.add('d-none'));
                
                // Show all city names
                cityNames.forEach(name => name.classList.remove('d-none'));
                
                // Hide this city name and show its edit form
                const cityName = document.querySelector(`.city-name[data-id="${cityId}"]`);
                const editForm = document.querySelector(`.edit-city-form[data-id="${cityId}"]`);
                
                if (cityName && editForm) {
                    cityName.classList.add('d-none');
                    editForm.classList.remove('d-none');
                }
            });
        });
        
        cancelEditBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const form = this.closest('.edit-city-form');
                const cityId = form.getAttribute('data-id');
                const cityName = document.querySelector(`.city-name[data-id="${cityId}"]`);
                
                form.classList.add('d-none');
                cityName.classList.remove('d-none');
            });
        });
        
        // District editing functionality
        const editDistrictBtns = document.querySelectorAll('.edit-district-btn');
        const districtNames = document.querySelectorAll('.district-name');
        const editDistrictForms = document.querySelectorAll('.edit-district-form');
        const cancelEditDistrictBtns = document.querySelectorAll('.cancel-edit-district');
        
        editDistrictBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const districtId = this.getAttribute('data-id');
                
                // Hide all edit forms first
                editDistrictForms.forEach(form => form.classList.add('d-none'));
                
                // Show all district names
                districtNames.forEach(name => name.classList.remove('d-none'));
                
                // Hide this district name and show its edit form
                const districtName = document.querySelector(`.district-name[data-id="${districtId}"]`);
                const editForm = document.querySelector(`.edit-district-form[data-id="${districtId}"]`);
                
                if (districtName && editForm) {
                    districtName.classList.add('d-none');
                    editForm.classList.remove('d-none');
                }
            });
        });
        
        cancelEditDistrictBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const form = this.closest('.edit-district-form');
                const districtId = form.getAttribute('data-id');
                const districtName = document.querySelector(`.district-name[data-id="${districtId}"]`);
                
                form.classList.add('d-none');
                districtName.classList.remove('d-none');
            });
        });
    });
</script>
