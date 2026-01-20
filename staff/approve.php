<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
include '../includes/header.php';

if(!isset($_SESSION['role']) || ($_SESSION['role'] != 'staff_user' && $_SESSION['role'] != 'admin_sec')) {
    include '../includes/footer.php';
    exit();
}

function handle_order_action($conn, $uid, $orderTime, $action) {
  if ($uid <= 0 || $orderTime === '' || !in_array($action, ['approve','reject'], true)) {
    return false;
  }

  $items = [];
  $total = 0;

  $detailStmt = $conn->prepare(
    "SELECT p.id, p.user_id, COALESCE(prod.title, p.product_name) AS product_name, p.product_price, p.quantity, u.email, u.username
     FROM purchases p
     JOIN users u ON p.user_id = u.id
     LEFT JOIN products prod ON p.product_id = prod.id
     WHERE p.user_id = ? AND p.approved = 0 AND DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') = ?"
  );
  $detailStmt->bind_param('is', $uid, $orderTime);
  $detailStmt->execute();
  $result = $detailStmt->get_result();
  $email = null;
  $username = null;
  while ($row = $result->fetch_assoc()) {
    $items[] = [
      'name' => $row['product_name'] ?? 'Unknown Product',
      'quantity' => (int)$row['quantity'],
      'price' => (float)$row['product_price']
    ];
    $total += (float)$row['product_price'] * (int)$row['quantity'];
    $email = $row['email'] ?? $email;
    $username = $row['username'] ?? $username;
  }
  $detailStmt->close();

  if (empty($items)) {
    return false;
  }

  $statusValue = $action === 'approve' ? 1 : 2;
  $upd = $conn->prepare("UPDATE purchases SET approved = ? WHERE user_id = ? AND approved = 0 AND DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') = ?");
  $upd->bind_param('iis', $statusValue, $uid, $orderTime);
  $upd->execute();
  $upd->close();

  require_once '../includes/mailer.php';
  if (!empty($email)) {
    if ($action === 'approve') {
      send_purchase_confirmation_email($email, $username ?? $email, $items, $total);
    } else {
      send_purchase_rejection_email($email, $username ?? $email, $items, $total);
    }
  }

  if (function_exists('log_action')) {
    $msg = $action === 'approve' ? 'Order approved in bulk and email sent' : 'Order rejected in bulk and email sent';
    log_action($conn, $uid, $msg);
  }

  return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_key'])) {
  $uid = intval($_POST['user_id'] ?? 0);
  $orderTime = $_POST['order_time'] ?? '';
  if (handle_order_action($conn, $uid, $orderTime, 'approve')) {
    $_SESSION['admin_toast'] = [
      'message' => 'Order approved and customer notified.',
      'type' => 'success'
    ];
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_key'])) {
  $uid = intval($_POST['user_id'] ?? 0);
  $orderTime = $_POST['order_time'] ?? '';
  if (handle_order_action($conn, $uid, $orderTime, 'reject')) {
    $_SESSION['admin_toast'] = [
      'message' => 'Order rejected and customer notified.',
      'type' => 'warning'
    ];
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && isset($_POST['bulk_orders']) && is_array($_POST['bulk_orders'])) {
  $action = $_POST['bulk_action'];
  $allowed = ['approve','reject'];
  if (in_array($action, $allowed, true)) {
    $handled = 0;
    foreach ($_POST['bulk_orders'] as $token) {
      if (strpos($token, '|') === false) continue;
      list($uidRaw, $timeRaw) = explode('|', $token, 2);
      $uid = intval($uidRaw);
      $orderTime = $timeRaw;
      if (handle_order_action($conn, $uid, $orderTime, $action)) {
        $handled++;
      }
    }
    if ($handled > 0) {
      $_SESSION['admin_toast'] = [
        'message' => $action === 'approve' ? "$handled order(s) approved." : "$handled order(s) rejected.",
        'type' => $action === 'approve' ? 'success' : 'warning'
      ];
    }
  }
}

$res = $conn->query("SELECT 
  u.id AS user_id,
  u.username,
  u.email,
  DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') AS order_time,
  GROUP_CONCAT(CONCAT(COALESCE(prod.title, p.product_name), ' x', p.quantity) ORDER BY p.id SEPARATOR ', ') AS item_list,
  SUM(p.quantity) AS total_qty,
  SUM(p.product_price * p.quantity) AS total_amount
FROM purchases p
JOIN users u ON p.user_id = u.id
LEFT JOIN products prod ON p.product_id = prod.id
WHERE p.approved = 0
GROUP BY u.id, u.username, u.email, order_time
ORDER BY order_time DESC");
?>

<div class="page-header">
  <h2>Pending Orders</h2>
</div>

<?php if ($res && $res->num_rows > 0): ?>
<form id="bulkForm" method="post">
  <input type="hidden" name="bulk_action" id="bulkActionInput" value="">
</form>

<table class="table align-middle">
  <thead>
    <tr><th><input type="checkbox" id="selectAllOrders"></th><th>User</th><th>Order Time</th><th>Items</th><th>Total Qty</th><th>Total Amount</th><th>Action</th></tr>
  </thead>
  <tbody>
    <?php while($row=$res->fetch_assoc()): ?>
    <tr>
      <td>
        <input type="checkbox" class="order-checkbox" name="bulk_orders[]" value="<?= htmlspecialchars($row['user_id'] . '|' . $row['order_time']) ?>" form="bulkForm">
      </td>
      <td><?= htmlspecialchars($row['username'] ?? 'Unknown User') ?></td>
      <td><?= htmlspecialchars($row['order_time'] ?? '') ?></td>
      <td><?= htmlspecialchars($row['item_list'] ?? 'No items') ?></td>
      <td><?= (int)$row['total_qty'] ?></td>
      <td>$<?= number_format((float)$row['total_amount'], 2) ?></td>
      <td>
        <div class="d-flex gap-2">
          <form method="post" style="margin:0;">
            <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
            <input type="hidden" name="order_time" value="<?= $row['order_time'] ?>">
            <input type="hidden" name="approve_key" value="1">
            <button type="submit" class="btn btn-primary btn-sm">Approve</button>
          </form>
          <form method="post" style="margin:0;">
            <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
            <input type="hidden" name="order_time" value="<?= $row['order_time'] ?>">
            <input type="hidden" name="reject_key" value="1">
            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<div id="bulkBar" class="bulk-bar d-none">
  <div class="container d-flex justify-content-between align-items-center py-2">
    <div><strong id="selectedCount">0</strong> selected</div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary" data-action="approve" form="bulkForm">Approve</button>
      <button type="submit" class="btn btn-danger" data-action="reject" form="bulkForm">Reject</button>
    </div>
  </div>
</div>
<?php else: ?>
  <div class="card">
    <div class="card-body text-center py-4">No pending orders.</div>
  </div>
<?php endif; ?>

<?php if (!empty($_SESSION['admin_toast'])): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      try {
        var msg = <?php echo json_encode($_SESSION['admin_toast']['message']); ?>;
        var type = <?php echo json_encode($_SESSION['admin_toast']['type']); ?>;
        if (typeof showToast === 'function') {
          showToast(msg, type);
        }
      } catch(e) {}
    });
  </script>
  <?php unset($_SESSION['admin_toast']); ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
  var selectAll = document.getElementById('selectAllOrders');
  var checkboxes = document.querySelectorAll('.order-checkbox');
  var bulkBar = document.getElementById('bulkBar');
  var selectedCountEl = document.getElementById('selectedCount');
  var bulkActionInput = document.getElementById('bulkActionInput');
  var bulkButtons = document.querySelectorAll('#bulkBar button[data-action]');

  function updateSelectionState() {
    var checked = Array.from(checkboxes).filter(function(cb){ return cb.checked; }).length;
    if (selectedCountEl) selectedCountEl.textContent = checked;
    if (bulkBar) {
      if (checked > 0) {
        bulkBar.classList.remove('d-none');
      } else {
        bulkBar.classList.add('d-none');
      }
    }
    if (selectAll) {
      var total = checkboxes.length;
      selectAll.checked = checked === total && total > 0;
      selectAll.indeterminate = checked > 0 && checked < total;
    }
  }

  if (selectAll) {
    selectAll.addEventListener('change', function(){
      checkboxes.forEach(function(cb){ cb.checked = selectAll.checked; });
      updateSelectionState();
    });
  }

  checkboxes.forEach(function(cb){
    cb.addEventListener('change', updateSelectionState);
  });

  bulkButtons.forEach(function(btn){
    btn.addEventListener('click', function(){
      if (bulkActionInput) {
        bulkActionInput.value = btn.getAttribute('data-action');
      }
    });
  });

  updateSelectionState();
});
</script>

<style>
.bulk-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  background: #fff;
  border-top: 1px solid #e5e7eb;
  box-shadow: 0 -6px 20px rgba(0,0,0,0.08);
  z-index: 1100;
}
</style>

<?php include '../includes/footer.php'; ?>