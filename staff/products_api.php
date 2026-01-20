<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
if ($_SESSION['role'] !== 'staff_user' && $_SESSION['role'] !== 'administrator') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        listProducts($conn);
        break;
    case 'get':
        getProduct($conn);
        break;
    case 'add':
        addProduct($conn, $_SESSION['user_id']);
        break;
    case 'update':
        updateProduct($conn, $_SESSION['user_id']);
        break;
    case 'delete':
        deleteProduct($conn, $_SESSION['user_id']);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function listProducts($conn) {
    $query = "SELECT * FROM products ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    echo json_encode(['success' => true, 'products' => $products]);
}

function getProduct($conn) {
    $id = intval($_GET['id'] ?? 0);
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if ($product) {
        echo json_encode(['success' => true, 'product' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
}

function addProduct($conn, $user_id) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image = trim($_POST['image'] ?? '');
    
    if (empty($name) || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product data']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO products (name, description, category, price, stock, image, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssdis", $name, $description, $category, $price, $stock, $image);
    
    if ($stmt->execute()) {
        log_action($conn, $user_id, "Added product: $name");
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding product']);
    }
}

function updateProduct($conn, $user_id) {
    $id = intval($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image = trim($_POST['image'] ?? '');
    
    if ($id <= 0 || empty($name) || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product data']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, category = ?, price = ?, stock = ?, image = ? WHERE id = ?");
    $stmt->bind_param("sssdisi", $name, $description, $category, $price, $stock, $image, $id);
    
    if ($stmt->execute()) {
        log_action($conn, $user_id, "Updated product ID: $id ($name)");
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating product']);
    }
}

function deleteProduct($conn, $user_id) {
    $id = intval($_POST['product_id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $product_name = $product['name'] ?? "ID: $id";
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        log_action($conn, $user_id, "Deleted product: $product_name");
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting product']);
    }
}
?>
