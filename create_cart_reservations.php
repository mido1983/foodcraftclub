<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=foodcraftclub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "u041fu043eu0434u043au043bu044eu0447u0435u043du0438u0435 u043a u0431u0430u0437u0435 u0434u0430u043du043du044bu0445 u0443u0441u043fu0435u0448u043du043e!\n";
    
    // u041fu0440u043eu0432u0435u0440u043au0430 u0441u0443u0449u0435u0441u0442u0432u043eu0432u0430u043du0438u044f u0442u0430u0431u043bu0438u0446u044b cart_reservations
    $stmt = $pdo->query("SHOW TABLES LIKE 'cart_reservations'");
    if ($stmt->rowCount() > 0) {
        echo "u0422u0430u0431u043bu0438u0446u0430 cart_reservations u0443u0436u0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442!\n";
        
        // u041fu043eu043au0430u0437u0430u0442u044c u0441u0442u0440u0443u043au0442u0443u0440u0443 u0442u0430u0431u043bu0438u0446u044b
        $columns = $pdo->query("SHOW COLUMNS FROM cart_reservations");
        echo "u0421u0442u0440u0443u043au0442u0443u0440u0430 u0442u0430u0431u043bu0438u0446u044b cart_reservations:\n";
        while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
    } else {
        echo "u0422u0430u0431u043bu0438u0446u0430 cart_reservations u043du0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442, u0441u043eu0437u0434u0430u044e...\n";
        
        // u0421u043eu0437u0434u0430u043du0438u0435 u0442u0430u0431u043bu0438u0446u044b cart_reservations
        $pdo->exec("CREATE TABLE IF NOT EXISTS cart_reservations (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            product_id BIGINT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            reserved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            status ENUM('active', 'expired', 'completed') NOT NULL DEFAULT 'active',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            INDEX (expires_at),
            INDEX (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        
        echo "u0422u0430u0431u043bu0438u0446u0430 cart_reservations u0443u0441u043fu0435u0448u043du043e u0441u043eu0437u0434u0430u043du0430!\n";
        
        // u041fu043eu043au0430u0437u0430u0442u044c u0441u0442u0440u0443u043au0442u0443u0440u0443 u0441u043eu0437u0434u0430u043du043du043eu0439 u0442u0430u0431u043bu0438u0446u044b
        $columns = $pdo->query("SHOW COLUMNS FROM cart_reservations");
        echo "u0421u0442u0440u0443u043au0442u0443u0440u0430 u0442u0430u0431u043bu0438u0446u044b cart_reservations:\n";
        while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
    }
    
    // u0414u043eu0431u0430u0432u043bu0435u043du0438u0435 u0437u0430u043fu0438u0441u0438 u0432 u0442u0430u0431u043bu0438u0446u0443 migrations
    $migrationName = 'm0005_add_product_quantity_and_reservations.php';
    $stmt = $pdo->prepare("SELECT * FROM migrations WHERE migration = ?");
    $stmt->execute([$migrationName]);
    
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migrationName]);
        echo "\nu0417u0430u043fu0438u0441u044c u043e u043cu0438u0433u0440u0430u0446u0438u0438 '{$migrationName}' u0434u043eu0431u0430u0432u043bu0435u043du0430 u0432 u0442u0430u0431u043bu0438u0446u0443 migrations.\n";
    } else {
        echo "\nu0417u0430u043fu0438u0441u044c u043e u043cu0438u0433u0440u0430u0446u0438u0438 '{$migrationName}' u0443u0436u0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442 u0432 u0442u0430u0431u043bu0438u0446u0435 migrations.\n";
    }
    
} catch (PDOException $e) {
    echo "u041eu0448u0438u0431u043au0430: " . $e->getMessage() . "\n";
}
