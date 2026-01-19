<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
include 'includes/header.php';

// Fetch products from Fake Store API
$api_url = 'https://fakestoreapi.com/products';
$products = [];

$response = @file_get_contents($api_url);
if ($response !== false) {
    $products = json_decode($response, true);
} else {
    // Fallback: try using cURL if file_get_contents fails
    if (function_exists('curl_init')) {
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response !== false) {
            $products = json_decode($response, true);
        }
    }
}
?>

<div class="page-header">
  <h2>Products</h2>
  <?php if(isset($_SESSION['username'])): ?>
    <p style="color: #64748b; margin-top: 0.5rem;">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>
  <?php endif; ?>
</div>

<?php if (empty($products)): ?>
  <div class="alert alert-warning">Unable to load products from the API. Please try again later.</div>
<?php else: ?>
  <div class="row g-4">
  <?php foreach ($products as $product):
      $name = htmlspecialchars($product['title'] ?? 'N/A');
      $desc = htmlspecialchars($product['description'] ?? '');
      $price = number_format((float)($product['price'] ?? 0), 2);
      $id = (int)($product['id'] ?? 0);
      $image = htmlspecialchars($product['image'] ?? '');
      $category = htmlspecialchars($product['category'] ?? '');
  ?>
    <div class="col-md-4">
      <div class="card h-100">
        <?php if ($image): ?>
        <img src="<?= $image ?>" class="card-img-top" alt="<?= $name ?>" style="height: 200px; object-fit: contain; padding: 1rem; background: #f8fafc;">
        <?php endif; ?>
        <div class="card-body d-flex flex-column">
          <?php if ($category): ?>
          <span class="badge bg-secondary mb-2" style="width: fit-content;"><?= $category ?></span>
          <?php endif; ?>
          <h5 class="card-title mb-2"><?= $name ?></h5>
          <p class="card-text" style="color: #64748b; flex-grow: 1; font-size: 0.9rem;"><?= strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc ?></p>
          <p class="card-text mb-3"><strong style="font-size: 1.25rem;">$<?= $price ?></strong></p>
          <?php if (isset($_SESSION['user_id'])): ?>
          <form method="post" action="/SCP/products/cart.php" class="d-flex gap-2">
            <input type="hidden" name="product_id" value="<?= $id ?>">
            <input type="number" name="quantity" min="1" value="1" class="form-control" style="max-width: 80px;">
            <button type="submit" name="add_to_cart" value="1" class="btn btn-primary flex-grow-1">Add to Cart</button>
          </form>
          <?php else: ?>
          <button class="btn btn-secondary w-100" onclick="alert('Please login to add items to cart')">Login to Purchase</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>