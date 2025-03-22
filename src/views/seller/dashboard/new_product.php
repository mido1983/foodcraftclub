<?php
/**
 * u0428u0430u0431u043bu043eu043du0438u0446u044b u0441u0442u0440u0430u043du0438u0446u044b u0434u043eu0431u0430u0432u043bu0435u043du0438u044f u043du043eu0432u043eu0433u043e u0442u043eu0432u0430u0440u0430
 */
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-3">
            <?php include_once __DIR__ . '/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">u0414u043eu0431u0430u0432u043bu0435u043du0438u0435 u043du043eu0432u043eu0433u043e u0442u043eu0432u0430u0440u0430</h5>
                </div>
                <div class="card-body">
                    <form action="/seller/products/add" method="post" enctype="multipart/form-data" id="newProductForm">
                        <div class="mb-3">
                            <label for="productName" class="form-label">u041du0430u0437u0432u0430u043du0438u0435 u0442u043eu0432u0430u0440u0430 *</label>
                            <input type="text" class="form-control" id="productName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="productDescription" class="form-label">u041eu043fu0438u0441u0430u043du0438u0435 *</label>
                            <textarea class="form-control" id="productDescription" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="productPrice" class="form-label">u0426u0435u043du0430 (u0433u0440u043d) *</label>
                                <input type="number" class="form-control" id="productPrice" name="price" min="0.01" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="productQuantity" class="form-label">u041au043eu043bu0438u0447u0435u0441u0442u0432u043e u0432 u043du0430u043bu0438u0447u0438u0438 *</label>
                                <input type="number" class="form-control" id="productQuantity" name="quantity" min="0" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="productCategory" class="form-label">u041au0430u0442u0435u0433u043eu0440u0438u044f *</label>
                            <select class="form-select" id="productCategory" name="category_id" required>
                                <option value="" selected disabled>u0412u044bu0431u0435u0440u0438u0442u0435 u043au0430u0442u0435u0433u043eu0440u0438u044e</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="productImage" class="form-label">u0418u0437u043eu0431u0440u0430u0436u0435u043du0438u0435 u0442u043eu0432u0430u0440u0430 *</label>
                            <input type="file" class="form-control" id="productImage" name="image" accept="image/*" required>
                            <div class="form-text">u0420u0435u043au043eu043cu0435u043du0434u0443u0435u043cu044bu0439 u0440u0430u0437u043cu0435u0440: 800x600 u043fu0438u043au0441u0435u043bu0435u0439, u043cu0430u043au0441. 2MB</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="productWeight" class="form-label">u0412u0435u0441 (u0433u0440u0430u043cu043cu044b)</label>
                            <input type="number" class="form-control" id="productWeight" name="weight" min="0">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="productIsAvailable" name="is_available" value="1" checked>
                                <label class="form-check-label" for="productIsAvailable">u0422u043eu0432u0430u0440 u0434u043eu0441u0442u0443u043fu0435u043d u0434u043bu044f u043fu0440u043eu0434u0430u0436u0438</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="productIsFeatured" name="is_featured" value="1">
                                <label class="form-check-label" for="productIsFeatured">u0420u0435u043au043eu043cu0435u043du0434u0443u0435u043cu044bu0439 u0442u043eu0432u0430u0440</label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="/seller/products" class="btn btn-outline-secondary me-md-2">u041eu0442u043cu0435u043du0430</a>
                            <button type="submit" class="btn btn-primary">u0421u043eu0445u0440u0430u043du0438u0442u044c u0442u043eu0432u0430u0440</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('newProductForm');
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            // u041fu0440u043eu0432u0435u0440u043au0430 u0446u0435u043du044b
            const price = document.getElementById('productPrice');
            if (parseFloat(price.value) <= 0) {
                isValid = false;
                price.classList.add('is-invalid');
                alert('u0426u0435u043du0430 u0434u043eu043bu0436u043du0430 u0431u044bu0442u044c u0431u043eu043bu044cu0448u0435 u043du0443u043bu044a');
            }
            
            // u041fu0440u043eu0432u0435u0440u043au0430 u0440u0430u0437u043cu0435u0440u0430 u0444u0430u0439u043bu0430
            const imageInput = document.getElementById('productImage');
            if (imageInput.files.length > 0) {
                const fileSize = imageInput.files[0].size / 1024 / 1024; // u0432 u041cu0411
                if (fileSize > 2) {
                    isValid = false;
                    imageInput.classList.add('is-invalid');
                    alert('u0420u0430u0437u043cu0435u0440 u0444u0430u0439u043bu0430 u043du0435 u0434u043eu043bu0436u0435u043d u043fu0440u0435u0432u044bu0448u0430u0442u044c 2u041cu0411');
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
</script>
