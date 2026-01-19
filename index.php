<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
include 'includes/header.php';
?>

<div class="hero">
  <h1>Welcome to my Website</h1>
  <p>A clean, minimal place to discover curated products and projects.</p>
  <hr class="my-4" style="border-color: #e2e8f0;">
  <?php if(isset($_SESSION['username'])): ?>
    <p style="color: #1e293b;">Hello, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>
  <?php endif; ?>
  <a class="btn btn-primary btn-lg" href="/SCP/products/products.php">Browse Products</a>
</div>

<?php include 'includes/footer.php'; ?>