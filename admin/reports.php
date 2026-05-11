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
$course = isset($_GET['course']) ? htmlspecialchars(trim($_GET['course'])) : '';
$st_dept = isset($_GET['st_dept']) ? htmlspecialchars(trim($_GET['st_dept'])) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
$types = '';

if($course !== '') {
  $where[] = "course = ?";
  $params[] = $course;
  $types .= 's';
}

if($st_dept !== '') {
  $where[] = "st_dept = ?";
  $params[] = $st_dept;
  $types .= 's';
}

$whereSql = '';
if(count($where) > 0) {
  $whereSql = " WHERE " . implode(" AND ", $where);
}

// Count
$countSql = "SELECT COUNT(*) as total FROM reports" . $whereSql;
$total = 0;
$countStmt = null;

if($types !== '') {
  $countStmt = mysqli_prepare($link, $countSql);
  mysqli_stmt_bind_param($countStmt, $types, ...$params);
  mysqli_stmt_execute($countStmt);
  $countRes = mysqli_stmt_get_result($countStmt);
  $row = mysqli_fetch_assoc($countRes);
  $total = intval($row['total'] ?? 0);
  mysqli_stmt_close($countStmt);
} else {
  $countRes = mysqli_query($link, $countSql);
  if($countRes) {
    $row = mysqli_fetch_assoc($countRes);
    $total = intval($row['total'] ?? 0);
  }
}

$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT st_id, course, st_status, st_name, st_dept, st_batch
        FROM reports" . $whereSql . "
        ORDER BY st_name ASC
        LIMIT {$perPage} OFFSET {$offset}";

$rows = [];
if($types !== '') {
  $stmt = mysqli_prepare($link, $sql);
  mysqli_stmt_bind_param($stmt, $types, ...$params);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  while($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
  }
  mysqli_stmt_close($stmt);
} else {
  $result = mysqli_query($link, $sql);
  while($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports - Admin Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .filter-grid { display:grid; grid-template-columns: 1fr 1fr auto; gap:12px; align-items:end; }
    .table-wrap { overflow-x:auto; }
  </style>
</head>
<body>
<div class="dashboard-container">
  <?php $activePage = 'reports.php'; include('sidebar.php'); ?>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-file-alt"></i> Reports</h1>
        <p style="color: var(--muted); margin: 0;">View student academic reports</p>
      </div>
      <div class="user-menu">
        <span>👨‍💼 Admin</span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">

      <?php if(isset($success_msg) && $success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
      <?php endif; ?>
      <?php if(isset($error_msg) && $error_msg): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header"><i class="fas fa-filter"></i> Filters</div>
        <div class="card-body">
          <form method="get" action="">
            <div class="filter-grid">
              <div class="form-group">
                <label for="course">Course</label>
                <input type="text" id="course" name="course" class="form-control" placeholder="e.g., algo" value="<?php echo $course; ?>">
              </div>
              <div class="form-group">
                <label for="st_dept">Department</label>
                <input type="text" id="st_dept" name="st_dept" class="form-control" placeholder="e.g., CSE" value="<?php echo $st_dept; ?>">
              </div>
              <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="card" style="margin-top:30px;">
        <div class="card-header"><i class="fas fa-list"></i> Results (<?php echo count($rows); ?>)</div>
        <div class="card-body">
          <div class="table-wrap">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Student ID</th>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Batch</th>
                  <th>Course</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if(count($rows) > 0): ?>
                  <?php foreach($rows as $r): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($r['st_id'] ?? ''); ?></strong></td>
                      <td><?php echo htmlspecialchars($r['st_name'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($r['st_dept'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($r['st_batch'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($r['course'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($r['st_status'] ?? ''); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="6" class="text-center text-muted"><i class="fas fa-inbox"></i> No reports found</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <?php if($totalPages > 1): ?>
            <div style="margin-top: 14px; display:flex; gap:10px; align-items:center; justify-content:flex-end;">
              <span style="color: var(--muted); font-weight:700;">Page <?php echo $page; ?> / <?php echo $totalPages; ?></span>
              <?php if($page > 1): ?>
                <a class="btn btn-light" href="?course=<?php echo urlencode($course); ?>&st_dept=<?php echo urlencode($st_dept); ?>&page=<?php echo $page-1; ?>"><i class="fas fa-chevron-left"></i> Prev</a>
              <?php endif; ?>
              <?php if($page < $totalPages): ?>
                <a class="btn btn-light" href="?course=<?php echo urlencode($course); ?>&st_dept=<?php echo urlencode($st_dept); ?>&page=<?php echo $page+1; ?>">Next <i class="fas fa-chevron-right"></i></a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
