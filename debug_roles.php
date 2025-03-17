<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;
use App\Models\User;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize the application
$app = new Application(dirname(__FILE__));

// Get user ID from query parameter
$userId = isset($_GET['id']) ? (int)$_GET['id'] : null;

echo '<h1>User Role Debug</h1>';

if (!$userId) {
    echo '<p>No user ID provided. Use ?id=X in the URL.</p>';
    exit;
}

// Find the user
$user = User::findOne(['id' => $userId]);

if (!$user) {
    echo '<p>User not found with ID: ' . $userId . '</p>';
    exit;
}

echo '<h2>User Information</h2>';
echo '<p>ID: ' . $user->id . '</p>';
echo '<p>Email: ' . $user->email . '</p>';
echo '<p>Full Name: ' . $user->full_name . '</p>';
echo '<p>Status: ' . $user->status . '</p>';

// Get user roles
$roles = $user->getRoles();
echo '<h2>Current Roles</h2>';

if (empty($roles)) {
    echo '<p>No roles assigned</p>';
} else {
    echo '<ul>';
    foreach ($roles as $role) {
        echo '<li>Role ID: ' . $role['id'] . ' - Name: ' . $role['name'] . '</li>';
    }
    echo '</ul>';
}

// Get all available roles
$db = Application::$app->db;
$statement = $db->prepare("SELECT * FROM roles ORDER BY name");
$statement->execute();
$allRoles = $statement->fetchAll(PDO::FETCH_ASSOC);

echo '<h2>Available Roles</h2>';
echo '<ul>';
foreach ($allRoles as $role) {
    echo '<li>Role ID: ' . $role['id'] . ' - Name: ' . $role['name'] . '</li>';
}
echo '</ul>';

// Form to update roles
echo '<h2>Update Roles</h2>';
echo '<form method="post">';
echo '<div>';
foreach ($allRoles as $role) {
    $checked = false;
    foreach ($roles as $userRole) {
        if ((int)$userRole['id'] === (int)$role['id']) {
            $checked = true;
            break;
        }
    }
    echo '<div>';
    echo '<input type="checkbox" name="roles[]" value="' . $role['id'] . '" id="role_' . $role['id'] . '"' . ($checked ? ' checked' : '') . '>';
    echo '<label for="role_' . $role['id'] . '">' . $role['name'] . '</label>';
    echo '</div>';
}
echo '</div>';

echo '<h2>Update Status</h2>';
echo '<div>';
echo '<select name="status">';
$statuses = ['active', 'pending', 'suspended'];
foreach ($statuses as $status) {
    echo '<option value="' . $status . '"' . ($user->status === $status ? ' selected' : '') . '>' . ucfirst($status) . '</option>';
}
echo '</select>';
echo '</div>';

echo '<div style="margin-top: 20px;">';
echo '<button type="submit">Update User</button>';
echo '</div>';
echo '</form>';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<h2>Form Submission</h2>';
    echo '<pre>';
    print_r($_POST);
    echo '</pre>';
    
    // Update roles
    if (isset($_POST['roles']) && is_array($_POST['roles'])) {
        $selectedRoles = array_map('intval', $_POST['roles']);
        
        try {
            $user->setRoles($selectedRoles);
            echo '<p style="color: green;">Roles updated successfully!</p>';
            
            // Show updated roles
            $updatedRoles = $user->getRoles();
            echo '<h3>Updated Roles</h3>';
            echo '<ul>';
            foreach ($updatedRoles as $role) {
                echo '<li>Role ID: ' . $role['id'] . ' - Name: ' . $role['name'] . '</li>';
            }
            echo '</ul>';
        } catch (Exception $e) {
            echo '<p style="color: red;">Error updating roles: ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p>No roles selected, setting default Client role</p>';
        $user->setRoles([3]); // Default to Client role
    }
    
    // Update status
    if (isset($_POST['status']) && in_array($_POST['status'], ['active', 'pending', 'suspended'])) {
        $user->status = $_POST['status'];
        
        try {
            if ($user->save()) {
                echo '<p style="color: green;">Status updated successfully to: ' . $user->status . '</p>';
            } else {
                echo '<p style="color: red;">Failed to update status</p>';
            }
        } catch (Exception $e) {
            echo '<p style="color: red;">Error updating status: ' . $e->getMessage() . '</p>';
        }
    }
}
