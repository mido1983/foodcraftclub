<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;

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

// u0424u0443u043du043au0446u0438u044f u0434u043bu044f u043fu0440u044fu043cu043eu0433u043e u043eu0431u043du043eu0432u043bu0435u043du0438u044f u0441u0442u0430u0442u0443u0441u0430
function updateUserStatus($userId, $status) {
    global $db;
    
    // u041fu043eu043bu0443u0447u0430u0435u043c u0442u0435u043au0443u0449u0438u0439 u0441u0442u0430u0442u0443u0441
    $stmt = $db->prepare("SELECT status FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $currentStatus = $stmt->fetchColumn();
    
    // u041eu0431u043du043eu0432u043bu044fu0435u043c u0441u0442u0430u0442u0443u0441
    $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
    $result = $stmt->execute([
        'id' => $userId,
        'status' => $status
    ]);
    
    // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0440u0435u0437u0443u043bu044cu0442u0430u0442
    $stmt = $db->prepare("SELECT status FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $updatedStatus = $stmt->fetchColumn();
    
    return [
        'success' => $result,
        'oldStatus' => $currentStatus,
        'newStatus' => $updatedStatus,
        'userId' => $userId
    ];
}

// u041eu0431u0440u0430u0431u043eu0442u043au0430 u0444u043eu0440u043cu044b
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_status' && isset($_POST['user_id']) && isset($_POST['status'])) {
        $userId = (int)$_POST['user_id'];
        $status = $_POST['status'];
        
        if (in_array($status, ['active', 'pending', 'suspended'])) {
            $result = updateUserStatus($userId, $status);
            $message = $result['success'] 
                ? "u0421u0442u0430u0442u0443u0441 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu044f ID: {$userId} u0438u0437u043cu0435u043du0435u043d u0441 '{$result['oldStatus']}' u043du0430 '{$result['newStatus']}'" 
                : "u041eu0448u0438u0431u043au0430 u043fu0440u0438 u043eu0431u043du043eu0432u043bu0435u043du0438u0438 u0441u0442u0430u0442u0443u0441u0430 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu044f ID: {$userId}";
            $messageType = $result['success'] ? 'success' : 'error';
        } else {
            $message = "u041du0435u0434u043eu043fu0443u0441u0442u0438u043cu044bu0439 u0441u0442u0430u0442u0443u0441: {$status}";
            $messageType = 'error';
        }
    } elseif ($action === 'fix_all_statuses') {
        // u041fu043eu043bu0443u0447u0430u0435u043c u0432u0441u0435u0445 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu0435u0439 u0441 u043fu0443u0441u0442u044bu043cu0438 u0438u043bu0438 u043du0435u0432u0435u0440u043du044bu043cu0438 u0441u0442u0430u0442u0443u0441u0430u043cu0438
        $stmt = $db->prepare("SELECT id, status FROM users WHERE status IS NULL OR status NOT IN ('active', 'pending', 'suspended')");
        $stmt->execute();
        $usersToFix = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $fixedCount = 0;
        foreach ($usersToFix as $user) {
            $result = updateUserStatus($user['id'], 'active');
            if ($result['success']) {
                $fixedCount++;
            }
        }
        
        $message = "u0418u0441u043fu0440u0430u0432u043bu0435u043du043e {$fixedCount} u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu0435u0439 u0441 u043du0435u0432u0435u0440u043du044bu043cu0438 u0441u0442u0430u0442u0443u0441u0430u043cu0438";
        $messageType = 'success';
    }
}

