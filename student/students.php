<?php
ob_start();
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'student') {
  header('location: ../index.php');
  exit;
}

include('connect.php');

$stName = $_SESSION['name'] ?? '';
$stId = $_SESSION['st_id'] ?? '';

$results = [];
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sr_btn'])) {
  $searched = true;
  $srBatch = trim((string)($_POST['sr_batch'] ?? ''));
  if ($srBatch !== '') {
    $sql = "SELECT st_id, st_name, st_dept, st_batch, st_sem, st_email
            FROM students
            WHERE st_batch = ?
            ORDER BY st_id ASC
            LIMIT 200";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "s", $srBatch);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) $results[] = $row;
    mysqli_stmt_close($stmt);
  }
}

if (!$searched || count($results) === 0) {
  // Show all students by default (or if filter returned nothing)
  $sql = "SELECT st_id, st_name, st_dept, st_batch, st_sem, st_email
          FROM students
          ORDER BY st_id ASC
          LIMIT 50";
  $res = mysqli_query($link, $sql);
  if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $results[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Class Directory - Student Dashboard</title>
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
      <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>

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
      <li><a href="students.php" class="active"><i class="fas fa-users"></i> Class Directory</a></li>
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
        <h1><i class="fas fa-users"></i> Class Directory</h1>
        <p style="color: var(--muted); margin: 0;">View students and contact by email</p>
      </div>
      <div class="user-menu">
        <span>👨‍🎓 <?php echo htmlspecialchars((string)($stId !== '' ? $stId : $stName)); ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <div class="card">
        <div class="card-header">
          <i class="fas fa-filter"></i> Filter Students
        </div>
        <div class="card-body">
          <form method="post" action="">
            <div style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
              <div class="form-group" style="flex:1; min-width: 240px;">
                <label for="sr_batch"><i class="fas fa-graduation-cap"></i> Batch</label>
                <input
                  type="text"
                  id="sr_batch"
                  name="sr_batch"
                  class="form-control"
                  placeholder="e.g., 2020"
                />
              </div>
              <button type="submit" name="sr_btn" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header">
          <i class="fas fa-users"></i> Students List
        </div>
        <div class="card-body">
          <?php if (count($results) === 0): ?>
            <div class="alert alert-info">No students found.</div>
          <?php else: ?>
            <div style="overflow-x:auto;">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th style="width: 15%;">Reg. No.</th>
                    <th style="width: 25%;">Name</th>
                    <th style="width: 15%;">Department</th>
                    <th style="width: 15%;">Batch</th>
                    <th style="width: 10%;">Semester</th>
                    <th style="width: 20%;">Email</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($results as $d): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars((string)$d['st_id']); ?></strong></td>
                      <td><?php echo htmlspecialchars((string)$d['st_name']); ?></td>
                      <td><?php echo htmlspecialchars((string)$d['st_dept']); ?></td>
                      <td><?php echo htmlspecialchars((string)$d['st_batch']); ?></td>
                      <td><?php echo htmlspecialchars((string)$d['st_sem']); ?></td>
                      <td>
                        <a href="mailto:<?php echo htmlspecialchars((string)$d['st_email']); ?>">
                          <?php echo htmlspecialchars((string)$d['st_email']); ?>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header">
          <i class="fas fa-info-circle"></i> About This Directory
        </div>
        <div class="card-body">
          <ul style="list-style:none; padding:0; margin:0; line-height:1.8; color: var(--muted);">
            <li><strong style="color: #000;">✓</strong> Filter by Batch</li>
            <li><strong style="color: #000;">✓</strong> View student details</li>
            <li><strong style="color: #000;">✓</strong> Contact via email link</li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
