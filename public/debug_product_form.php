<?php
// u0421u043au0440u0438u043fu0442 u0434u043bu044f u043eu0442u043bu0430u0434u043au0438 u0444u043eu0440u043cu044b u0434u043eu0431u0430u0432u043bu0435u043du0438u044f u043fu0440u043eu0434u0443u043au0442u0430

// u041fu043eu0434u043au043bu044eu0447u0430u0435u043c u043du0435u043eu0431u0445u043eu0434u0438u043cu044bu0435 u0444u0430u0439u043bu044b
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

// u0421u043eu0437u0434u0430u0435u043c u043fu043eu0434u043au043bu044eu0447u0435u043du0438u0435 u043a u0431u0430u0437u0435 u0434u0430u043du043du044bu0443
$dbConfig = require_once dirname(__DIR__) . '/config/db.php';

// u0424u0443u043du043au0446u0438u044f u0434u043bu044f u043fu0440u043eu0432u0435u0440u043au0438 u0441u0443u0449u0435u0441u0442u0432u043eu0432u0430u043du0438u044f u043au043eu043bu043eu043du043au0443 u0432 u0442u0430u0431u043bu0438u0446u0435
function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
    $stmt->execute([$column]);
    return $stmt->rowCount() > 0;
}

// u0424u0443u043du043au0446u0438u044f u0434u043bu044f u0434u043eu0431u0430u0432u043bu0435u043du0438u044f u043au043eu043bu043eu043du043au0443, u0435u0441u043bu0438 u043eu043du0430 u043du0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442
function addColumnIfNotExists($pdo, $table, $column, $definition) {
    if (!columnExists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        return true;
    }
    return false;
}

// u0424u0443u043du043au0446u0438u044f u0434u043bu044f u0437u0430u043fu0448u0438 u0432 u0444u0430u0439u043b
function logToFile($message, $data = [], $file = 'debug_product.log') {
    $logPath = dirname(__DIR__) . '/logs/' . $file;
    $timestamp = date('Y-m-d H:i:s');
    $dataStr = !empty($data) ? ' ' . json_encode($data, JSON_UNESCAPED_UNICODE) : '';
    $logEntry = "[{$timestamp}] {$message}{$dataStr}" . PHP_EOL;
    file_put_contents($logPath, $logEntry, FILE_APPEND | LOCK_EX);
}

