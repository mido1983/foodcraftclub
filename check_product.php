<?php

try {
    $db = new PDO('mysql:host=127.0.0.1;dbname=foodcraftclub;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check users table schema
    $stmt = $db->prepare("DESCRIBE users");
    $stmt->execute();
    $usersSchema = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Users table schema:\n";
    foreach ($usersSchema as $column) {
        echo $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check products table schema
    $stmt = $db->prepare("DESCRIBE products");
    $stmt->execute();
    $productsSchema = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nProducts table schema:\n";
    foreach ($productsSchema as $column) {
        echo $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check seller_profiles table schema
    $stmt = $db->prepare("DESCRIBE seller_profiles");
    $stmt->execute();
    $sellerProfilesSchema = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nSeller Profiles table schema:\n";
    foreach ($sellerProfilesSchema as $column) {
        echo $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Now query with the correct column names
    $stmt = $db->prepare(
        "SELECT p.id, p.product_name, p.description, sp.user_id, u.email 
         FROM products p 
         JOIN seller_profiles sp ON p.seller_profile_id = sp.id 
         JOIN users u ON sp.user_id = u.id 
         WHERE p.id = 12"
    );
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nProduct information:\n";
    print_r($product);
    
    // Check user 16 information
    $stmt = $db->prepare("SELECT * FROM users WHERE id = 16");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nUser information:\n";
    print_r($user);
    
    // Check all products owned by user 16
    $stmt = $db->prepare(
        "SELECT p.id, p.product_name 
         FROM products p 
         JOIN seller_profiles sp ON p.seller_profile_id = sp.id 
         WHERE sp.user_id = 16"
    );
    $stmt->execute();
    $userProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nProducts owned by user 16:\n";
    print_r($userProducts);
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
