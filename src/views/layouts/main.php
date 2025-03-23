<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->title ?> - Food Craft Club</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/css/main.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="bi bi-basket"></i> Food Craft Club
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/catalog">
                            <i class="bi bi-grid"></i> Catalog
                        </a>
                    </li>
                    <?php if (\App\Core\Application::$app->session->isLoggedIn()): ?>
                        <?php if (\App\Core\Application::$app->session->hasRole('seller')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/seller/dashboard">
                                    <i class="bi bi-speedometer2"></i> Seller Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (\App\Core\Application::$app->session->hasRole('admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin">
                                    <i class="bi bi-shield-lock"></i> Admin Panel
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <!-- Cart Icon -->
                    <li class="nav-item me-2">
                        <a class="nav-link position-relative" href="/cart">
                            <i class="bi bi-cart3 fs-5"></i>
                            <?php 
                            $cartItems = \App\Core\Application::$app->session->get('cart');
                            $cartCount = 0;
                            $cartTotal = 0;
                            
                            if (is_array($cartItems)) {
                                $cartCount = count($cartItems);
                                foreach ($cartItems as $item) {
                                    $cartTotal += $item['price'] * $item['quantity'];
                                }
                            }
                            ?>
                            <?php if ($cartCount > 0): ?>
                                <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $cartCount ?>
                                </span>
                            <?php else: ?>
                                <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                                    0
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (!\App\Core\Application::$app->session->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                    <?php else: ?>
                        <?php $user = \App\Core\Application::$app->session->getUser(); ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user->full_name) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="/seller/profile">
                                        <i class="bi bi-person"></i> Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/orders">
                                        <i class="bi bi-cart"></i> Orders
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="/logout">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container">
            <?php if (\App\Core\Application::$app->session->getFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo \App\Core\Application::$app->session->getFlash('success'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (\App\Core\Application::$app->session->getFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo \App\Core\Application::$app->session->getFlash('error'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>

        {{content}}
    </main>

    <footer class="bg-light py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Food Craft Club. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="/terms" class="text-decoration-none text-dark me-3">Terms</a>
                    <a href="/privacy" class="text-decoration-none text-dark me-3">Privacy</a>
                    <a href="/contact" class="text-decoration-none text-dark">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/main.js"></script>
</body>
</html>
