<?php
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

// Dashboard Summary data
$dashboard = [
  'attendance_percentage' => 0.0,
  'programs_enrolled_count' => 0,
  'next_exam' => null,
  'payment_status' => 'Paid',
  'payment_outstanding' => 0.0,
  'recent_results' => [],
  'latest_announcements' => [],
];

// 1) Programs enrolled count
$progCountSql = "SELECT COUNT(*) AS c
                 FROM student_enrollments
                 WHERE st_id = ? AND status='active'";
$stmt = mysqli_prepare($link, $progCountSql);
mysqli_stmt_bind_param($stmt, "s", $stId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $progCount);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$dashboard['programs_enrolled_count'] = (int)($progCount ?? 0);

// 2) Attendance percentage (last 30 days) - legacy attendance table
$attSql = "SELECT
    SUM(CASE WHEN st_status IN ('present','Present','late','half-day','Half-day') THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN st_status IN ('absent','Absent') THEN 1 ELSE 0 END) AS absent_count,
    COUNT(*) AS total_count
  FROM attendance
  WHERE stat_id = ?
    AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$stmt = mysqli_prepare($link, $attSql);
mysqli_stmt_bind_param($stmt, "s", $stId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $presentCount, $absentCount, $totalCount);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$totalCount = (int)($totalCount ?? 0);
$presentCount = (int)($presentCount ?? 0);
$dashboard['attendance_percentage'] = $totalCount > 0 ? round(($presentCount / $totalCount) * 100, 1) : 0.0;

// 3) Payment status
$paySql = "SELECT
    SUM(total_due) AS total_due,
    SUM(total_paid) AS total_paid
  FROM student_fee_balances
  WHERE st_id = ?";
$stmt = mysqli_prepare($link, $paySql);
mysqli_stmt_bind_param($stmt, "s", $stId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $totalDue, $totalPaid);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$totalDue = (float)($totalDue ?? 0);
$totalPaid = (float)($totalPaid ?? 0);
$dashboard['payment_outstanding'] = max(0, $totalDue - $totalPaid);
$dashboard['payment_status'] = $dashboard['payment_outstanding'] > 0 ? 'Due' : 'Paid';

// 4) Next upcoming exam for this student (derived from program/batches in enrollments)
$enrollSql = "SELECT program_id, batch_id
              FROM student_enrollments
              WHERE st_id = ? AND status='active'";
$stmt = mysqli_prepare($link, $enrollSql);
mysqli_stmt_bind_param($stmt, "s", $stId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$programBatches = [];
while ($row = mysqli_fetch_assoc($res)) $programBatches[] = $row;
mysqli_stmt_close($stmt);

if (count($programBatches) > 0) {
  $clauses = [];
  $types = '';
  $binds = [];
  foreach ($programBatches as $pb) {
    $clauses[] = "(e.program_id = ? AND e.batch_id = ?)";
    $types .= "ss";
    $binds[] = $pb['program_id'];
    $binds[] = $pb['batch_id'];
  }
  $wherePairs = implode(" OR ", $clauses);

  $nextExamSql = "SELECT e.exam_id, ec.exam_category_name, e.exam_name, e.exam_date,
                          e.mcq_marks, e.written_marks
                  FROM exams e
                  JOIN exam_categories ec ON e.exam_category_id = ec.exam_category_id
                  WHERE ($wherePairs) AND e.exam_date >= CURDATE()
                  ORDER BY e.exam_date ASC
                  LIMIT 1";
  $stmt = mysqli_prepare($link, $nextExamSql);
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$binds);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($r)) $dashboard['next_exam'] = $row;
    mysqli_stmt_close($stmt);
  }
}

// 5) Recent results (from marks_entries + exams) - only if marks_entries table exists
$hasMarksEntries = false;
$tblCheck = mysqli_query($link, "SHOW TABLES LIKE 'marks_entries'");
if ($tblCheck && mysqli_num_rows($tblCheck) > 0) {
  $hasMarksEntries = true;
}

if ($hasMarksEntries) {
  $recentResultsSql = "SELECT e.exam_id, e.exam_name, e.exam_date, ec.exam_category_name,
                               me.total_obtained
                        FROM marks_entries me
                        JOIN exams e ON e.exam_id = me.exam_id
                        JOIN exam_categories ec ON e.exam_category_id = ec.exam_category_id
                        WHERE me.st_id = ?
                        ORDER BY e.exam_date DESC
                        LIMIT 5";
  $stmt = mysqli_prepare($link, $recentResultsSql);
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $stId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) $dashboard['recent_results'][] = $row;
    mysqli_stmt_close($stmt);
  }
}

// 6) Latest announcements
$annSql = "SELECT announcement_id, title, published_date, published_by, announcement_type, priority
          FROM announcements
          WHERE is_active = 1
            AND (expiry_date IS NULL OR expiry_date >= CURDATE())
          ORDER BY published_date DESC
          LIMIT 5";
