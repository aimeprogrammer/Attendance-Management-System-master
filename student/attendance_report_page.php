<?php
ob_start();
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'student') {
  header('location: ../index.php');
  exit;
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

include('connect.php');

$stId = $_SESSION['st_id'] ?? '';
if ($stId === '') {
  header('location: dashboard.php');
  exit;
}

$fromDate = $_GET['from'] ?? '';
$toDate = $_GET['to'] ?? '';
$selectedProgram = $_GET['program_id'] ?? '';

$attendanceRows = [];
$stats = [
  'present' => 0,
  'absent' => 0,
  'total' => 0,
  'percentage' => 0.0,
];

// Note: current attendance schema uses legacy columns: course + st_status.
// Program/batch filtering for attendance is not fully modeled in schema; keep program filter stub-safe.
$dateWhere = '';
$params = [];
$types = '';

if ($fromDate !== '' && $toDate !== '') {
  $dateWhere = ' AND stat_date BETWEEN ? AND ? ';
  $types .= 'ss';
  $params[] = $fromDate;
  $params[] = $toDate;
}

// Stats (all courses)
$statsSql = "SELECT
  SUM(CASE WHEN st_status IN ('present','Present','late','half-day','Half-day') THEN 1 ELSE 0 END) AS present_count,
  SUM(CASE WHEN st_status IN ('absent','Absent') THEN 1 ELSE 0 END) AS absent_count,
  COUNT(*) AS total_count
  FROM attendance
  WHERE stat_id = ? $dateWhere";

$stmt = mysqli_prepare($link, $statsSql);
if ($stmt) {
  // bind stId first, then optional date params
  $bindTypes = 's' . $types;
  $bindVals = array_merge([$stId], $params);
  mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindVals);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $presentCount, $absentCount, $totalCount);
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);

  $stats['present'] = (int)($presentCount ?? 0);
  $stats['absent'] = (int)($absentCount ?? 0);
  $stats['total'] = (int)($totalCount ?? 0);
  $stats['percentage'] = $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100, 1) : 0.0;
}

// Attendance records (last 50 or within date range)
$limit = 50;
$rowsSql = "SELECT stat_date, course, st_status, check_in_time, remarks
            FROM attendance
            WHERE stat_id = ? $dateWhere
            ORDER BY stat_date DESC
            LIMIT $limit";

