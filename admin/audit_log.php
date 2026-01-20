<?php
require_once '../includes/db.php';
include '../includes/header.php';

$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }

$countRes = $conn->query("SELECT COUNT(*) AS cnt FROM audit_log");
$total = 0;
if ($countRes && ($rowCnt = $countRes->fetch_assoc())) {
  $total = (int)$rowCnt['cnt'];
}
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $perPage;

$res = $conn->query(
  "SELECT audit_log.*, users.username FROM audit_log " .
  "LEFT JOIN users ON audit_log.user_id=users.id " .
  "ORDER BY audit_log.log_time DESC LIMIT {$perPage} OFFSET {$offset}"
);
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

<?php if ($total > $perPage): ?>
<?php
  $start = $offset + 1;
  $end = min($offset + $perPage, $total);
  function page_url($p) {
      $params = $_GET;
      $params['page'] = $p;
      return '?' . http_build_query($params);
  }
?>
<div class="d-flex align-items-center justify-content-between" style="margin-top: 12px;">
  <div class="text-muted">Showing <?= $start ?>–<?= $end ?> of <?= $total ?></div>
  <nav aria-label="Audit log pagination">
    <ul class="pagination mb-0">
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= $page > 1 ? page_url($page - 1) : '#' ?>" tabindex="-1" aria-disabled="<?= $page <= 1 ? 'true' : 'false' ?>">Previous</a>
      </li>
      <?php
        $window = 1;
        $pages = [];
        $pages[] = 1;
        for ($i = $page - $window; $i <= $page + $window; $i++) {
            if ($i > 1 && $i < $totalPages) { $pages[] = $i; }
        }
        if ($totalPages > 1) { $pages[] = $totalPages; }
        $pages = array_values(array_unique(array_filter($pages, function($n){ return $n >= 1; })));
        sort($pages);
        $lastPrinted = 0;
        foreach ($pages as $p) {
            if ($lastPrinted && $p > $lastPrinted + 1) {
                echo '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            }
            $active = $p == $page ? ' active' : '';
            echo '<li class="page-item'.$active.'"><a class="page-link" href="'.page_url($p).'">'.$p.'</a></li>';
            $lastPrinted = $p;
        }
      ?>
      <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= $page < $totalPages ? page_url($page + 1) : '#' ?>" aria-disabled="<?= $page >= $totalPages ? 'true' : 'false' ?>">Next</a>
      </li>
    </ul>
  </nav>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>