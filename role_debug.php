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
$action = isset($_GET['action']) ? $_GET['action'] : null;
$roleId = isset($_GET['role']) ? (int)$_GET['role'] : null;

echo '<h1>Role Debug Tool</h1>';

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

// Get all available roles
$db = Application::$app->db;
$statement = $db->prepare("SELECT * FROM roles ORDER BY name");
$statement->execute();
$allRoles = $statement->fetchAll(PDO::FETCH_ASSOC);

// Get user roles
$statement = $db->prepare("
    SELECT r.* 
    FROM roles r
    JOIN user_roles ur ON r.id = ur.role_id
    WHERE ur.user_id = :user_id
");
$statement->execute(['user_id' => $user->id]);
$userRoles = $statement->fetchAll(PDO::FETCH_ASSOC);

echo '<h2>Current Roles (Direct DB Query)</h2>';
if (empty($userRoles)) {
    echo '<p>No roles assigned</p>';
} else {
    echo '<ul>';
    foreach ($userRoles as $role) {
        echo '<li>Role ID: ' . $role['id'] . ' - Name: ' . $role['name'] . '</li>';
    }
    echo '</ul>';
}

// Process actions
if ($action === 'add' && $roleId) {
    echo '<h3>Adding Role ID: ' . $roleId . '</h3>';
    
    // Check if role exists
    $roleExists = false;
    foreach ($allRoles as $role) {
        if ((int)$role['id'] === $roleId) {
            $roleExists = true;
            break;
        }
    }
    
    if (!$roleExists) {
        echo '<p style="color: red;">Error: Role ID ' . $roleId . ' does not exist</p>';
    } else {
        // Check if user already has this role
        $hasRole = false;
        foreach ($userRoles as $role) {
            if ((int)$role['id'] === $roleId) {
                $hasRole = true;
                break;
            }
        }
        
        if ($hasRole) {
            echo '<p>User already has this role</p>';
        } else {
            // Add role directly to database
            try {
                $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
                $result = $stmt->execute([
                    'user_id' => $user->id,
                    'role_id' => $roleId
                ]);
                
                if ($result) {
                    echo '<p style="color: green;">Role added successfully!</p>';
                    // Refresh the page to show updated roles
                    echo '<script>window.location.href = "role_debug.php?id=' . $user->id . '";</script>';
                } else {
                    echo '<p style="color: red;">Failed to add role</p>';
                }
            } catch (Exception $e) {
                echo '<p style="color: red;">Error: ' . $e->getMessage() . '</p>';
            }
        }
    }
} elseif ($action === 'remove' && $roleId) {
    echo '<h3>Removing Role ID: ' . $roleId . '</h3>';
    
    // Check if user has this role
    $hasRole = false;
    foreach ($userRoles as $role) {
        if ((int)$role['id'] === $roleId) {
            $hasRole = true;
            break;
        }
    }
    
    if (!$hasRole) {
        echo '<p>User does not have this role</p>';
    } else {
        // Remove role directly from database
        try {
            $stmt = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id AND role_id = :role_id");
            $result = $stmt->execute([
                'user_id' => $user->id,
                'role_id' => $roleId
            ]);
            
            if ($result) {
                echo '<p style="color: green;">Role removed successfully!</p>';
                // Refresh the page to show updated roles
                echo '<script>window.location.href = "role_debug.php?id=' . $user->id . '";</script>';
            } else {
                echo '<p style="color: red;">Failed to remove role</p>';
            }
        } catch (Exception $e) {
            echo '<p style="color: red;">Error: ' . $e->getMessage() . '</p>';
        }
    }
}

// Display all available roles
echo '<h2>Available Roles</h2>';
echo '<ul>';
foreach ($allRoles as $role) {
    $hasRole = false;
    foreach ($userRoles as $userRole) {
        if ((int)$userRole['id'] === (int)$role['id']) {
            $hasRole = true;
            break;
        }
    }
    
    echo '<li>';
    echo 'Role ID: ' . $role['id'] . ' - Name: ' . $role['name'];
    
    if ($hasRole) {
        echo ' <a href="role_debug.php?id=' . $user->id . '&action=remove&role=' . $role['id'] . '" style="color: red;">[Remove]</a>';
    } else {
        echo ' <a href="role_debug.php?id=' . $user->id . '&action=add&role=' . $role['id'] . '" style="color: green;">[Add]</a>';
    }
    
    echo '</li>';
}
echo '</ul>';

// Add a link to the admin edit page
echo '<p><a href="/admin/users/edit/' . $user->id . '" class="btn btn-primary">Edit in Admin Interface</a></p>';

// Add a form to test the admin edit functionality
echo '<h2>Test Admin Edit Form</h2>';
echo '<form method="post" action="role_debug.php?id=' . $user->id . '&action=test_edit">';

echo '<div style="margin-bottom: 20px;">';
echo '<label><strong>Roles:</strong></label><br>';
foreach ($allRoles as $role) {
    $checked = false;
    foreach ($userRoles as $userRole) {
        if ((int)$userRole['id'] === (int)$role['id']) {
            $checked = true;
            break;
        }
    }
    
    echo '<div>';
    echo '<input type="checkbox" name="roles[]" value="' . $role['id'] . '" id="test_role_' . $role['id'] . '"' . ($checked ? ' checked' : '') . '>';
    echo '<label for="test_role_' . $role['id'] . '">' . $role['name'] . '</label>';
    echo '</div>';
}
echo '</div>';

echo '<div style="margin-bottom: 20px;">';
echo '<label><strong>Status:</strong></label><br>';
echo '<select name="status">';
$statuses = ['active', 'pending', 'suspended'];
foreach ($statuses as $status) {
    echo '<option value="' . $status . '"' . ($user->status === $status ? ' selected' : '') . '>' . ucfirst($status) . '</option>';
}
echo '</select>';
echo '</div>';

echo '<button type="submit">Test Update</button>';
echo '</form>';

// Process test edit form
if ($action === 'test_edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<h3>Processing Test Edit Form</h3>';
    echo '<pre>';
    print_r($_POST);
    echo '</pre>';
    
    // Update roles
    if (isset($_POST['roles']) && is_array($_POST['roles'])) {
        $selectedRoles = array_map('intval', $_POST['roles']);
        
        try {
            // Begin transaction
            $db->beginTransaction();
            
            // Delete existing roles
            $stmt = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user->id]);
            
            // Insert new roles
            foreach ($selectedRoles as $roleId) {
                $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
                $result = $stmt->execute([
                    'user_id' => $user->id,
                    'role_id' => $roleId
                ]);
                
                echo '<p>Adding role ID ' . $roleId . ': ' . ($result ? 'Success' : 'Failed') . '</p>';
            }
            
            $db->commit();
            echo '<p style="color: green;">Roles updated successfully!</p>';
        } catch (Exception $e) {
            $db->rollBack();
            echo '<p style="color: red;">Error updating roles: ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p>No roles selected</p>';
        
        // Delete all roles
        try {
            $stmt = $db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
            $result = $stmt->execute(['user_id' => $user->id]);
            
            if ($result) {
                echo '<p style="color: green;">All roles removed</p>';
            }
        } catch (Exception $e) {
            echo '<p style="color: red;">Error removing roles: ' . $e->getMessage() . '</p>';
        }
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
    
    // Refresh the page after 2 seconds
    echo '<script>setTimeout(function() { window.location.href = "role_debug.php?id=' . $user->id . '"; }, 2000);</script>';
}

// Display raw database data
echo '<h2>Raw Database Data</h2>';

// Show user_roles table data for this user
echo '<h3>user_roles Table</h3>';
$stmt = $db->prepare("SELECT * FROM user_roles WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user->id]);
$userRolesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($userRolesRaw)) {
    echo '<p>No records found in user_roles table for this user</p>';
} else {
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>user_id</th><th>role_id</th></tr>';
    foreach ($userRolesRaw as $record) {
        echo '<tr>';
        echo '<td>' . $record['user_id'] . '</td>';
        echo '<td>' . $record['role_id'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Show roles table
echo '<h3>roles Table</h3>';
$stmt = $db->prepare("SELECT * FROM roles");
$stmt->execute();
$rolesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<table border="1" cellpadding="5">';
echo '<tr><th>id</th><th>name</th></tr>';
foreach ($rolesRaw as $record) {
    echo '<tr>';
    echo '<td>' . $record['id'] . '</td>';
    echo '<td>' . $record['name'] . '</td>';
    echo '</tr>';
}
echo '</table>';
