<?php
/**
 * @var array $sellerProfile
 * @var array $cities
 * @var array $districts
 * @var array $deliveryAreas
 * @var array $notifications
 * @var int $unreadNotifications
 */

// Create lookup arrays for city and district names
$cityNames = [];
foreach ($cities as $city) {
    $cityNames[$city['id']] = isset($city['city_name']) ? $city['city_name'] : 'City ID: ' . $city['id'];
}

$districtNames = [];
foreach ($districts as $district) {
    $districtNames[$district['id']] = isset($district['district_name']) ? $district['district_name'] : 'District ID: ' . $district['id'];
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once __DIR__ . '/../_sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Delivery Areas Management</h1>
            </div>
            
            <?php if (isset($_SESSION['flash']['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['flash']['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['flash']['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['flash']['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Add New Delivery Area</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($cities) || empty($districts)): ?>
                                <div class="alert alert-warning">
                                    <p><strong>Setup Required:</strong> Before you can add delivery areas, you need to populate the cities and districts database tables.</p>
                                </div>
                            <?php else: ?>
                                <form action="/seller/delivery-areas/add" method="post">
                                    <div class="mb-3">
                                        <label for="city_id" class="form-label">City</label>
                                        <select class="form-select" id="city_id" name="city_id" required>
                                            <option value="">Select City</option>
                                            <?php foreach ($cities as $city): ?>
                                                <option value="<?= $city['id'] ?>"><?= htmlspecialchars($cityNames[$city['id']]) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="district_id" class="form-label">District</label>
                                        <select class="form-select" id="district_id" name="district_id" required>
                                            <option value="">Select District</option>
                                            <?php foreach ($districts as $district): ?>
                                                <option value="<?= $district['id'] ?>"><?= htmlspecialchars($districtNames[$district['id']]) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="delivery_fee" class="form-label">Delivery Fee</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₪</span>
                                            <input type="number" class="form-control" id="delivery_fee" name="delivery_fee" min="0" step="0.01" value="0">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="min_order_amount" class="form-label">Minimum Order Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₪</span>
                                            <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" min="0" step="0.01" value="0">
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Add Delivery Area</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Current Delivery Areas</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($deliveryAreas)): ?>
                                <div class="alert alert-info mb-0">
                                    You haven't added any delivery areas yet. Add your first delivery area using the form on the left.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>City</th>
                                                <th>District</th>
                                                <th>Delivery Fee</th>
                                                <th>Min. Order</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($deliveryAreas as $area): ?>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        if (isset($area['city_name'])) {
                                                            echo htmlspecialchars($area['city_name']);
                                                        } else if (isset($area['city_id']) && isset($cityNames[$area['city_id']])) {
                                                            echo htmlspecialchars($cityNames[$area['city_id']]);
                                                        } else {
                                                            echo 'Unknown City';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if (isset($area['district_name'])) {
                                                            echo htmlspecialchars($area['district_name']);
                                                        } else if (isset($area['district_id']) && isset($districtNames[$area['district_id']])) {
                                                            echo htmlspecialchars($districtNames[$area['district_id']]);
                                                        } else {
                                                            echo 'Unknown District';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>₪<?= number_format($area['delivery_fee'], 2) ?></td>
                                                    <td>₪<?= number_format($area['free_from_amount'] ?? 0, 2) ?></td>
                                                    <td>
                                                        <form action="/seller/delivery-areas/delete" method="post" class="d-inline">
                                                            <input type="hidden" name="id" value="<?= $area['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this delivery area?')">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const citySelect = document.getElementById('city_id');
        const districtSelect = document.getElementById('district_id');
        
        // Create a mapping of cities to districts
        const cityToDistrictMap = {};
        <?php foreach ($cities as $city): ?>
            if (!cityToDistrictMap['<?= $city['id'] ?>']) {
                cityToDistrictMap['<?= $city['id'] ?>'] = '<?= $city['district_id'] ?>';
            }
        <?php endforeach; ?>
        
        // When a city is selected, automatically select the corresponding district
        citySelect.addEventListener('change', function() {
            const selectedCityId = this.value;
            
            if (!selectedCityId) {
                // If no city selected, reset district
                districtSelect.value = '';
                return;
            }
            
            // Get the district for this city
            const districtId = cityToDistrictMap[selectedCityId];
            if (districtId) {
                districtSelect.value = districtId;
            }
        });
    });
</script>
