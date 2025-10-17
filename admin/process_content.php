<?php
/**
 * Process Content Management
 * Handles AJAX requests for managing content tables
 */

require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Get the requested action
$action = $_POST['action'] ?? '';

// Get the table to operate on
$table = $_POST['table'] ?? '';

// Validate table name to prevent SQL injection
$allowed_tables = ['enrollment_instructions', 'exam_instructions', 'exam_required_documents', 'required_documents'];
if (!in_array($table, $allowed_tables)) {
    $response['message'] = 'Invalid table specified';
    echo json_encode($response);
    exit;
}

// Process based on action
switch ($action) {
    case 'get_item':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            $response['message'] = 'Invalid ID';
            break;
        }
        
        try {
            $stmt = $conn->prepare("SELECT * FROM {$table} WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                $response['success'] = true;
                $response['data'] = $item;
            } else {
                $response['message'] = 'Item not found';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
        break;
        
    case 'update_order':
        if (empty($_POST['items'])) {
            $response['message'] = 'No items provided';
            break;
        }
        
        try {
            $conn->beginTransaction();
            $items = json_decode($_POST['items'], true);
            
            foreach ($items as $item) {
                $id = (int)$item['id'];
                $order = (int)$item['order'];
                
                if ($id <= 0) continue;
                
                $stmt = $conn->prepare("UPDATE {$table} SET sort_order = ? WHERE id = ?");
                $stmt->execute([$order, $id]);
            }
            
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Order updated successfully';
        } catch (Exception $e) {
            $conn->rollBack();
            $response['message'] = 'Error updating order: ' . $e->getMessage();
        }
        break;
        
    case 'toggle_status':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $field = $_POST['field'] ?? '';
        
        // Validate field name to prevent SQL injection
        $allowed_fields = ['is_active', 'is_mandatory'];
        if (!in_array($field, $allowed_fields)) {
            $response['message'] = 'Invalid field specified';
            break;
        }
        
        if ($id <= 0) {
            $response['message'] = 'Invalid ID';
            break;
        }
        
        try {
            // Get current status first
            $stmt = $conn->prepare("SELECT {$field} FROM {$table} WHERE id = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current) {
                $response['message'] = 'Item not found';
                break;
            }
            
            // Toggle the status
            $new_status = $current[$field] ? 0 : 1;
            
            $stmt = $conn->prepare("UPDATE {$table} SET {$field} = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            
            $response['success'] = true;
            $response['message'] = 'Status toggled successfully';
            $response['data'] = ['new_status' => $new_status];
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
        break;
        
    default:
        $response['message'] = 'Invalid action';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 