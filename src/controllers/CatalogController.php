<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Middleware\AuthMiddleware;
use PDO;

class CatalogController extends Controller {
    /**
     * Constructor - registers middleware for authentication
     */
    public function __construct() {
        parent::__construct();
        
        // Check if user is authenticated except for public pages
        $this->registerMiddleware(new AuthMiddleware(['index']));
    }
    
    /**
     * Display catalog page with products, categories and sellers
     * @return string Rendered view
     */
    public function index(): string {
        $this->view->title = 'Product Catalog';
        
        // Get all categories
        $categories = $this->getCategories();
        
        // Get all sellers
        $sellers = $this->getSellers();
        
        return $this->render('catalog/index', [
            'categories' => $categories,
            'sellers' => $sellers
        ]);
    }
    
    /**
     * API method for getting products with filters
     * @return void
     */
    public function getProducts(): void {
        $request = Application::$app->request;
        $response = Application::$app->response;
        
        // u041fu043eu043bu0443u0447u0430u0435u043c u0434u0430u043du043du044bu0435 u0438u0437 JSON-u0437u0430u043fu0440u043eu0441u0430
        $data = $request->isAjax() ? $request->getJson() : $request->getBody();
        
        // u0415u0441u043bu0438 u0434u0430u043du043du044bu0435 u043du0435 u043fu043eu043bu0443u0447u0435u043du0442u044b, u0438u0441u043fu043eu043bu044cu0437u0443u0435u043c u043fu0443u0441u0442u043eu0439 u043cu0430u0441u0441u0438u0432
        if ($data === null) {
            $data = [];
        }
        
        // u041eu0442u043bu0430u0434u043eu0447u043du0430u044f u0438u043du0444u043eu0440u043cu0430u0446u0438u044f
        Application::$app->logger->info(
            'Request type and data', 
            [
                'is_ajax' => $request->isAjax(),
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
                'raw_data' => file_get_contents('php://input'),
                'processed_data' => $data
            ],
            'errors.log'
        );
        
        // Filtering parameters
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 25;
        $offset = ($page - 1) * $limit;
        
        // Log request data for debugging
        Application::$app->logger->info(
            'Product filter request data', 
            ['data' => $data],
            'errors.log'
        );
        
        // Filter by category
        $categoryId = null;
        if (isset($data['category_id']) && !empty($data['category_id']) && $data['category_id'] !== '') {
            $categoryId = (int)$data['category_id'];
            // Добавляем отладочную информацию
            Application::$app->logger->info(
                'Category filter applied', 
                ['category_id' => $categoryId, 'raw_value' => $data['category_id']],
                'errors.log'
            );
        }
        
        $sellerId = null;
        if (isset($data['seller_id']) && !empty($data['seller_id']) && $data['seller_id'] !== '') {
            $sellerId = (int)$data['seller_id'];
        }
        
        $availability = isset($data['availability']) ? $data['availability'] : 'all'; // 'all', 'in_stock', 'preorder'
        
        Application::$app->logger->info(
            'Applied filters', 
            ['category_id' => $categoryId, 'seller_id' => $sellerId, 'availability' => $availability],
            'errors.log'
        );
        
        // Create SQL query with filters
        $sql = "SELECT p.*, c.name as category_name, sp.name as seller_name, 
                    (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image,
                    0 as avg_rating,
                    0 as rating_count,
                    p.quantity as quantity
               FROM products p
               LEFT JOIN categories c ON p.category_id = c.id
               LEFT JOIN seller_profiles sp ON p.seller_profile_id = sp.id
               WHERE 1=1";
        
        $params = [];
        
        // Add category filter
        if ($categoryId) {
            $sql .= " AND p.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }
        
        // Add seller filter
        if ($sellerId) {
            $sql .= " AND p.seller_profile_id = :seller_id";
            $params['seller_id'] = $sellerId;
        }
        
        // Add availability filter
        if ($availability === 'in_stock') {
            $sql .= " AND p.is_active = 1";
        } else if ($availability === 'preorder') {
            $sql .= " AND p.available_for_preorder = 1";
        } else {
            // 'all' - do not add any additional conditions
            $sql .= " AND (p.is_active = 1 OR p.available_for_preorder = 1)";
        }
        
        // Add limit and offset
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        // Create SQL query for counting total products
        $countSql = "SELECT COUNT(*) FROM products p WHERE 1=1";
        
        // Apply filters to count query
        if ($categoryId) {
            $countSql .= " AND p.category_id = :category_id";
        }
        
        if ($sellerId) {
            $countSql .= " AND p.seller_profile_id = :seller_id";
        }
        
        if ($availability === 'in_stock') {
            $countSql .= " AND p.is_active = 1";
        } else if ($availability === 'preorder') {
            $countSql .= " AND p.available_for_preorder = 1";
        } else {
            $countSql .= " AND (p.is_active = 1 OR p.available_for_preorder = 1)";
        }
        
        try {
            // Prepare and execute SQL query
            $statement = Application::$app->db->prepare($sql);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                if ($key === 'limit' || $key === 'offset') {
                    $statement->bindValue(":$key", $value, PDO::PARAM_INT);
                } else {
                    $statement->bindValue(":$key", $value);
                }
            }
            
            $statement->execute();
            $products = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            // Prepare and execute count query
            $countStatement = Application::$app->db->prepare($countSql);
            
            // Bind parameters for count query
            if ($categoryId) {
                $countStatement->bindValue(":category_id", $categoryId, PDO::PARAM_INT);
            }
            
            if ($sellerId) {
                $countStatement->bindValue(":seller_id", $sellerId, PDO::PARAM_INT);
            }
            
            $countStatement->execute();
            $totalCount = $countStatement->fetchColumn();
            
            // Process products
            foreach ($products as &$product) {
                // Set default image if not found
                if (empty($product['main_image'])) {
                    $product['main_image'] = '/assets/images/default-products.svg';
                }
                
                // Round average rating - ensure it's a float before rounding
                $product['avg_rating'] = !empty($product['avg_rating']) ? 
                    round((float)$product['avg_rating'], 1) : 0.0;
                
                // Set availability flags
                $product['available'] = $product['is_active'] == 1;
                $product['preorder'] = $product['available_for_preorder'] == 1;
                
                // u041fu043eu043bu0443u0447u0430u0435u043c u0438u043du0433u0440u0435u0434u0438u0435u043du0442u044b u0434u043bu044fu043eu0440u043eu0434u0443u043au0442u0430
                $ingredientsSql = "SELECT bi.id, bi.name, bi.category, bi.kosher, bi.allergen 
                                   FROM product_ingredients pi
                                   JOIN base_ingredients bi ON pi.ingredient_name COLLATE utf8mb4_unicode_ci = bi.name COLLATE utf8mb4_unicode_ci
                                   WHERE pi.product_id = :product_id
                                   ORDER BY bi.category, bi.name";
                
                $ingredientsStmt = Application::$app->db->prepare($ingredientsSql);
                $ingredientsStmt->execute(['product_id' => $product['id']]);
                $ingredients = $ingredientsStmt->fetchAll(\PDO::FETCH_ASSOC);
                
                // u0414u043eu0431u0430u0432u043bu044fu0435u043c u0438u043du0433u0440u0435u0434u0438u0435u043du0442u044b u0432 u043fu0440u043eu0434u0443u043au0442
                $product['ingredients'] = $ingredients;
            }
            
            // Add debug information
            $debugInfo = [
                'original_request' => $data,
                'processed_category_id' => $categoryId,
                'processed_seller_id' => $sellerId,
                'sql_params' => $params,
                'sql_query' => $sql
            ];
            
            // Form response
            $responseData = [
                'products' => $products,
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'has_more' => $totalCount > ($page * $limit),
                'debug' => $debugInfo
            ];
            
            $response->json($responseData, 200);
        } catch (\Exception $e) {
            // Log error to errors.log
            Application::$app->logger->error(
                'Error occurred while getting products: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString(), 'filters' => $params],
                'errors.log'
            );
            
            // Return error response
            $response->json(['error' => 'Failed to fetch products', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get all categories for filtering
     * @return array List of categories
     */
    private function getCategories(): array {
        try {
            // Removed is_active filter since the column doesn't exist in the database
            $sql = "SELECT id, name, description FROM categories ORDER BY name ASC";
            $statement = Application::$app->db->prepare($sql);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Log error to errors.log
            Application::$app->logger->error(
                'Error fetching categories: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return [];
        }
    }
    
    /**
     * Get all sellers for filtering
     * @return array List of sellers
     */
    private function getSellers(): array {
        try {
            // Removed is_active filter since the column doesn't exist in the database
            $sql = "SELECT sp.id, sp.name, sp.description, u.email 
                   FROM seller_profiles sp 
                   JOIN users u ON sp.user_id = u.id 
                   ORDER BY sp.name ASC";
            $statement = Application::$app->db->prepare($sql);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Log error to errors.log
            Application::$app->logger->error(
                'Error fetching sellers: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return [];
        }
    }
}
