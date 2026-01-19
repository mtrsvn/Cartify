<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
include '../includes/header.php';

if(!isset($_SESSION['role']) || ($_SESSION['role'] != 'staff_user' && $_SESSION['role'] != 'admin_sec')) {
    include '../includes/footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['purchase_id'])) {
    $pid = intval($_POST['purchase_id']);
    $stmt = $conn->prepare("UPDATE purchases SET approved=1 WHERE id=?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $stmt->close();
}

$res = $conn->query("SELECT purchases.*, users.username, products.name AS product_name FROM purchases 
LEFT JOIN users ON purchases.user_id=users.id
LEFT JOIN products ON purchases.product_id=products.id
WHERE purchases.approved=0");
?>

<div class="page-header">
  <h2>Pending Orders</h2>
</div>

<table class="table">
  <thead>
    <tr><th>User</th><th>Product</th><th>Qty</th><th>Action</th></tr>
  </thead>
  <tbody>
    <?php while($row=$res->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['username']) ?></td>
      <td><?= htmlspecialchars($row['product_name']) ?></td>
      <td><?= $row['quantity'] ?></td>
      <td>
        <form method="post" style="margin:0;">
          <input type="hidden" name="purchase_id" value="<?= $row['id'] ?>">
          <button type="submit" class="btn btn-primary btn-sm">Approve</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include '../includes/footer.php'; ?>