$res = mysqli_query($link, $annSql);
if ($res) {
  while ($row = mysqli_fetch_assoc($res)) $dashboard['latest_announcements'][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - Attendance Management</title>
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
      <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">ACADEMICS</li>
      <li><a href="academics.php"><i class="fas fa-book-open"></i> Academic Information</a></li>
      <li><a href="exams_results.php"><i class="fas fa-sticky-note"></i> Exam & Results</a></li>
      <li><a href="attendance_report_page.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
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
        <h1><i class="fas fa-graduation-cap"></i> Welcome, <?php echo htmlspecialchars($stId); ?></h1>
        <p style="color: var(--muted); margin: 0;">Your academic dashboard summary</p>
      </div>
      <div class="user-menu">
        <span>👨‍🎓 <?php echo htmlspecialchars($stId); ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
          <div class="stat-content">
            <div class="stat-label">Current Programs</div>
            <div class="stat-value"><?php echo (int)$dashboard['programs_enrolled_count']; ?></div>
          </div>
        </div>

        <div class="stat-card info">
          <div class="stat-icon"><i class="fas fa-percentage"></i></div>
          <div class="stat-content">
            <div class="stat-label">Attendance % (30 days)</div>
            <div class="stat-value"><?php echo htmlspecialchars((string)$dashboard['attendance_percentage']); ?>%</div>
          </div>
        </div>

        <div class="stat-card <?php echo $dashboard['payment_status'] === 'Due' ? 'warning' : 'success'; ?>">
          <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
          <div class="stat-content">
            <div class="stat-label">Payment Status</div>
            <div class="stat-value"><?php echo htmlspecialchars($dashboard['payment_status']); ?></div>
          </div>
        </div>

        <div class="stat-card success">
          <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
          <div class="stat-content">
            <div class="stat-label">Recent Results (Count)</div>
            <div class="stat-value"><?php echo (int)count($dashboard['recent_results']); ?></div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-calendar-alt"></i> Next Exam</div>
        <div class="card-body">
          <?php if ($dashboard['next_exam'] === null): ?>
            <div class="alert alert-info">No upcoming exams found.</div>
          <?php else: ?>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 18px;">
              <div>
                <div style="color: var(--muted); font-size: var(--font-sm);">Exam</div>
                <div style="font-size: var(--font-xl); font-weight: 800;">
                  <?php echo htmlspecialchars($dashboard['next_exam']['exam_name']); ?>
                </div>
                <div style="margin-top: 6px; color: var(--muted);">
                  Category: <?php echo htmlspecialchars($dashboard['next_exam']['exam_category_name']); ?>
                </div>
              </div>
              <div>
                <div style="color: var(--muted); font-size: var(--font-sm);">Date</div>
                <div style="font-size: var(--font-xl); font-weight: 800;">
                  <?php echo htmlspecialchars($dashboard['next_exam']['exam_date']); ?>
                </div>
                <div style="margin-top: 6px; color: var(--muted);">
                  Marks split: <?php echo htmlspecialchars($dashboard['next_exam']['mcq_marks']); ?> / <?php echo htmlspecialchars($dashboard['next_exam']['written_marks']); ?>
                </div>
              </div>
            </div>
            <div style="margin-top: 16px;">
              <a class="btn btn-primary" href="exams_results.php">
                <i class="fas fa-sticky-note"></i> View Exams & Results
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-bullhorn"></i> Latest Announcements</div>
        <div class="card-body">
          <?php if (count($dashboard['latest_announcements']) === 0): ?>
            <div class="alert alert-info">No announcements available.</div>
          <?php else: ?>
            <div style="display:grid; gap: 12px;">
              <?php foreach ($dashboard['latest_announcements'] as $a): ?>
                <div style="border: 1px solid var(--border-light); padding: 14px; border-radius: var(--radius);">
                  <div style="display:flex; justify-content:space-between; gap: 12px; flex-wrap:wrap;">
                    <div style="font-weight: 800;">
                      <i class="fas fa-flag"></i> <?php echo htmlspecialchars($a['title']); ?>
                    </div>
                    <div style="color: var(--muted); font-size: var(--font-sm);">
                      <?php echo htmlspecialchars($a['published_date']); ?> • <?php echo htmlspecialchars($a['published_by']); ?>
                    </div>
                  </div>
                  <div style="margin-top: 8px; color: var(--muted); font-size: var(--font-sm);">
                    Type: <?php echo htmlspecialchars($a['announcement_type']); ?> • Priority: <?php echo htmlspecialchars($a['priority']); ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div style="margin-top: 16px;">
              <a class="btn btn-secondary" href="notices.php">
                <i class="fas fa-bullhorn"></i> View Notices
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-bolt"></i> Quick Actions</div>
        <div class="card-body">
          <div class="quick-actions-grid">
            <a href="account.php" class="btn btn-primary"><i class="fas fa-user-edit"></i> My Profile</a>
            <a href="attendance_report_page.php" class="btn btn-info"><i class="fas fa-calendar-check"></i> Attendance</a>
            <a href="exams_results.php" class="btn btn-success"><i class="fas fa-sticky-note"></i> Exams & Results</a>
            <a href="payments.php" class="btn btn-warning"><i class="fas fa-credit-card"></i> Payment & Fees</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
