<?php
// u0421u043au0440u0438u043fu0442 u0434u043bu044f u0438u0441u043fu0440u0430u0432u043bu0435u043du0438u044f u043fu0440u043eu0431u043bu0435u043cu044b u0441 u0441u043eu0445u0440u0430u043du0435u043du0438u0435u043c u0441u043fu043eu0441u043eu0431u043eu0432 u043eu043fu043bu0430u0442u044b

// u041fu043eu0434u043au043bu044eu0447u0435u043du0438u0435 u043a u0431u0430u0437u0435 u0434u0430u043du043du044bu0445
$host = 'localhost';
$db = 'foodcraftclub';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    echo "\nu041fu043eu0434u043au043bu044eu0447u0435u043du0438u0435 u043a u0431u0430u0437u0435 u0434u0430u043du043du044bu0445 u0443u0441u043fu0435u0448u043du043e\n";
    
    // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0441u0443u0449u0435u0441u0442u0432u043eu0432u0430u043du0438u0435 u0442u0430u0431u043bu0438u0446u044b seller_payment_options
    $stmt = $pdo->query("SHOW TABLES LIKE 'seller_payment_options'");
    $tableExists = $stmt->fetchColumn();
    
    if (!$tableExists) {
        // u0421u043eu0437u0434u0430u0435u043c u0442u0430u0431u043bu0438u0446u0443, u0435u0441u043bu0438 u043eu043du0430 u043du0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS seller_payment_options (
                id INT AUTO_INCREMENT PRIMARY KEY,
                seller_profile_id INT NOT NULL,
                payment_method_id INT NOT NULL,
                enabled TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_seller_payment (seller_profile_id, payment_method_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($createTableSQL);
        echo "u0422u0430u0431u043bu0438u0446u0430 seller_payment_options u0441u043eu0437u0434u0430u043du0430\n";
    } else {
        echo "u0422u0430u0431u043bu0438u0446u0430 seller_payment_options u0443u0436u0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442\n";
    }
    
    // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0441u0442u0440u0443u043au0442u0443u0440u0443 u0442u0430u0431u043bu0438u0446u044b
    $stmt = $pdo->query("DESCRIBE seller_payment_options");
    $columns = $stmt->fetchAll();
    
    echo "\nu0421u0442u0440u0443u043au0442u0443u0440u0430 u0442u0430u0431u043bu0438u0446u044b seller_payment_options:\n";
    foreach ($columns as $column) {
        echo "{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}\n";
    }
    
    // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0434u043eu0441u0442u0443u043fu043du044bu0435 u043cu0435u0442u043eu0434u044b u043eu043fu043bu0430u0442u044b
    $stmt = $pdo->query("SELECT * FROM payment_methods");
    $methods = $stmt->fetchAll();
    
    echo "\nu0414u043eu0441u0442u0443u043fu043du044bu0435 u043cu0435u0442u043eu0434u044b u043eu043fu043bu0430u0442u044b:\n";
    foreach ($methods as $method) {
        echo "ID: {$method['id']}, u041du0430u0437u0432u0430u043du0438u0435: {$method['method_name']}\n";
    }
    
    // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0441u0435u043bu043bu0435u0440u043eu0432
    $stmt = $pdo->query("SELECT * FROM seller_profiles LIMIT 5");
    $sellers = $stmt->fetchAll();
    
    echo "\nu041fu0440u043eu0444u0438u043bu0438 u043fu0440u043eu0434u0430u0432u0446u043eu0432:\n";
    foreach ($sellers as $seller) {
        echo "ID: {$seller['id']}, u041du0430u0437u0432u0430u043du0438u0435: {$seller['name']}\n";
        
        // u0414u043bu044f u043au0430u0436u0434u043eu0433u043e u043fu0440u043eu0434u0430u0432u0446u0430 u0434u043eu0431u0430u0432u043bu044fu0435u043c u0432u0441u0435 u043cu0435u0442u043eu0434u044b u043eu043fu043bu0430u0442u044b
        foreach ($methods as $method) {
            try {
                // u0421u043du0430u0447u0430u043bu0430 u0443u0434u0430u043bu044fu0435u043c u0441u0443u0449u0435u0441u0442u0432u0443u044eu0449u0438u0435 u0437u0430u043fu0438u0441u0438
                $stmt = $pdo->prepare("DELETE FROM seller_payment_options WHERE seller_profile_id = ? AND payment_method_id = ?");
                $stmt->execute([$seller['id'], $method['id']]);
                
                // u0414u043eu0431u0430u0432u043bu044fu0435u043c u043du043eu0432u0443u044e u0437u0430u043fu0438u0441u044c
                $stmt = $pdo->prepare("INSERT INTO seller_payment_options (seller_profile_id, payment_method_id, enabled) VALUES (?, ?, ?)");
                $stmt->execute([$seller['id'], $method['id'], 1]);
                
                echo "  u0414u043eu0431u0430u0432u043bu0435u043d u043cu0435u0442u043eu0434 u043eu043fu043bu0430u0442u044b {$method['method_name']} u0434u043bu044f u043fu0440u043eu0434u0430u0432u0446u0430 {$seller['name']}\n";
            } catch (PDOException $e) {
                echo "  u041eu0448u0438u0431u043au0430 u043fu0440u0438 u0434u043eu0431u0430u0432u043bu0435u043du0438u0438 u043cu0435u0442u043eu0434u0430 u043eu043fu043bu0430u0442u044b: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0440u0435u0437u0443u043bu044cu0442u0430u0442
    $stmt = $pdo->query("SELECT spo.*, sp.name as seller_name, pm.method_name 
                         FROM seller_payment_options spo 
                         JOIN seller_profiles sp ON spo.seller_profile_id = sp.id 
                         JOIN payment_methods pm ON spo.payment_method_id = pm.id 
                         LIMIT 20");
    $options = $stmt->fetchAll();
    
    echo "\nu0420u0435u0437u0443u043bu044cu0442u0430u0442 u0434u043eu0431u0430u0432u043bu0435u043du0438u044f u0441u043fu043eu0441u043eu0431u043eu0432 u043eu043fu043bu0430u0442u044b:\n";
    foreach ($options as $option) {
        echo "u041fu0440u043eu0434u0430u0432u0435u0446: {$option['seller_name']}, u041cu0435u0442u043eu0434 u043eu043fu043bu0430u0442u044b: {$option['method_name']}, u0421u0442u0430u0442u0443u0441: " . ($option['enabled'] ? 'u0412u043au043bu044eu0447u0435u043d' : 'u041eu0442u043au043bu044eu0447u0435u043d') . "\n";
    }
    
    echo "\nu0418u0441u043fu0440u0430u0432u043bu0435u043du0438u0435 u0437u0430u0432u0435u0440u0448u0435u043du043e u0443u0441u043fu0435u0448u043du043e!\n";
    
} catch (PDOException $e) {
    echo "u041eu0448u0438u0431u043au0430: " . $e->getMessage() . "\n";
}