try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    
    echo "<h1>u041eu0442u043bu0430u0434u043au0430 u0444u043eu0440u043cu044b u0434u043eu0431u0430u0432u043bu0435u043du0438u044f u043fu0440u043eu0434u0443u043au0442u0430</h1>";
    
    // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0438 u0434u043eu0431u0430u0432u043bu044fu0435u043c u043au043eu043bu043eu043du043au0443 available_for_preorder, u0435u0441u043bu0438 u043eu043du0430 u043du0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442
    $added = addColumnIfNotExists($pdo, 'products', 'available_for_preorder', 'tinyint(1) NOT NULL DEFAULT 0');
    if ($added) {
        echo "<div style='color: green;'>u041au043eu043bu0435u043du0430 'available_for_preorder' u0431u044bu043bu0430 u0434u043eu0431u0430u0432u043bu0435u043du0430 u0432 u0442u0430u0431u043bu0438u0446u0443 'products'</div>";
        logToFile("u0414u043eu0431u0430u0432u043bu0435u043du0430 u043au043eu043bu0435u043du0430 'available_for_preorder' u0432 u0442u0430u0431u043bu0438u0446u0443 'products'");
    } else {
        echo "<div>u041au043eu043bu0435u043du0430 'available_for_preorder' u0443u0436u0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442 u0432 u0442u0430u0431u043bu0438u0446u0435 'products'</div>";
    }
    
    // u041fu0440u043eu0431u0435u0440u0430u0435u043c u0441u0442u0440u0443u043au0442u0443u0440u0443 u0442u0430u0431u043bu0438u0446u044b
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
    
    // u0422u0435u0441u0442u0438u0440u0443u0435u043c u0437u0430u043fu0440u043eu0441 u043du0430 u0434u043eu0431u0430u0432u043bu0435u043du0438u0435 u043fu0440u043eu0434u0443u043au0442u0430
    echo "<h2>u0422u0435u0441u0442 SQL-u0437u0430u043fu0440u043eu0441u0430 u043du0430 u0434u043eu0431u0430u0432u043bu0435u043du0438u0435 u043fu0440u043eu0434u0443u043au0442u0430:</h2>";
    
    // u041fu0443u0434u0433u043eu0442u0430u0432u043bu0438u0432u0430u0435u043c seller_profile_id u0434u043bu044f u0442u0435u0441u0442u0430
    $stmt = $pdo->query("SELECT id FROM seller_profiles LIMIT 1");
    $sellerProfile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sellerProfile) {
        echo "<div style='color: red;'>u041du0435 u043du0430u0439u0434u0435u043d u043du0438 u043eu0434u0438u043d u043fu0440u043eu0444u0438u043bu044c u043fu0440u043eu0434u0430u0432u0446u0430 u0434u043bu044f u0442u0435u0441u0442u0430</div>";
        logToFile("u041du0435 u043du0430u0439u0434u0435u043d u043fu0440u043eu0444u0438u043bu044c u043fu0440u043eu0434u0430u0432u0446u0430 u0434u043bu044f u0442u0435u0441u0442u0430");
    } else {
        $sellerId = $sellerProfile['id'];
        
        // u0422u0435u0441u0442u0438u0440u0443u0435u043c u0437u0430u043fu0440u043eu0441 u0434u043bu044f u0434u043eu0431u0430u0432u043bu0435u043du0438u044f u043fu0440u043eu0434u0443u043au0442u0430
        $testSql = "INSERT INTO products (product_name, description, price, category_id, seller_profile_id, is_active, available_for_preorder, created_at, updated_at)
                  VALUES ('u0422u0435u0441u0442u043eu0432u044bu0439 u043fu0440u043eu0434u0443u043au0442', 'u041eu043fu0438u0441u0430u043du0438u0435 u0442u0435u0441u0442u043eu0432u043eu0433u043e u043fu0440u043eu0434u0443u043au0442u0430 u0434u043bu044f u043eu0442u043bu0430u0434u043au0438', 100.00, 1, {$sellerId}, 1, 1, NOW(), NOW())";
        
        echo "<pre>{$testSql}</pre>";
        
        // u0422u0435u0441u0442u0438u0440u0443u0435u043c u0437u0430u043fu0440u043eu0441 u043du0430 u0434u043eu0431u0430u0432u043bu0435u043du0438u0435 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u044f
        $testImageSql = "INSERT INTO product_images (product_id, image_url, is_main) VALUES (1, '/uploads/test.jpg', 1)";
        
        echo "<pre>{$testImageSql}</pre>";
        
        try {
            // u0412u044bu043fu043eu043bu043du044fu0435u043c u0437u0430u043fu0440u043eu0441 u0442u043eu043bu044cu043au043e u0434u043bu044f u043fu0440u043eu0432u0435u0440u043au0438, u0431u0435u0437 u0440u0435u0430u043bu044cu043du043eu0433u043e u0434u043eu0431u0430u0432u043bu0435u043du0438u044f u0434u0430u043du043du044bu0443
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->beginTransaction();
            $testResult = $pdo->exec($testSql);
            $pdo->rollBack(); // u041eu0442u043cu0435u043du044fu0435u043c u0442u0440u0430u043du0437u0430u043au0446u0438u044e, u0447u0442u043eu0431u044b u043du0435 u0434u043eu0431u0430u0432u043bu044fu0442u044c u0442u0435u0441u0442u043eu0432u044bu0439 u0434u0430u043du043du044bu0435
            echo "<div style='color: blue;'>u0422u0440u0430u043du0437u0430u043au0446u0438u044f u043eu0442u043cu0435u043du0435u043du0430, u0442u0435u0441u0442u043eu0432u044bu0439 u0437u0430u043fu0440u043eu0441 u043du0435 u0441u043eu0445u0440u0430u043du0435u043d u0432 u0431u0430u0437u0435 u0434u0430u043du043du044bu0443</div>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "<div style='color: red;'>u041eu0448u0438u0431u043au0430 u0432u044bu043fu043eu043bu043du0435u043du0438u044f u0437u0430u043fu0440u043eu0441u0430: " . htmlspecialchars($e->getMessage()) . "</div>";
            logToFile("u041eu0448u0438u0431u043au0430 u0432u044bu043fu043eu043bu043du0435u043du0438u044f u0437u0430u043fu0440u043eu0441u0430 u043du0430 u0434u043eu0431u0430u0432u043bu0435u043du0438u0435 u043fu0440u043eu0434u0443u043au0442u0430", ['error' => $e->getMessage()]);
        }
    }
    
    // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0444u043eu0440u043cu044b u0434u043eu0431u0430u0432u043bu0435u043du0438u044f u043fu0440u043eu0434u0443u043au0442u0430
    echo "<h2>u041fu0440u043eu0432u0435u0440u043au0430 u0444u043eu0440u043cu044b u0434u043eu0431u0430u0432u043bu0435u043du0438u044f u043fu0440u043eu0434u0443u043au0442u0430:</h2>";
    
    // u0414u043eu0431u0430u0432u043bu044fu0435u043c u0442u0435u0441u0442u043eu0432u0443u044e u0444u043eu0440u043cu0443 u0434u043bu044f u043eu0442u043fu0440u0430u0432u043au0438 u0434u0430u043du043du044bu0443
    echo "<form action='/seller/products/add' method='post' enctype='multipart/form-data'>";
    echo "<div style='margin-bottom: 10px;'><label>u041du0430u0437u0432u0430u043du0438u0435 u043fu0440u043eu0434u0443u043au0442u0430: <input type='text' name='product_name' value='u0422u0435u0441u0442u043eu0432u044bu0439 u043fu0440u043eu0434u0443u043au0442'></label></div>";
    echo "<div style='margin-bottom: 10px;'><label>u041eu043fu0438u0441u0430u043du0438u0435: <textarea name='description'>u0422u0435u0441u0442u043eu0432u043eu0435 u043eu043fu0438u0441u0430u043du0438u0435</textarea></label></div>";
    echo "<div style='margin-bottom: 10px;'><label>u0426u0435u043du0430: <input type='number' name='price' value='100'></label></div>";
    echo "<div style='margin-bottom: 10px;'><label>u0418u0437u043eu0431u0440u0430u0436u0435u043du0438u0435: <input type='file' name='image'></label></div>";
    echo "<div style='margin-bottom: 10px;'><label>u0421u0442u0430u0442u0443u0441: 
        <select name='product_status'>
            <option value='active'>u0410u043au0442u0438u0432u0435u043d (u0434u043eu0441u0442u0443u043fu0435u043d u0434u043bu044f u043fu043eu043au0443u043fu043au0438)</option>
            <option value='draft'>u0427u0435u0440u043du043eu0432u0438u043a (u043du0435 u043eu0442u043eu0431u0440u0430u0437u0430u0435u0442u0441u044f u0432 u043au0430u0442u0430u043bu043eu0433u0435)</option>
        </select>
    </label></div>";
    echo "<div style='margin-bottom: 10px;'><label><input type='checkbox' name='available_for_preorder' value='1'> u0414u043eu0441u0442u0443u043fu0435u043d u0434u043bu044f u043fu0440u0435u0434u0437u0430u043au0430u0437u0430</label></div>";
    echo "<div style='margin-bottom: 10px;'><button type='submit'>u041eu0442u043fu0440u0430u0432u0438u0442u044c</button></div>";
    echo "</form>";
    
} catch (PDOException $e) {
    echo "<h1>u041eu0448u0438u0431u043au0430 u043fu0440u0438 u043fu043eu0434u043au043bu044eu0447u0435u043du0438u044e u043a u0431u0430u0437u0435 u0434u0430u043du043du044bu0443</h1>";
    echo "<p style='color: red;'>{$e->getMessage()}</p>";
}
