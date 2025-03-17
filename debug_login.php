<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Connect to database
try {
    $dsn = "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}";
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n";
    
    // Check if the users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $usersTableExists = $stmt->rowCount() > 0;
    
    if (!$usersTableExists) {
        echo "ERROR: The 'users' table does not exist!\n";
        exit(1);
    }
    
    echo "The 'users' table exists.\n";
    
    // Check the structure of the users table
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nUsers table structure:\n";
    foreach ($columns as $column) {
        echo "{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}\n";
    }
    
    // Check if the admin user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => 'michael.doroshenko1@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "\nERROR: Admin user 'michael.doroshenko1@gmail.com' does not exist!\n";
        
        // Create the admin user
        echo "\nCreating admin user...\n";
        
        // Check if the roles table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'roles'");
        $rolesTableExists = $stmt->rowCount() > 0;
        
        if (!$rolesTableExists) {
            echo "Creating roles table...\n";
            $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                description VARCHAR(255)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            echo "Inserting default roles...\n";
            $pdo->exec("INSERT INTO roles (id, name, description) VALUES
                (1, 'admin', 'Administrator with full access'),
                (2, 'seller', 'Food craft seller'),
                (3, 'client', 'Regular client user')");
        }
        
        // Check if the user_roles table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_roles'");
        $userRolesTableExists = $stmt->rowCount() > 0;
        
        if (!$userRolesTableExists) {
            echo "Creating user_roles table...\n";
            $pdo->exec("CREATE TABLE IF NOT EXISTS user_roles (
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                PRIMARY KEY (user_id, role_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
        
        // Create the admin user
        $passwordHash = password_hash('Admin123!', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, status) VALUES (:email, :password_hash, :full_name, :status)");
        $stmt->execute([
            'email' => 'michael.doroshenko1@gmail.com',
            'password_hash' => $passwordHash,
            'full_name' => 'Michael Dor',
            'status' => 'active'
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Assign admin role
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
        $stmt->execute([
            'user_id' => $userId,
            'role_id' => 1 // Admin role
        ]);
        
        echo "Admin user created successfully!\n";
        echo "Email: michael.doroshenko1@gmail.com\n";
        echo "Password: Admin123!\n";
    } else {
        echo "\nAdmin user exists:\n";
        echo "ID: {$user['id']}\n";
        echo "Email: {$user['email']}\n";
        echo "Full Name: {$user['full_name']}\n";
        echo "Status: {$user['status']}\n";
        
        // Check if password verification works
        $passwordVerified = password_verify('Admin123!', $user['password_hash']);
        echo "Password verification: " . ($passwordVerified ? "SUCCESS" : "FAILED") . "\n";
        
        if (!$passwordVerified) {
            echo "\nWARNING: Password verification failed. Updating password...\n";
            $passwordHash = password_hash('Admin123!', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
            $stmt->execute([
                'password_hash' => $passwordHash,
                'id' => $user['id']
            ]);
            echo "Password updated successfully!\n";
        }
        
        // Check user roles
        $stmt = $pdo->prepare("SELECT r.* FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = :user_id");
        $stmt->execute(['user_id' => $user['id']]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nUser roles:\n";
        if (empty($roles)) {
            echo "No roles assigned!\n";
            echo "Assigning admin role...\n";
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
            $stmt->execute([
                'user_id' => $user['id'],
                'role_id' => 1 // Admin role
            ]);
            echo "Admin role assigned successfully!\n";
        } else {
            foreach ($roles as $role) {
                echo "{$role['id']} - {$role['name']}\n";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
