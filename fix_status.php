<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;
use App\Models\User;

// u0418u043du0438u0446u0438u0430u043bu0438u0437u0430u0446u0438u044f u043fu0440u0438u043bu043eu0436u0435u043du0438u044f
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$config = [
    'db' => [
        'dsn' => $_ENV['DB_DSN'],
        'user' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD'],
    ]
];

$app = new Application(__DIR__, $config);
$db = Application::$app->db;

// u041fu0440u044fu043cu043eu0435 u043eu0431u043du043eu0432u043bu0435u043du0438u0435 u0441u0442u0430u0442u0443u0441u0430 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu044f
if (isset($_GET['id']) && isset($_GET['status'])) {
    $userId = (int)$_GET['id'];
    $newStatus = $_GET['status'];
    
    if (in_array($newStatus, ['active', 'pending', 'suspended'])) {
        // u041fu043eu043bu0443u0447u0430u0435u043c u0442u0435u043au0443u0449u0438u0439 u0441u0442u0430u0442u0443u0441
        $stmt = $db->prepare("SELECT status FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $currentStatus = $stmt->fetchColumn();
        
        // u041eu0431u043du043eu0432u043bu044fu0435u043c u0441u0442u0430u0442u0443u0441
        $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
        $result = $stmt->execute([
            'id' => $userId,
            'status' => $newStatus
        ]);
        
        // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0440u0435u0437u0443u043bu044cu0442u0430u0442
        $stmt = $db->prepare("SELECT status FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $updatedStatus = $stmt->fetchColumn();
        
        echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px;'>";
        echo "<h1>u041eu0431u043du043eu0432u043bu0435u043du0438u0435 u0441u0442u0430u0442u0443u0441u0430 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu044f</h1>";
        
        if ($result) {
            echo "<div style='padding: 10px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 15px;'>";
            echo "<p><strong>u0421u0442u0430u0442u0443u0441 u0443u0441u043fu0435u0448u043du043e u043eu0431u043du043eu0432u043bu0435u043d!</strong></p>";
            echo "<p>u041fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu044c ID: $userId</p>";
            echo "<p>u0421u0442u0430u0440u044bu0439 u0441u0442u0430u0442u0443u0441: $currentStatus</p>";
            echo "<p>u041du043eu0432u044bu0439 u0441u0442u0430u0442u0443u0441: $updatedStatus</p>";
            echo "</div>";
        } else {
            echo "<div style='padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;'>";
            echo "<p><strong>u041eu0448u0438u0431u043au0430 u043fu0440u0438 u043eu0431u043du043eu0432u043bu0435u043du0438u0438 u0441u0442u0430u0442u0443u0441u0430</strong></p>";
            echo "</div>";
        }
        
        // u0414u043eu0431u0430u0432u043bu044fu0435u043c u0444u043eu0440u043cu0443 u0434u043bu044f u0438u0437u043cu0435u043du0435u043du0438u044f u0441u0442u0430u0442u0443u0441u0430
        echo "<div style='margin-top: 20px;'>";
        echo "<h2>u0418u0437u043cu0435u043du0438u0442u044c u0441u0442u0430u0442u0443u0441 u0441u043du043eu0432u0430</h2>";
        echo "<form method='get' action=''>";
        echo "<input type='hidden' name='id' value='$userId'>";
        echo "<div style='margin-bottom: 15px;'>";
        echo "<label style='display: block; margin-bottom: 5px;'>u0412u044bu0431u0435u0440u0438u0442u0435 u0441u0442u0430u0442u0443u0441:</label>";
        echo "<select name='status' style='padding: 8px; width: 100%; border: 1px solid #ced4da; border-radius: 4px;'>";
        echo "<option value='active' ".($updatedStatus === 'active' ? 'selected' : '').">Active (u0410u043au0442u0438u0432u043du044bu0439)</option>";
        echo "<option value='pending' ".($updatedStatus === 'pending' ? 'selected' : '').">Pending (u041eu0436u0438u0434u0430u044eu0449u0438u0439)</option>";
        echo "<option value='suspended' ".($updatedStatus === 'suspended' ? 'selected' : '').">Suspended (u0417u0430u0431u043bu043eu043au0438u0440u043eu0432u0430u043du043du044bu0439)</option>";
        echo "</select>";
        echo "</div>";
        echo "<button type='submit' style='background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;'>u0418u0437u043cu0435u043du0438u0442u044c u0441u0442u0430u0442u0443u0441</button>";
        echo "</form>";
        echo "</div>";
        
        // u0414u043eu0431u0430u0432u043bu044fu0435u043c u0441u0441u044bu043bu043au0443 u043du0430 u0430u0434u043cu0438u043du043au0443
        echo "<div style='margin-top: 20px;'>";
        echo "<a href='/admin/users' style='display: inline-block; padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>u0412u0435u0440u043du0443u0442u044cu0441u044f u0432 u0430u0434u043cu0438u043du043au0443</a>";
        echo "</div>";
        
        echo "</div>";
    } else {
        echo "<h1>u041du0435u0434u043eu043fu0443u0441u0442u0438u043cu044bu0439 u0441u0442u0430u0442u0443u0441</h1>";
        echo "<p>u0414u043eu043fu0443u0441u0442u0438u043cu044bu0435 u0441u0442u0430u0442u0443u0441u044b: active, pending, suspended</p>";
    }
} else {
    // u041fu043eu043bu0443u0447u0430u0435u043c u0441u043fu0438u0441u043eu043a u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu0435u0439
    $stmt = $db->prepare("SELECT id, email, full_name, status FROM users ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px;'>";
    echo "<h1>u0423u043fu0440u0430u0432u043bu0435u043du0438u0435 u0441u0442u0430u0442u0443u0441u0430u043cu0438 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu0435u0439</h1>";
    
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;'>ID</th>";
    echo "<th style='padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;'>Email</th>";
    echo "<th style='padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;'>u0418u043cu044f</th>";
    echo "<th style='padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;'>u0421u0442u0430u0442u0443u0441</th>";
    echo "<th style='padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;'>u0414u0435u0439u0441u0442u0432u0438u044f</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        $statusClass = '';
        $statusText = '';
        
        switch ($user['status']) {
            case 'active':
                $statusClass = 'background-color: #d4edda; color: #155724;';
                $statusText = 'u0410u043au0442u0438u0432u043du044bu0439';
                break;
            case 'pending':
                $statusClass = 'background-color: #fff3cd; color: #856404;';
                $statusText = 'u041eu0436u0438u0434u0430u044eu0449u0438u0439';
                break;
            case 'suspended':
                $statusClass = 'background-color: #f8d7da; color: #721c24;';
                $statusText = 'u0417u0430u0431u043bu043eu043au0438u0440u043eu0432u0430u043du043du044bu0439';
                break;
            default:
                $statusClass = 'background-color: #e2e3e5; color: #383d41;';
                $statusText = $user['status'];
        }
        
        echo "<tr>";
        echo "<td style='padding: 10px; border-bottom: 1px solid #dee2e6;'>{$user['id']}</td>";
        echo "<td style='padding: 10px; border-bottom: 1px solid #dee2e6;'>{$user['email']}</td>";
        echo "<td style='padding: 10px; border-bottom: 1px solid #dee2e6;'>{$user['full_name']}</td>";
        echo "<td style='padding: 10px; border-bottom: 1px solid #dee2e6;'><span style='padding: 3px 8px; border-radius: 4px; $statusClass'>$statusText</span></td>";
        echo "<td style='padding: 10px; border-bottom: 1px solid #dee2e6;'>";
        echo "<a href='?id={$user['id']}&status=active' style='margin-right: 5px; padding: 5px 10px; background-color: #28a745; color: white; text-decoration: none; border-radius: 3px;'>u0410u043au0442u0438u0432u043du044bu0439</a>";
        echo "<a href='?id={$user['id']}&status=pending' style='margin-right: 5px; padding: 5px 10px; background-color: #ffc107; color: #212529; text-decoration: none; border-radius: 3px;'>u041eu0436u0438u0434u0430u044eu0449u0438u0439</a>";
        echo "<a href='?id={$user['id']}&status=suspended' style='padding: 5px 10px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 3px;'>u0417u0430u0431u043bu043eu043au0438u0440u043eu0432u0430u043du043du044bu0439</a>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // u0414u043eu0431u0430u0432u043bu044fu0435u043c u0441u0441u044bu043bu043au0443 u043du0430 u0430u0434u043cu0438u043du043au0443
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='/admin/users' style='display: inline-block; padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>u0412u0435u0440u043du0443u0442u044cu0441u044f u0432 u0430u0434u043cu0438u043du043au0443</a>";
    echo "</div>";
    
    echo "</div>";
}