// u041fu043eu043bu0443u0447u0430u0435u043c u0441u043fu0438u0441u043eu043a u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu0435u0439
$stmt = $db->prepare("SELECT u.id, u.email, u.full_name, u.status, GROUP_CONCAT(r.name) as roles
                      FROM users u
                      LEFT JOIN user_roles ur ON u.id = ur.user_id
                      LEFT JOIN roles r ON ur.role_id = r.id
                      GROUP BY u.id
                      ORDER BY u.id");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// u0421u0442u0430u0442u0438u0441u0442u0438u043au0430 u043fu043e u0441u0442u0430u0442u0443u0441u0430u043c
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM users GROUP BY status");
$stmt->execute();
$statusStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// HTML u0448u0430u0431u043bu043eu043d
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>u041fu0440u044fu043cu043eu0435 u0443u043fu0440u0430u0432u043bu0435u043du0438u0435 u0441u0442u0430u0442u0443u0441u0430u043cu0438 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu0435u0439</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            border-radius: 0.25rem;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
        }
        .status-active {
            background-color: #28a745;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .status-suspended {
            background-color: #dc3545;
            color: white;
        }
        .status-unknown {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="mb-4">u041fu0440u044fu043cu043eu0435 u0443u043fu0440u0430u0432u043bu0435u043du0438u0435 u0441u0442u0430u0442u0443u0441u0430u043cu0438 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu0435u0439</h1>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> mb-4">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">u0421u0442u0430u0442u0438u0441u0442u0438u043au0430 u043fu043e u0441u0442u0430u0442u0443u0441u0430u043c</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>u0421u0442u0430u0442u0443u0441</th>
                                    <th>u041au043eu043bu0438u0447u0435u0441u0442u0432u043e u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu0435u0439</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="status-badge status-active">active</span></td>
                                    <td><?= $statusStats['active'] ?? 0 ?></td>
                                </tr>
                                <tr>
                                    <td><span class="status-badge status-pending">pending</span></td>
                                    <td><?= $statusStats['pending'] ?? 0 ?></td>
                                </tr>
                                <tr>
                                    <td><span class="status-badge status-suspended">suspended</span></td>
                                    <td><?= $statusStats['suspended'] ?? 0 ?></td>
                                </tr>
                                <?php foreach ($statusStats as $status => $count): ?>
                                    <?php if (!in_array($status, ['active', 'pending', 'suspended']) && $status !== null): ?>
                                        <tr>
                                            <td><span class="status-badge status-unknown"><?= htmlspecialchars($status) ?></span></td>
                                            <td><?= $count ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (isset($statusStats[null])): ?>
                                    <tr>
                                        <td><span class="status-badge status-unknown">NULL</span></td>
                                        <td><?= $statusStats[null] ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">u0418u0441u043fu0440u0430u0432u0438u0442u044c u0432u0441u0435 u043du0435u0432u0435u0440u043du044bu0435 u0441u0442u0430u0442u0443u0441u044b</h5>
                    </div>
                    <div class="card-body">
                        <p>u042du0442u0430 u0444u0443u043du043au0446u0438u044f u043du0430u0439u0434u0435u0442 u0432u0441u0435u0445 u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu0435u0439 u0441 u043fu0443u0441u0442u044bu043cu0438 u0438u043bu0438 u043du0435u0432u0435u0440u043du044bu043cu0438 u0441u0442u0430u0442u0443u0441u0430u043cu0438 u0438 u0443u0441u0442u0430u043du043eu0432u0438u0442 u0438u043c u0441u0442u0430u0442u0443u0441 "active".</p>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="fix_all_statuses">
                            <button type="submit" class="btn btn-success">u0418u0441u043fu0440u0430u0432u0438u0442u044c u0432u0441u0435 u0441u0442u0430u0442u0443u0441u044b</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">u0421u043fu0438u0441u043eu043a u043fu043eu043bu044cu0437u043eu0432u0430u0442u0435u043bu0435u0439</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>u0418u043cu044f</th>
                                <th>u0421u0442u0430u0442u0443u0441</th>
                                <th>u0420u043eu043bu0438</th>
                                <th>u0414u0435u0439u0441u0442u0432u0438u044f</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = '';
                                        $statusText = $user['status'];
                                        
                                        switch ($user['status']) {
                                            case 'active':
                                                $statusClass = 'status-active';
                                                break;
                                            case 'pending':
                                                $statusClass = 'status-pending';
                                                break;
                                            case 'suspended':
                                                $statusClass = 'status-suspended';
                                                break;
                                            default:
                                                $statusClass = 'status-unknown';
                                        }
                                        ?>
                                        <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($user['roles'] ?? '') ?></td>
                                    <td>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="btn btn-sm btn-success me-1">u0410u043au0442u0438u0432u043du044bu0439</button>
                                        </form>
                                        
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="status" value="pending">
                                            <button type="submit" class="btn btn-sm btn-warning me-1">u041eu0436u0438u0434u0430u044eu0449u0438u0439</button>
                                        </form>
                                        
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="status" value="suspended">
                                            <button type="submit" class="btn btn-sm btn-danger">u0417u0430u0431u043bu043eu043au0438u0440u043eu0432u0430u043du043du044bu0439</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="/admin/users" class="btn btn-secondary">u0412u0435u0440u043du0443u0442u044cu0441u044f u0432 u0430u0434u043cu0438u043du043au0443</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
