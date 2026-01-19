<?php
require_once '../includes/db.php';
include '../includes/header.php';

$res = $conn->query("SELECT audit_log.*, users.username FROM audit_log 
LEFT JOIN users ON audit_log.user_id=users.id 
ORDER BY audit_log.log_time DESC LIMIT 100");
?>

<div class="page-header">
  <h2>Audit Log</h2>
</div>

<table class="table">
  <thead>
    <tr><th>User</th><th>Action</th><th>Time</th></tr>
  </thead>
  <tbody>
    <?php while($row=$res->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></td>
      <td><?= htmlspecialchars($row['action']) ?></td>
      <td style="color: #64748b;"><?= $row['log_time'] ?></td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include '../includes/footer.php'; ?>