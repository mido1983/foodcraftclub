<?php

try {
    // Connect to database
    $db = new PDO('mysql:host=127.0.0.1;dbname=foodcraftclub;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check for foreign key constraints on products table
    $stmt = $db->query("SELECT * FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE REFERENCED_TABLE_NAME = 'categories' 
                        AND TABLE_NAME = 'products'");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Foreign key constraints on products.category_id:\n";
    print_r($constraints);
    
    // Check if there's a problem with how category_id is being set in the updateProduct method
    // Let's simulate the update process with a test product
    $testProductId = 1; // Change this to a real product ID if needed
    
    // Get the current product data
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$testProductId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "\nTest product data:\n";
        print_r($product);
        
        // Get valid category IDs
        $stmt = $db->query("SELECT id FROM categories");
        $validCategoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "\nValid category IDs: " . implode(", ", $validCategoryIds) . "\n";
        
        // Check if the current category_id is valid
        if ($product['category_id'] !== null) {
            $isValid = in_array($product['category_id'], $validCategoryIds);
            echo "Current category_id {$product['category_id']} is " . ($isValid ? "valid" : "invalid") . "\n";
        } else {
            echo "Current category_id is NULL\n";
        }
        
        // Check if the category_id is being cast correctly
        $testCategoryId = $validCategoryIds[0] ?? null;
        echo "\nTesting category_id casting:\n";
        echo "Original value: " . var_export($testCategoryId, true) . "\n";
        echo "After intval(): " . var_export(intval($testCategoryId), true) . "\n";
        
        // Check if there's a type mismatch between the columns
        $stmt = $db->query("SHOW COLUMNS FROM categories WHERE Field = 'id'");
        $categoryIdColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $db->query("SHOW COLUMNS FROM products WHERE Field = 'category_id'");
        $productCategoryIdColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\nColumn types:\n";
        echo "categories.id: " . $categoryIdColumn['Type'] . "\n";
        echo "products.category_id: " . $productCategoryIdColumn['Type'] . "\n";
    } else {
        echo "\nNo product found with ID $testProductId\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
