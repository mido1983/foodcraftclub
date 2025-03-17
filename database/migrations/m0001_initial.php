<?php

class m0001_initial {
    public function up() {
        $db = \App\Core\Application::$app->db;
        
        // 1. Roles
        $SQL = "CREATE TABLE IF NOT EXISTS roles (
            id TINYINT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // Insert default roles
        $SQL = "INSERT INTO roles (name) VALUES 
            ('admin'), ('seller'), ('client')
            ON DUPLICATE KEY UPDATE name=VALUES(name);";
        $db->prepare($SQL)->execute();

        // 2. Users
        $SQL = "CREATE TABLE IF NOT EXISTS users (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(255),
            status ENUM('active','pending','suspended') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 3. User Roles (many-to-many)
        $SQL = "CREATE TABLE IF NOT EXISTS user_roles (
            user_id BIGINT NOT NULL,
            role_id TINYINT NOT NULL,
            PRIMARY KEY (user_id, role_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 4. Membership Fees
        $SQL = "CREATE TABLE IF NOT EXISTS membership_fees (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            fee_amount DECIMAL(10,2) NOT NULL,
            payment_status ENUM('paid','pending','failed') NOT NULL DEFAULT 'pending',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 5. Cities
        $SQL = "CREATE TABLE IF NOT EXISTS cities (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            city_name VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 6. Districts
        $SQL = "CREATE TABLE IF NOT EXISTS districts (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            city_id BIGINT NOT NULL,
            district_name VARCHAR(100) NOT NULL,
            FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 7. Seller Profiles
        $SQL = "CREATE TABLE IF NOT EXISTS seller_profiles (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NOT NULL UNIQUE,
            seller_type ENUM('ordinary','vip','premium') NOT NULL DEFAULT 'ordinary',
            min_order_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 8. Seller Delivery Areas
        $SQL = "CREATE TABLE IF NOT EXISTS seller_delivery_areas (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            seller_profile_id BIGINT NOT NULL,
            city_id BIGINT NOT NULL,
            district_id BIGINT,
            delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
            free_from_amount DECIMAL(10,2),
            FOREIGN KEY (seller_profile_id) REFERENCES seller_profiles(id) ON DELETE CASCADE,
            FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
            FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 9. Payment Methods
        $SQL = "CREATE TABLE IF NOT EXISTS payment_methods (
            id TINYINT PRIMARY KEY AUTO_INCREMENT,
            method_code VARCHAR(50) NOT NULL UNIQUE,
            method_name VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // Insert default payment methods
        $SQL = "INSERT INTO payment_methods (method_code, method_name) VALUES 
            ('CASH', 'Cash on Delivery'),
            ('CARD', 'Credit/Debit Card'),
            ('BANK', 'Bank Transfer')
            ON DUPLICATE KEY UPDATE method_name=VALUES(method_name);";
        $db->prepare($SQL)->execute();

        // 10. Seller Payment Options
        $SQL = "CREATE TABLE IF NOT EXISTS seller_payment_options (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            seller_profile_id BIGINT NOT NULL,
            payment_method_id TINYINT NOT NULL,
            enabled BOOLEAN NOT NULL DEFAULT TRUE,
            FOREIGN KEY (seller_profile_id) REFERENCES seller_profiles(id) ON DELETE CASCADE,
            FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 11. Products
        $SQL = "CREATE TABLE IF NOT EXISTS products (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            seller_profile_id BIGINT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            available_for_preorder BOOLEAN NOT NULL DEFAULT FALSE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_profile_id) REFERENCES seller_profiles(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 12. Product Images
        $SQL = "CREATE TABLE IF NOT EXISTS product_images (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            product_id BIGINT NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            is_main BOOLEAN NOT NULL DEFAULT FALSE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 13. Orders
        $SQL = "CREATE TABLE IF NOT EXISTS orders (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            buyer_id BIGINT NOT NULL,
            seller_profile_id BIGINT NOT NULL,
            order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('new','processing','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'new',
            payment_status ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
            payment_method_id TINYINT,
            city_id BIGINT,
            district_id BIGINT,
            address_line VARCHAR(255),
            delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
            FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (seller_profile_id) REFERENCES seller_profiles(id) ON DELETE CASCADE,
            FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
            FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL,
            FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 14. Order Items
        $SQL = "CREATE TABLE IF NOT EXISTS order_items (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            order_id BIGINT NOT NULL,
            product_id BIGINT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price_at_moment DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 15. Payments
        $SQL = "CREATE TABLE IF NOT EXISTS payments (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            membership_fee_id BIGINT,
            order_id BIGINT,
            amount DECIMAL(10,2) NOT NULL,
            payment_method_id TINYINT,
            payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            transaction_id VARCHAR(255),
            payment_status ENUM('completed','failed','pending') NOT NULL DEFAULT 'pending',
            payment_type ENUM('membership','order') NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (membership_fee_id) REFERENCES membership_fees(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 16. Conversations
        $SQL = "CREATE TABLE IF NOT EXISTS conversations (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 17. Conversation Participants
        $SQL = "CREATE TABLE IF NOT EXISTS conversation_participants (
            conversation_id BIGINT NOT NULL,
            user_id BIGINT NOT NULL,
            PRIMARY KEY (conversation_id, user_id),
            FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 18. Messages
        $SQL = "CREATE TABLE IF NOT EXISTS messages (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            conversation_id BIGINT NOT NULL,
            sender_id BIGINT NOT NULL,
            message_text TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();

        // 19. Notifications
        $SQL = "CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            notification_type VARCHAR(50),
            content TEXT,
            is_read BOOLEAN NOT NULL DEFAULT FALSE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->prepare($SQL)->execute();
    }
}
