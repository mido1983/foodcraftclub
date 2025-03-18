<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;

// u0418u043du0438u0446u0438u0430u043bu0438u0437u0438u0440u0443u0435u043c u043fu0440u0438u043bu043eu0436u0435u043du0438u0435
$app = new Application(dirname(__DIR__));

echo "<h1>u0414u043eu0431u0430u0432u043bu0435u043du0438u0435 u043au043eu043bu043eu043du043au0438 image_url u0432 u0442u0430u0431u043bu0438u0446u0443 products</h1>";

// u041fu0440u043eu0432u0435u0440u044fu0435u043c u0441u0442u0440u0443u043au0442u0443u0440u0443 u0442u0430u0431u043bu0438u0446u044b
try {
    $statement = $app->db->prepare("DESCRIBE products");
    $statement->execute();
    $columns = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    $hasImageUrlColumn = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'image_url') {
            $hasImageUrlColumn = true;
            break;
        }
    }
    
    echo "<h2>u0422u0435u043au0443u0449u0430u044f u0441u0442u0440u0443u043au0442u0443u0440u0430 u0442u0430u0431u043bu0438u0446u044b</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    if ($hasImageUrlColumn) {
        echo "<div style='color: green; font-weight: bold;'>u041au043eu043bu043eu043du043au0430 image_url u0443u0436u0435 u0441u0443u0449u0435u0441u0442u0432u0443u0435u0442 u0432 u0442u0430u0431u043bu0438u0446u0435 products.</div>";
    } else {
        echo "<div style='color: red; font-weight: bold;'>u041au043eu043bu043eu043du043au0430 image_url u043eu0442u0441u0443u0442u0441u0442u0432u0443u0435u0442 u0432 u0442u0430u0431u043bu0438u0446u0435 products.</div>";
        
        // u041fu0440u043eu0432u0435u0440u044fu0435u043c, u0435u0441u0442u044c u043bu0438 u043au043eu043bu043eu043du043au0430 u0441 u043fu043eu0445u043eu0436u0438u043c u043du0430u0437u0432u0430u043du0438u0435u043c
        $similarColumns = [];
        foreach ($columns as $column) {
            if (strpos(strtolower($column['Field']), 'image') !== false) {
                $similarColumns[] = $column['Field'];
            }
        }
        
        if (!empty($similarColumns)) {
            echo "<div>u041du0430u0439u0434u0435u043du044b u043fu043eu0445u043eu0436u0438u0435 u043au043eu043bu043eu043du043au0438: " . implode(", ", $similarColumns) . "</div>";
        }
        
        // u0424u043eu0440u043cu0430 u0434u043bu044f u0434u043eu0431u0430u0432u043bu0435u043du0438u044f u043au043eu043bu043eu043du043au0438
        echo "<h2>u0414u043eu0431u0430u0432u0438u0442u044c u043au043eu043bu043eu043du043au0443 image_url</h2>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='action' value='add_column'>";
        echo "<button type='submit' style='padding: 10px; background-color: #4CAF50; color: white; border: none; cursor: pointer;'>u0414u043eu0431u0430u0432u0438u0442u044c u043au043eu043bu043eu043du043au0443 image_url</button>";
        echo "</form>";
        
        // u041eu0431u0440u0430u0431u043eu0442u043au0430 u0444u043eu0440u043cu044b
        if (isset($_POST['action']) && $_POST['action'] === 'add_column') {
            try {
                // u0414u043eu0431u0430u0432u043bu044fu0435u043c u043au043eu043bu043eu043du043au0443 image_url
                $sql = "ALTER TABLE products ADD COLUMN image_url VARCHAR(255) DEFAULT NULL AFTER description";
                $statement = $app->db->prepare($sql);
                $result = $statement->execute();
                
                if ($result) {
                    echo "<div style='color: green; font-weight: bold; margin-top: 20px;'>u041au043eu043bu043eu043du043au0430 image_url u0443u0441u043fu0435u0448u043du043e u0434u043eu0431u0430u0432u043bu0435u043du0430 u0432 u0442u0430u0431u043bu0438u0446u0443 products!</div>";
                    
                    // u041eu0431u043du043eu0432u043bu044fu0435u043c u0441u0442u0440u0430u043du0438u0446u0443 u0447u0435u0440u0435u0437 3 u0441u0435u043au0443u043du0434u044b
                    echo "<script>setTimeout(function() { window.location.reload(); }, 3000);</script>";
                } else {
                    echo "<div style='color: red; font-weight: bold; margin-top: 20px;'>u041eu0448u0438u0431u043au0430 u043fu0440u0438 u0434u043eu0431u0430u0432u043bu0435u043du0438u0438 u043au043eu043bu043eu043du043au0438 image_url.</div>";
                }
            } catch (\Exception $e) {
                echo "<div style='color: red; font-weight: bold; margin-top: 20px;'>u041eu0448u0438u0431u043au0430: " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // u041fu0440u043eu0432u0435u0440u044fu0435u043c, u0435u0441u0442u044c u043bu0438 u0443 u043fu0440u043eu0434u0443u043au0442u043eu0432 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u044f
    if ($hasImageUrlColumn) {
        $statement = $app->db->prepare("SELECT id, product_name, image_url FROM products");
        $statement->execute();
        $products = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>u0421u043fu0438u0441u043eu043a u043fu0440u043eu0434u0443u043au0442u043eu0432 u0438 u0438u0445 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0439</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>u041du0430u0437u0432u0430u043du0438u0435</th><th>URL u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u044f</th><th>u0421u0442u0430u0442u0443u0441</th></tr>";
        
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['product_name']}</td>";
            echo "<td>{$product['image_url']}</td>";
            
            if (empty($product['image_url'])) {
                echo "<td style='color: red;'>u041eu0442u0441u0443u0442u0441u0442u0432u0443u0435u0442</td>";
            } else {
                $imagePath = $app->rootPath . '/public' . $product['image_url'];
                if (file_exists($imagePath)) {
                    echo "<td style='color: green;'>u0421u0443u0449u0435u0441u0442u0432u0443u0435u0442</td>";
                } else {
                    echo "<td style='color: orange;'>u0424u0430u0439u043b u043du0435 u043du0430u0439u0434u0435u043d</td>";
                }
            }
            
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // u0414u043eu0431u0430u0432u043bu044fu0435u043c u043au043du043eu043fu043au0443 u0434u043bu044f u0432u043eu0437u0432u0440u0430u0442u0430 u043du0430 u0441u0442u0440u0430u043du0438u0446u0443 u043fu0440u043eu0434u0443u043au0442u043eu0432
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='/seller/products' style='padding: 10px; background-color: #2196F3; color: white; text-decoration: none;'>u0412u0435u0440u043du0443u0442u044cu0441u044f u043a u043fu0440u043eu0434u0443u043au0442u0430u043c</a>";
    echo "</div>";
    
} catch (\Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>u041eu0448u0438u0431u043au0430 u043fu0440u0438 u0440u0430u0431u043eu0442u0435 u0441 u0431u0430u0437u043eu0439 u0434u0430u043du043du044bu0445: " . $e->getMessage() . "</div>";
}
