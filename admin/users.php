<?php
require_once '../includes/db.php';
include '../includes/header.php';

define('STANDARD_RESET_PASSWORD', 'Password123!');

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin_sec') {
    include '../includes/footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['reset_id'])) {
    $reset_id = (int)$_POST['reset_id'];
    $newplain = STANDARD_RESET_PASSWORD;
    $newpass = password_hash($newplain, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
    $stmt->bind_param("si", $newpass, $reset_id);
    $stmt->execute();
    echo "<p class='text-muted'>Password has been reset to: <strong>{$newplain}</strong></p>";
}

$res = $conn->query("SELECT id, username, email, role FROM users");
?>

<div class="page-header">
  <h2>User Management</h2>
</div>

<div class="password-hint mb-4">
  <strong>Password Reset Info:</strong> When you reset a user's password, it will be set to: <code><?= STANDARD_RESET_PASSWORD ?></code>
</div>

<table class="table">
  <thead>
    <tr><th>Username</th><th>Email</th><th>Role</th><th>Action</th></tr>
  </thead>
  <tbody>
    <?php while($row=$res->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['username']) ?></td>
      <td><?= htmlspecialchars($row['email']) ?></td>
      <td><span class="badge bg-secondary"><?= $row['role'] ?></span></td>
      <td>
        <form method="post" style="margin:0;">
          <input type="hidden" name="reset_id" value="<?= $row['id'] ?>">
          <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Reset password for this user?')">Reset Password</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include '../includes/footer.php'; ?>