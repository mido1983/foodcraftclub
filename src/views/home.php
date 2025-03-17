<?php
/** @var string $welcomeMessage */
?>

<div class="container">
    <div class="text-center mb-5">
        <h1 class="display-4"><?php echo htmlspecialchars($welcomeMessage); ?></h1>
        <p class="lead">Discover exclusive craft food from talented artisans</p>
    </div>

    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="card-title">For Buyers</h3>
                    <p class="card-text">Join our exclusive club to access unique craft food products</p>
                    <a href="/register" class="btn btn-primary">Join Now</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="card-title">For Sellers</h3>
                    <p class="card-text">Showcase your craft food to an exclusive audience</p>
                    <a href="/seller/register" class="btn btn-primary">Become a Seller</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="card-title">Members</h3>
                    <p class="card-text">Already a member? Sign in to your account</p>
                    <a href="/login" class="btn btn-primary">Sign In</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 offset-md-3 text-center">
            <h2 class="mb-4">Why Choose Food Craft Club?</h2>
            <div class="list-group">
                <div class="list-group-item">
                    <h5 class="mb-1">Exclusive Access</h5>
                    <p class="mb-1">Members-only marketplace for craft food enthusiasts</p>
                </div>
                <div class="list-group-item">
                    <h5 class="mb-1">Quality Assurance</h5>
                    <p class="mb-1">Carefully selected artisans and products</p>
                </div>
                <div class="list-group-item">
                    <h5 class="mb-1">Direct Communication</h5>
                    <p class="mb-1">Chat directly with sellers and build relationships</p>
                </div>
            </div>
        </div>
    </div>
</div>
