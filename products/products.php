<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
include '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    include '../includes/footer.php';
    exit();
}

$res = $conn->query("SELECT id, name, description, price FROM products");
?>

<div class="page-header">
  <h2>Products</h2>
</div>

<div class="row g-4">
<?php while ($row = $res->fetch_assoc()):
    $name = htmlspecialchars($row['name']);
    $desc = htmlspecialchars($row['description'] ?? '');
    $price = number_format((float)$row['price'], 2);
    $id = (int)$row['id'];
?>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body d-flex flex-column">
        <h5 class="card-title mb-2"><?= $name ?></h5>
        <p class="card-text" style="color: #64748b; flex-grow: 1;"><?= $desc ?></p>
        <p class="card-text mb-3"><strong style="font-size: 1.25rem;">$<?= $price ?></strong></p>
        <form method="post" action="/SCP/products/cart.php" class="d-flex gap-2">
          <input type="hidden" name="product_id" value="<?= $id ?>">
          <input type="number" name="quantity" min="1" value="1" class="form-control" style="max-width: 80px;">
          <button type="submit" name="add_to_cart" value="1" class="btn btn-primary flex-grow-1">Add to Cart</button>
        </form>
      </div>
    </div>
  </div>
<?php endwhile; ?>
</div>

<?php include '../includes/footer.php'; ?>