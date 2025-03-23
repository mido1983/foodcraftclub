<?php
// u041fu0440u043eu0441u0442u043eu0439 u0441u043au0440u0438u043fu0442 u0434u043bu044f u043fu0440u043eu0432u0435u0440u043au0438 u0441u0442u0440u0443u043au0442u0443u0440u044b u0431u0430u0437u044b u0434u0430u043du043du044bu0445

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
    
    if ($tableExists) {
        echo "\nu0422u0430u0431u043bu0438u0446u0430 seller_payment_options u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442\n";
        
        // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0441u0442u0440u0443u043au0442u0443u0440u0443 u0442u0430u0431u043bu0438u0446u044b
        $stmt = $pdo->query("DESCRIBE seller_payment_options");
        $columns = $stmt->fetchAll();
        
        echo "\nu0421u0442u0440u0443u043au0442u0443u0440u0430 u0442u0430u0431u043bu0438u0446u044b seller_payment_options:\n";
        foreach ($columns as $column) {
            echo "{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}\n";
        }
        
        // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0441u043eu0434u0435u0440u0436u0438u043cu043eu0435 u0442u0430u0431u043bu0438u0446u044b
        $stmt = $pdo->query("SELECT * FROM seller_payment_options LIMIT 10");
        $rows = $stmt->fetchAll();
        
        echo "\nu0421u043eu0434u0435u0440u0436u0438u043cu043eu0435 u0442u0430u0431u043bu0438u0446u044b seller_payment_options:\n";
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                echo "ID: {$row['id']}, Seller Profile ID: {$row['seller_profile_id']}, Payment Method ID: {$row['payment_method_id']}\n";
            }
        } else {
            echo "u0422u0430u0431u043bu0438u0446u0430 u043fu0443u0441u0442u0430\n";
        }
    } else {
        echo "\nu0422u0430u0431u043bu0438u0446u0430 seller_payment_options u043du0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442\n";
        
        // u0421u043eu0437u0434u0430u0435u043c u0442u0430u0431u043bu0438u0446u0443
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS seller_payment_options (
                id INT AUTO_INCREMENT PRIMARY KEY,
                seller_profile_id INT NOT NULL,
                payment_method_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_seller_payment (seller_profile_id, payment_method_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($createTableSQL);
        echo "u0422u0430u0431u043bu0438u0446u0430 seller_payment_options u0441u043eu0437u0434u0430u043du0430\n";
    }
    
    // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0434u043eu0441u0442u0443u043fu043du044bu0435 u043cu0435u0442u043eu0434u044b u043eu043fu043bu0430u0442u044b
    $stmt = $pdo->query("SELECT * FROM payment_methods");
    $methods = $stmt->fetchAll();
    
    echo "\nu0414u043eu0441u0442u0443u043fu043du044bu0435 u043cu0435u0442u043eu0434u044b u043eu043fu043bu0430u0442u044b:\n";
    foreach ($methods as $method) {
        echo "ID: {$method['id']}, u041du0430u0437u0432u0430u043du0438u0435: {$method['method_name']}\n";
    }
    
} catch (PDOException $e) {
    echo "u041eu0448u0438u0431u043au0430 u043fu043eu0434u043au043bu044eu0447u0435u043du0438u044f u043a u0431u0430u0437u0435 u0434u0430u043du043du044bu0445: " . $e->getMessage() . "\n";
}