$stmt = mysqli_prepare($link, $rowsSql);
if ($stmt) {
  $bindTypes = 's' . $types;
  $bindVals = array_merge([$stId], $params);
  mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindVals);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($res)) {
    $attendanceRows[] = $row;
  }
  mysqli_stmt_close($stmt);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance - Student Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <i class="fas fa-graduation-cap"></i>
        <span>Student Portal</span>
      </div>
    </div>
    <ul class="sidebar-menu">
      <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">ACADEMICS</li>
      <li><a href="academics.php"><i class="fas fa-book-open"></i> Academic Information</a></li>
      <li><a href="exams_results.php"><i class="fas fa-sticky-note"></i> Exam & Results</a></li>
      <li><a href="attendance_report_page.php" class="active"><i class="fas fa-calendar-check"></i> Attendance</a></li>
      <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payment & Fees</a></li>
      <li><a href="notices.php"><i class="fas fa-bullhorn"></i> Notices & Announcements</a></li>
      <li><a href="communications.php"><i class="fas fa-comments"></i> Communications</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">MY RECORDS</li>
      <li><a href="account.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
      <li><a href="report.php"><i class="fas fa-chart-bar"></i> Attendance Report</a></li>
      <li><a href="students.php"><i class="fas fa-users"></i> Class Directory</a></li>
      <li><a href="leave.php"><i class="fas fa-calendar"></i> Leave Requests</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">NOTIFICATIONS</li>
      <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>

      <li><hr style="margin: 15px 0; border: none; border-top: 1px solid rgba(255,255,255,0.1);"></li>
      <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-calendar-check"></i> Attendance</h1>
        <p style="color: var(--muted); margin: 0;">Record view, attendance % and download-ready export stub</p>
      </div>
      <div class="user-menu">
        <span>👨‍🎓 <?php echo htmlspecialchars($stId); ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">

      <div class="card">
        <div class="card-header"><i class="fas fa-filter"></i> Filter by Date Range</div>
        <div class="card-body">
          <form method="get" action="">
            <div style="display:flex; gap:15px; flex-wrap:wrap; align-items:end;">
              <div class="form-group" style="margin:0;">
                <label for="from"><i class="fas fa-calendar"></i> From</label>
                <input type="date" name="from" id="from" class="form-control" value="<?php echo htmlspecialchars($fromDate); ?>">
              </div>
              <div class="form-group" style="margin:0;">
                <label for="to"><i class="fas fa-calendar"></i> To</label>
                <input type="date" name="to" id="to" class="form-control" value="<?php echo htmlspecialchars($toDate); ?>">
              </div>
              <button type="submit" class="btn btn-primary" style="height:44px;">
                <i class="fas fa-search"></i> Apply
              </button>
              <a href="attendance_report_page.php" class="btn btn-light" style="height:44px;">
                <i class="fas fa-undo"></i> Reset
              </a>
            </div>
          </form>
        </div>
      </div>

      <div class="stats-grid" style="margin-top: 30px;">
        <div class="stat-card success">
          <div class="stat-icon"><i class="fas fa-user-check"></i></div>
          <div class="stat-content">
            <div class="stat-label">Present</div>
            <div class="stat-value"><?php echo (int)$stats['present']; ?></div>
          </div>
        </div>
        <div class="stat-card danger">
          <div class="stat-icon"><i class="fas fa-user-times"></i></div>
          <div class="stat-content">
            <div class="stat-label">Absent</div>
            <div class="stat-value"><?php echo (int)$stats['absent']; ?></div>
          </div>
        </div>
        <div class="stat-card info">
          <div class="stat-icon"><i class="fas fa-list"></i></div>
          <div class="stat-content">
            <div class="stat-label">Total</div>
            <div class="stat-value"><?php echo (int)$stats['total']; ?></div>
          </div>
        </div>
        <div class="stat-card primary">
          <div class="stat-icon"><i class="fas fa-percentage"></i></div>
          <div class="stat-content">
            <div class="stat-label">Attendance Rate</div>
            <div class="stat-value"><?php echo htmlspecialchars((string)$stats['percentage']); ?>%</div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-list"></i> Attendance Records</div>
        <div class="card-body">
          <?php if (count($attendanceRows) === 0): ?>
            <div class="alert alert-info">No attendance records found for this filter.</div>
          <?php else: ?>
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Course</th>
                  <th>Status</th>
                  <th>Check-in</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($attendanceRows as $r): ?>
                  <?php
                    $statusLower = strtolower((string)$r['st_status']);
                    $statusClass = in_array($statusLower, ['present','late','half-day','half-day']) ? 'success' : 'danger';
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars((string)$r['stat_date']); ?></td>
                    <td><?php echo htmlspecialchars((string)$r['course']); ?></td>
                    <td><span class="badge badge-<?php echo $statusClass; ?>"><?php echo htmlspecialchars((string)$r['st_status']); ?></span></td>
                    <td><?php echo htmlspecialchars((string)($r['check_in_time'] ?? '-')); ?></td>
                    <td><small><?php echo htmlspecialchars((string)($r['remarks'] ?? '-')); ?></small></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-download"></i> Download Certificate / Export</div>
        <div class="card-body">
          <div class="alert alert-warning">
            Certificate/export is a stub-safe UI. Attendance PDF generation is not implemented in this repo version.
          </div>
          <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <button class="btn btn-success" disabled><i class="fas fa-certificate"></i> Download Attendance Certificate</button>
            <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print View</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
<?php
// Ensure no unexpected EOF parsing issues in some PHP runtimes
?>
