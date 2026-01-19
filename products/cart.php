<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
include '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    include '../includes/footer.php';
    exit();
}

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    if ($product_id > 0) {
        if ($quantity < 1) $quantity = 1;
        $_SESSION['cart'][$product_id] = $quantity;
    }
}

if (isset($_POST['checkout'])) {
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $pid => $qty) {
            $stmt = $conn->prepare("INSERT INTO purchases (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param('iii', $_SESSION['user_id'], $pid, $qty);
            $stmt->execute();
            $stmt->close();
            log_action($conn, $_SESSION['user_id'], "Purchase created, awaiting staff approval");
        }
        $_SESSION['cart'] = [];
    }
}
?>

<div class="page-header">
  <h2>Your Cart</h2>
</div>

<?php if (empty($_SESSION['cart'])): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <p style="color: #64748b; margin-bottom: 1rem;">Your cart is empty.</p>
      <a href="/SCP/products/products.php" class="btn btn-primary">Browse Products</a>
    </div>
  </div>
<?php else: ?>
  <form method="post">
    <table class="table">
      <thead>
        <tr><th>Product</th><th>Quantity</th></tr>
      </thead>
      <tbody>
        <?php foreach ($_SESSION['cart'] as $pid => $qty):
          $res = $conn->query("SELECT name FROM products WHERE id=$pid");
          $prod = $res->fetch_assoc();
        ?>
        <tr>
          <td><?= htmlspecialchars($prod['name']) ?></td>
          <td><?= $qty ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button class="btn btn-primary" name="checkout">Checkout</button>
  </form>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>