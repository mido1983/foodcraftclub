<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>Setup Delivery Areas</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    .container { max-width: 800px; margin: 0 auto; }
    h1 { color: #333; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow: auto; }
    .btn { display: inline-block; background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; margin-top: 20px; }
</style>
</head><body>
<div class='container'>
<h1>Setup Delivery Areas</h1>";

try {
    $db = Application::$app->db;
    
    echo "<h2>Checking Database Tables</h2>";
    
    // Check if cities table is empty
    $citiesCheck = $db->prepare("SELECT COUNT(*) as count FROM cities");
    $citiesCheck->execute();
    $citiesCount = $citiesCheck->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p>Cities table has {$citiesCount} records.</p>";
    
    // Check if districts table is empty
    $districtsCheck = $db->prepare("SELECT COUNT(*) as count FROM districts");
    $districtsCheck->execute();
    $districtsCount = $districtsCheck->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p>Districts table has {$districtsCount} records.</p>";
    
    // If cities table is empty, add sample cities
    if ($citiesCount == 0) {
        echo "<h2>Adding Sample Cities</h2>";
        
        // First check the structure of the cities table
        $columnsCheck = $db->prepare("DESCRIBE cities");
        $columnsCheck->execute();
        $columns = $columnsCheck->fetchAll(PDO::FETCH_ASSOC);
        
        $hasNameColumn = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'name') {
                $hasNameColumn = true;
                break;
            }
        }
        
        // If name column doesn't exist, add it
        if (!$hasNameColumn) {
            echo "<p class='warning'>Adding 'name' column to cities table...</p>";
            $db->exec("ALTER TABLE cities ADD COLUMN name VARCHAR(100) NOT NULL");
            echo "<p class='success'>Column 'name' added to cities table.</p>";
        } else {
            echo "<p>Column 'name' already exists in cities table.</p>";
        }
        
        // Add sample cities
        $cities = [
            ['name' => 'Kyiv'],
            ['name' => 'Lviv'],
            ['name' => 'Odesa'],
            ['name' => 'Kharkiv'],
            ['name' => 'Dnipro']
        ];
        
        $cityInsert = $db->prepare("INSERT INTO cities (name) VALUES (:name)");
        
        echo "<ul>";
        foreach ($cities as $city) {
            $cityInsert->execute(['name' => $city['name']]);
            echo "<li class='success'>Added city: {$city['name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='warning'>Cities table already has data. Skipping...</p>";
    }
    
    // If districts table is empty, add sample districts
    if ($districtsCount == 0) {
        echo "<h2>Adding Sample Districts</h2>";
        
        // First check the structure of the districts table
        $columnsCheck = $db->prepare("DESCRIBE districts");
        $columnsCheck->execute();
        $columns = $columnsCheck->fetchAll(PDO::FETCH_ASSOC);
        
        $hasNameColumn = false;
        $hasCityIdColumn = false;
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'name') {
                $hasNameColumn = true;
            }
            if ($column['Field'] === 'city_id') {
                $hasCityIdColumn = true;
            }
        }
        
        // If name column doesn't exist, add it
        if (!$hasNameColumn) {
            echo "<p class='warning'>Adding 'name' column to districts table...</p>";
            $db->exec("ALTER TABLE districts ADD COLUMN name VARCHAR(100) NOT NULL");
            echo "<p class='success'>Column 'name' added to districts table.</p>";
        } else {
            echo "<p>Column 'name' already exists in districts table.</p>";
        }
        
        // If city_id column doesn't exist, add it
        if (!$hasCityIdColumn) {
            echo "<p class='warning'>Adding 'city_id' column to districts table...</p>";
            $db->exec("ALTER TABLE districts ADD COLUMN city_id INT NOT NULL");
            echo "<p class='success'>Column 'city_id' added to districts table.</p>";
        } else {
            echo "<p>Column 'city_id' already exists in districts table.</p>";
        }
        
        // Get city IDs
        $citiesQuery = $db->prepare("SELECT id, name FROM cities");
        $citiesQuery->execute();
        $cities = $citiesQuery->fetchAll(PDO::FETCH_ASSOC);
        
        $cityMap = [];
        foreach ($cities as $city) {
            $cityMap[$city['name']] = $city['id'];
        }
        
        // Add sample districts for each city
        $districts = [
            // Kyiv districts
            ['name' => 'Shevchenkivskyi', 'city' => 'Kyiv'],
            ['name' => 'Pecherskyi', 'city' => 'Kyiv'],
            ['name' => 'Obolonskyi', 'city' => 'Kyiv'],
            ['name' => 'Podilskyi', 'city' => 'Kyiv'],
            ['name' => 'Solomianskyi', 'city' => 'Kyiv'],
            
            // Lviv districts
            ['name' => 'Halytskyi', 'city' => 'Lviv'],
            ['name' => 'Lychakivskyi', 'city' => 'Lviv'],
            ['name' => 'Sykhivskyi', 'city' => 'Lviv'],
            
            // Odesa districts
            ['name' => 'Prymorskyi', 'city' => 'Odesa'],
            ['name' => 'Kyivskyi', 'city' => 'Odesa'],
            ['name' => 'Malinovskyi', 'city' => 'Odesa'],
            
            // Kharkiv districts
            ['name' => 'Shevchenkivskyi', 'city' => 'Kharkiv'],
            ['name' => 'Kyivskyi', 'city' => 'Kharkiv'],
            ['name' => 'Saltivskyi', 'city' => 'Kharkiv'],
            
            // Dnipro districts
            ['name' => 'Tsentralnyi', 'city' => 'Dnipro'],
            ['name' => 'Chechelivskyi', 'city' => 'Dnipro'],
            ['name' => 'Sobornyi', 'city' => 'Dnipro']
        ];
        
        $districtInsert = $db->prepare("INSERT INTO districts (name, city_id) VALUES (:name, :city_id)");
        
        echo "<ul>";
        foreach ($districts as $district) {
            if (isset($cityMap[$district['city']])) {
                $districtInsert->execute([
                    'name' => $district['name'],
                    'city_id' => $cityMap[$district['city']]
                ]);
                echo "<li class='success'>Added district: {$district['name']} (City: {$district['city']})</li>";
            } else {
                echo "<li class='error'>Warning: City '{$district['city']}' not found. Skipping district '{$district['name']}'</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p class='warning'>Districts table already has data. Skipping...</p>";
    }
    
    echo "<h2 class='success'>Setup Complete!</h2>";
    echo "<p>You can now go back to the <a href='/seller/delivery-areas' class='btn'>Delivery Areas</a> page to manage your delivery areas.</p>";
    
} catch (\Exception $e) {
    echo "<h2 class='error'>Error</h2>";
    echo "<p class='error'>{$e->getMessage()}</p>";
    echo "<pre>{$e->getTraceAsString()}</pre>";
}

echo "</div></body></html>";
