<?php

try {
    // Connect to database
    $db = new PDO('mysql:host=127.0.0.1;dbname=foodcraftclub;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check categories table structure
    $stmt = $db->query('DESCRIBE categories');
    $categories_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Categories table structure:\n";
    print_r($categories_structure);
    
    // Get all categories
    $stmt = $db->query('SELECT * FROM categories');
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nCategories data:\n";
    print_r($categories);
    
    // Check products table structure for category_id field
    $stmt = $db->query('DESCRIBE products');
    $products_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nProducts table structure:\n";
    foreach ($products_structure as $field) {
        if ($field['Field'] === 'category_id') {
            print_r($field);
        }
    }
    
    // Check for any product with invalid category_id
    $stmt = $db->query('SELECT p.id, p.product_name, p.category_id FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id IS NOT NULL AND c.id IS NULL');
    $invalid_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nProducts with invalid category_id:\n";
    print_r($invalid_products);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
