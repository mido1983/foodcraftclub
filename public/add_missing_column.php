<?php
// u0421u043au0440u0438u043fu0442 u0434u043bu044f u0434u043eu0431u0430u0432u043bu0435u043du0438u044f u043du0435u0434u043eu0441u0442u0430u044eu0449u0435u0439 u043au043eu043bu043eu043du043au0438 available_for_preorder u0432 u0442u0430u0431u043bu0438u0446u0443 products

// u041fu043eu0434u043au043bu044eu0447u0430u0435u043c u043du0435u043eu0431u0445u043eu0434u0438u043cu044bu0435 u0444u0430u0439u043bu044b
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

// u041fu043eu043bu0443u0447u0430u0435u043c u043au043eu043du0444u0438u0433u0443u0440u0430u0446u0438u044e u0431u0430u0437u044b u0434u0430u043du043du044bu0445
$dbConfig = require_once dirname(__DIR__) . '/config/db.php';

// u0424u0443u043du043au0446u0438u044f u0434u043bu044f u043fu0440u043eu0432u0435u0440u043au0438 u0441u0443u0449u0435u0441u0442u0432u043eu0432u0430u043du0438u044f u043au043eu043bu043eu043du043au0438 u0432 u0442u0430u0431u043bu0438u0446u0435
function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
    $stmt->execute([$column]);
    return $stmt->rowCount() > 0;
}

try {
    // u0421u043eu0437u0434u0430u0435u043c u043fu043eu0434u043au043bu044eu0447u0435u043du0438u0435 u043a u0431u0430u0437u0435 u0434u0430u043du043du044bu0445
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    
    echo "<h1>u0414u043eu0431u0430u0432u043bu0435u043du0438u0435 u043du0435u0434u043eu0441u0442u0430u044eu0449u0435u0439 u043au043eu043bu043eu043du043au0438 available_for_preorder</h1>";
    
    // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0441u0443u0449u0435u0441u0442u0432u043eu0432u0430u043du0438u0435 u0442u0430u0431u043bu0438u0446u044b products
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($stmt->rowCount() === 0) {
        echo "<p style='color: red;'>u0422u0430u0431u043bu0438u0446u0430 products u043du0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442!</p>";
        exit;
    }
    
    // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0441u0443u0449u0435u0441u0442u0432u043eu0432u0430u043du0438u0435 u043au043eu043bu043eu043du043au0438 available_for_preorder
    if (columnExists($pdo, 'products', 'available_for_preorder')) {
        echo "<p style='color: green;'>u041au043eu043bu043eu043du043au0430 'available_for_preorder' u0443u0436u0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442 u0432 u0442u0430u0431u043bu0438u0446u0435 products.</p>";
    } else {
        // u0414u043eu0431u0430u0432u043bu044fu0435u043c u043au043eu043bu043eu043du043au0443 available_for_preorder
        $pdo->exec("ALTER TABLE products ADD COLUMN available_for_preorder TINYINT(1) NOT NULL DEFAULT 0");
        echo "<p style='color: green;'>u041au043eu043bu043eu043du043au0430 'available_for_preorder' u0443u0441u043fu0435u0448u043du043e u0434u043eu0431u0430u0432u043bu0435u043du0430 u0432 u0442u0430u0431u043bu0438u0446u0443 products.</p>";
    }
    
    // u041fu043eu043bu0443u0447u0430u0435u043c u0441u0442u0440u0443u043au0442u0443u0440u0443 u0442u0430u0431u043bu0438u0446u044b u043fu043eu0441u043bu0435 u0438u0437u043cu0435u043du0435u043du0438u0439
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>u0421u0442u0440u0443u043au0442u0443u0440u0430 u0442u0430u0431u043bu0438u0446u044b products:</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>u041fu043eu043bu0435</th><th>u0422u0438u043f</th><th>Null</th><th>u041au043bu044eu0447</th><th>u041fu043e u0443u043cu043eu043bu0447u0430u043du0438u044e</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p><a href='/seller/products'>u0412u0435u0440u043du0443u0442u044cu0441u044f u043a u043fu0440u043eu0434u0443u043au0442u0430u043c</a></p>";
    
} catch (PDOException $e) {
    echo "<h1>u041eu0448u0438u0431u043au0430 u043fu043eu0434u043au043bu044eu0447u0435u043du0438u044f u043a u0431u0430u0437u0435 u0434u0430u043du043du044bu0445</h1>";
    echo "<p style='color: red;'>{$e->getMessage()}</p>";
}
