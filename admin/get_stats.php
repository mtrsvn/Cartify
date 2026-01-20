<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
if ($_SESSION['role'] !== 'staff_user' && $_SESSION['role'] !== 'administrator') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

try {
    $products_query = "SELECT COUNT(*) as count FROM products";
    $products_result = $conn->query($products_query);
    $total_products = $products_result->fetch_assoc()['count'];
    $pending_query = "SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'pending'";
    $pending_result = $conn->query($pending_query);
    $pending_orders = $pending_result->fetch_assoc()['count'];
    $approved_query = "SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'approved'";
    $approved_result = $conn->query($approved_query);
    $approved_orders = $approved_result->fetch_assoc()['count'];
    $users_query = "SELECT COUNT(*) as count FROM users";
    $users_result = $conn->query($users_query);
    $total_users = $users_result->fetch_assoc()['count'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_products' => $total_products,
            'pending_orders' => $pending_orders,
            'approved_orders' => $approved_orders,
            'total_users' => $total_users
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching stats: ' . $e->getMessage()
    ]);
}
?>
