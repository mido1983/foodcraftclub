<?php
/** @var Exception $exception */
?>

<div class="container text-center py-5">
    <h1 class="display-1">Error</h1>
    <h2 class="mb-4">Oops! Something went wrong</h2>
    <?php if (Application::$app->request->isAjax()): ?>
        <p class="lead mb-5"><?= htmlspecialchars($exception->getMessage()) ?></p>
    <?php else: ?>
        <p class="lead mb-5">We encountered an unexpected error. Please try again later.</p>
    <?php endif; ?>
    <a href="/" class="btn btn-primary">Return to Homepage</a>
</div>
