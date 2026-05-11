<?php
ob_start();
session_start();

if(!isset($_SESSION['name']) || $_SESSION['name']!='oasis') {
  header('location: ../index.php');
  exit;
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

include('connect.php');

$error_msg = '';
$success_msg = '';

// Filters
$user_id = isset($_GET['user_id']) ? htmlspecialchars(trim($_GET['user_id'])) : '';

// Load logs (basic filter)
$logs = [];
$q = "SELECT * FROM activity_logs";
$where = [];
if ($user_id !== '') {
  $where[] = "user_id = ?";
}

if (count($where) > 0) {
  $q .= " WHERE " . implode(" AND ", $where);
}

$q .= " ORDER BY created_at DESC LIMIT 100";

$stmt = null;
if ($user_id !== '') {
  $stmt = mysqli_prepare($link, $q);
  mysqli_stmt_bind_param($stmt, "s", $user_id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
} else {
  $result = mysqli_query($link, $q);
}

if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $logs[] = $row;
  }
}
if ($stmt) mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Activity Logs - Admin Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .filter-grid { display:grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items:end; }
    .table-wrap { overflow-x:auto; }
    .badge { display:inline-block; padding:4px 10px; border-radius:999px; border:2px solid #000; background:#fff; font-weight:800; font-size:12px; }
    .badge-success { background:#27ae60; color:#fff; border-color:#27ae60; }
    .badge-failure { background:#e74c3c; color:#fff; border-color:#e74c3c; }
  </style>
</head>
<body>
<div class="dashboard-container">
  <?php $activePage = 'activity_logs.php'; include('sidebar.php'); ?>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-history"></i> Activity Logs</h1>
        <p style="color: var(--muted); margin: 0;">Track admin/teacher actions and changes</p>
      </div>
      <div class="user-menu">
        <span>👨‍💼 Admin</span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <?php if($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
      <?php endif; ?>
      <?php if($error_msg): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header"><i class="fas fa-filter"></i> Filters</div>
        <div class="card-body">
          <form method="get" action="">
            <div class="filter-grid">
              <div class="form-group">
                <label for="user_id">User ID</label>
                <input type="text" id="user_id" name="user_id" class="form-control" placeholder="e.g., oasis" value="<?php echo $user_id; ?>">
              </div>
              <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
              </div>
              <div class="form-group" style="justify-self:end;">
                <a href="activity_logs.php" class="btn btn-light"><i class="fas fa-undo"></i> Reset</a>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-list"></i> Recent Logs (<?php echo count($logs); ?>)</div>
        <div class="card-body">
          <div class="table-wrap">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>User</th>
                  <th>Action</th>
                  <th>Entity</th>
                  <th>Status</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                <?php if(count($logs) > 0): ?>
                  <?php foreach($logs as $log): ?>
                    <tr>
                      <td><strong><?php echo (int)($log['log_id'] ?? 0); ?></strong></td>
                      <td><?php echo htmlspecialchars($log['user_id'] ?? '-'); ?></td>
                      <td><?php echo htmlspecialchars($log['action'] ?? '-'); ?></td>
                      <td>
                        <?php
                          $et = htmlspecialchars($log['entity_type'] ?? '');
                          $eid = htmlspecialchars($log['entity_id'] ?? '');
                          echo trim(($et || $eid) ? ($et . ' / ' . $eid) : '-');
                        ?>
                      </td>
                      <td>
                        <?php
                          $status = strtolower($log['status'] ?? 'success');
                          $class = $status === 'failure' ? 'badge-failure' : 'badge-success';
                          $label = $status === 'failure' ? 'Failure' : 'Success';
                        ?>
                        <span class="badge <?php echo $class; ?>"><?php echo $label; ?></span>
                      </td>
                      <td><?php echo htmlspecialchars($log['created_at'] ?? '-'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="6" class="text-center text-muted"><i class="fas fa-inbox"></i> No logs found</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
