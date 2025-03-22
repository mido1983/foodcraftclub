<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Application;

try {
    $db = Application::$app->db;
    
    // Check if cities table is empty
    $citiesCheck = $db->prepare("SELECT COUNT(*) as count FROM cities");
    $citiesCheck->execute();
    $citiesCount = $citiesCheck->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Check if districts table is empty
    $districtsCheck = $db->prepare("SELECT COUNT(*) as count FROM districts");
    $districtsCheck->execute();
    $districtsCount = $districtsCheck->fetch(PDO::FETCH_ASSOC)['count'];
    
    // If cities table is empty, add sample cities
    if ($citiesCount == 0) {
        echo "Adding sample cities...\n";
        
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
            echo "Adding 'name' column to cities table...\n";
            $db->exec("ALTER TABLE cities ADD COLUMN name VARCHAR(100) NOT NULL");
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
        
        foreach ($cities as $city) {
            $cityInsert->execute(['name' => $city['name']]);
            echo "Added city: {$city['name']}\n";
        }
    } else {
        echo "Cities table already has data. Skipping...\n";
    }
    
    // If districts table is empty, add sample districts
    if ($districtsCount == 0) {
        echo "Adding sample districts...\n";
        
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
            echo "Adding 'name' column to districts table...\n";
            $db->exec("ALTER TABLE districts ADD COLUMN name VARCHAR(100) NOT NULL");
        }
        
        // If city_id column doesn't exist, add it
        if (!$hasCityIdColumn) {
            echo "Adding 'city_id' column to districts table...\n";
            $db->exec("ALTER TABLE districts ADD COLUMN city_id INT NOT NULL");
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
        
        foreach ($districts as $district) {
            if (isset($cityMap[$district['city']])) {
                $districtInsert->execute([
                    'name' => $district['name'],
                    'city_id' => $cityMap[$district['city']]
                ]);
                echo "Added district: {$district['name']} (City: {$district['city']})\n";
            } else {
                echo "Warning: City '{$district['city']}' not found. Skipping district '{$district['name']}'\n";
            }
        }
    } else {
        echo "Districts table already has data. Skipping...\n";
    }
    
    echo "\nSample data added successfully!\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
