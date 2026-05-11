<?php
ob_start();
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'student') {
  header('location: ../index.php');
  exit;
}

include('connect.php');

$stId = $_SESSION['st_id'] ?? '';
if ($stId === '') {
  header('location: dashboard.php');
  exit;
}

$programFilter = $_GET['program_id'] ?? '';
$results = [];

// Notices/announcements (schema_extensions.sql)
$sql = "SELECT announcement_id, title, content, announcement_type, priority, visibility, published_by, published_date, expiry_date, is_active
        FROM announcements
        WHERE is_active = 1
          AND (expiry_date IS NULL OR expiry_date >= CURDATE())";

$params = [];
$types = '';

if ($programFilter !== '') {
  $sql .= " AND program_id = ?";
  $params[] = $programFilter;
  $types = 's';
}

$sql .= " ORDER BY published_date DESC LIMIT 50";

$stmt = mysqli_prepare($link, $sql);
if ($stmt) {
  if ($types !== '' && count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
  }
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($res)) {
    $results[] = $row;
  }
  mysqli_stmt_close($stmt);
}

// Programs list for filter UI
$programs = [];
$progSql = "SELECT program_id, program_name FROM programs ORDER BY program_name ASC";
$progRes = mysqli_query($link, $progSql);
if ($progRes) {
  while ($p = mysqli_fetch_assoc($progRes)) $programs[] = $p;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notices & Announcements - Student Dashboard</title>
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
      <li><a href="attendance_report_page.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
      <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payment & Fees</a></li>
      <li><a href="notices.php" class="active"><i class="fas fa-bullhorn"></i> Notices & Announcements</a></li>
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
        <h1><i class="fas fa-bullhorn"></i> Notices & Announcements</h1>
        <p style="color: var(--muted); margin: 0;">Filter and view latest notices from admin/teachers</p>
      </div>
      <div class="user-menu">
        <span>👨‍🎓 <?php echo htmlspecialchars($stId); ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">

      <div class="card">
        <div class="card-header"><i class="fas fa-filter"></i> Filters</div>
        <div class="card-body">
          <form method="get" action="">
            <div style="display:flex; gap:15px; flex-wrap:wrap; align-items:end;">
              <div style="min-width: 260px;">
                <label for="program_id"><i class="fas fa-book"></i> Program</label>
                <select id="program_id" name="program_id" class="form-control">
                  <option value="">All programs (stub)</option>
                  <?php foreach ($programs as $p): ?>
                    <option value="<?php echo htmlspecialchars($p['program_id']); ?>" <?php echo ($programFilter === $p['program_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($p['program_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button class="btn btn-primary" type="submit" style="height:44px;">
                <i class="fas fa-search"></i> Apply
              </button>
              <a class="btn btn-light" href="notices.php" style="height:44px;">
                <i class="fas fa-undo"></i> Reset
              </a>
            </div>
          </form>
          <div class="alert alert-success" style="margin-top: 14px;">
            Program filtering is enabled (scoped by announcement <code>program_id</code>). Leave “All programs” for global notices.
          </div>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-inbox"></i> Latest Notices</div>
        <div class="card-body">
          <?php if (count($results) === 0): ?>
            <div class="alert alert-info">No active announcements found.</div>
          <?php else: ?>
            <?php foreach ($results as $n): ?>
              <div style="border: 1px solid var(--border-light); padding: 16px; border-radius: var(--radius); margin-bottom: 16px;">
                <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;">
                  <div>
                    <h3 style="margin: 0 0 6px 0; font-size: var(--font-xl);">
                      <i class="fas fa-flag"></i> <?php echo htmlspecialchars($n['title']); ?>
                    </h3>
                    <div style="color: var(--muted); font-size: var(--font-sm);">
                      Published: <?php echo htmlspecialchars($n['published_date']); ?> • By: <?php echo htmlspecialchars($n['published_by']); ?>
                    </div>
                  </div>
                  <div>
                    <?php
                      $type = $n['announcement_type'] ?? 'announcement';
                      $priority = $n['priority'] ?? 'medium';
                      $prioClass = $priority === 'high' ? 'danger' : ($priority === 'low' ? 'info' : 'warning');
                    ?>
                    <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                      <span class="label label-<?php echo $prioClass; ?>"><?php echo htmlspecialchars(ucfirst($priority)); ?> priority</span>
                      <span class="label label-primary"><?php echo htmlspecialchars($type); ?></span>
                    </div>
                  </div>
                </div>

                <div style="margin-top: 12px; color: var(--text);">
                  <div style="white-space: pre-wrap;"><?php echo htmlspecialchars($n['content']); ?></div>
                </div>

                <div style="margin-top: 12px; display:flex; gap:10px; flex-wrap:wrap;">
                  <button class="btn btn-secondary btn-sm" disabled>
                    <i class="fas fa-download"></i> Download
                  </button>
                  <button class="btn btn-secondary btn-sm" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
