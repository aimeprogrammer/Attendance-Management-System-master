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

// Upcoming exams for student's program/batch (derived from enrollments)
$upcomingExams = [];
$resultsHistory = [];
$selectedExamId = $_GET['exam_id'] ?? '';

$enrollmentsSql = "SELECT program_id, batch_id
                    FROM student_enrollments
                    WHERE st_id = ? AND status='active'";
$stmt = mysqli_prepare($link, $enrollmentsSql);
mysqli_stmt_bind_param($stmt, "s", $stId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$programBatches = [];
while ($row = mysqli_fetch_assoc($res)) {
  $programBatches[] = $row;
}
mysqli_stmt_close($stmt);

// Build query using dynamic OR pairs (keep it simple and safe: limited pairs)
if (count($programBatches) > 0) {
  $clauses = [];
  $types = '';
  $binds = [];

  foreach ($programBatches as $i => $pb) {
    $clauses[] = "(e.program_id = ? AND e.batch_id = ?)";
    $types .= "ss";
    $binds[] = $pb['program_id'];
    $binds[] = $pb['batch_id'];
  }

  $wherePairs = implode(" OR ", $clauses);

  // Upcoming exams: exam_date >= today
  $sql = "SELECT e.exam_id, ec.exam_category_name, e.exam_name, e.exam_date, e.mcq_marks, e.written_marks
          FROM exams e
          JOIN exam_categories ec ON e.exam_category_id = ec.exam_category_id
          WHERE ($wherePairs) AND e.exam_date >= CURDATE()
          ORDER BY e.exam_date ASC
          LIMIT 10";

  $stmt = mysqli_prepare($link, $sql);
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$binds);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) {
      $upcomingExams[] = $row;
    }
    mysqli_stmt_close($stmt);
  }
}

$hasMarksEntries = false;
$tblCheck = mysqli_query($link, "SHOW TABLES LIKE 'marks_entries'");
if ($tblCheck && mysqli_num_rows($tblCheck) > 0) {
  $hasMarksEntries = true;
}

$hasResultsPublish = false;
$tblCheckRp = mysqli_query($link, "SHOW TABLES LIKE 'results_publish'");
if ($tblCheckRp && mysqli_num_rows($tblCheckRp) > 0) {
  $hasResultsPublish = true;
}

// Results history (only if marks_entries exists; results_publish is optional)
if ($hasMarksEntries) {
  $resultsSql = "SELECT DISTINCT me.exam_id, e.exam_name, e.exam_date,
                      ec.exam_category_name,
                      rp.published,
                      rp.published_at
                  FROM marks_entries me
                  JOIN exams e ON me.exam_id = e.exam_id
                  JOIN exam_categories ec ON e.exam_category_id = ec.exam_category_id
                  LEFT JOIN results_publish rp ON rp.exam_id = e.exam_id
                  WHERE me.st_id = ?
                  ORDER BY e.exam_date DESC
                  LIMIT 20";
  $stmt = mysqli_prepare($link, $resultsSql);
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $stId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
      $resultsHistory[] = $row;
    }
    mysqli_stmt_close($stmt);
  }
  // If results_publish table is missing, LEFT JOIN can still fail on some MySQL configs.
  // In that case, the query would have thrown; table-existence checks above keep the page stable.
}

// Marks breakdown for selected exam (only if marks_entries exists)
$marksBreakdown = [];
if ($hasMarksEntries && $selectedExamId !== '') {
  $marksSql = "SELECT s.subject_code, s.subject_name,
                       me.mcq_obtained, me.written_obtained, me.total_obtained,
                       me.grading
                FROM marks_entries me
                JOIN subjects s ON me.subject_id = s.subject_id
                WHERE me.st_id = ? AND me.exam_id = ?
                ORDER BY s.subject_code ASC";
  $stmt = mysqli_prepare($link, $marksSql);
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $stId, $selectedExamId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
      $marksBreakdown[] = $row;
    }
    mysqli_stmt_close($stmt);
  }
}

// Page layout (uses existing main.css/sidebar styles)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exam & Results - Student Dashboard</title>
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
      <li><a href="exams_results.php" class="active"><i class="fas fa-sticky-note"></i> Exam & Results</a></li>
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
        <h1><i class="fas fa-sticky-note"></i> Exam & Results</h1>
        <p style="color: var(--muted); margin: 0;">Upcoming exams, result history, and marks breakdown</p>
      </div>
      <div class="user-menu">
        <span>👨‍🎓 <?php echo htmlspecialchars($stId); ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">

      <div class="card">
        <div class="card-header"><i class="fas fa-calendar-alt"></i> Upcoming Exams</div>
        <div class="card-body">
          <?php if (count($upcomingExams) === 0): ?>
            <div class="alert alert-info">No upcoming exams found.</div>
          <?php else: ?>
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Exam</th>
                  <th>Category</th>
                  <th>Date</th>
                  <th>Marks Split</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($upcomingExams as $e): ?>
                <tr>
                  <td><?php echo htmlspecialchars($e['exam_name']); ?></td>
                  <td><?php echo htmlspecialchars($e['exam_category_name']); ?></td>
                  <td><?php echo htmlspecialchars($e['exam_date']); ?></td>
                  <td><?php echo htmlspecialchars($e['mcq_marks']); ?> / <?php echo htmlspecialchars($e['written_marks']); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-history"></i> Result History</div>
        <div class="card-body">
          <?php if (count($resultsHistory) === 0): ?>
            <div class="alert alert-info">No results published/entered yet.</div>
          <?php else: ?>
            <div style="margin-bottom: 12px;">
              <span class="label label-primary">Select an exam to view marks breakdown</span>
            </div>
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Exam</th>
                  <th>Category</th>
                  <th>Date</th>
                  <th>Published</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($resultsHistory as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r['exam_name']); ?></td>
                  <td><?php echo htmlspecialchars($r['exam_category_name']); ?></td>
                  <td><?php echo htmlspecialchars($r['exam_date']); ?></td>
                  <td>
                    <?php if ((int)($r['published'] ?? 0) === 1): ?>
                      <span class="label label-success">Published</span>
                    <?php else: ?>
                      <span class="label label-warning">Not Published</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a class="btn btn-primary btn-sm" href="?exam_id=<?php echo urlencode($r['exam_id']); ?>">
                      View Breakdown
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($selectedExamId !== ''): ?>
        <div class="card" style="margin-top: 30px;">
          <div class="card-header"><i class="fas fa-receipt"></i> Marks Breakdown (Exam ID: <?php echo htmlspecialchars($selectedExamId); ?>)</div>
          <div class="card-body">
            <?php if (count($marksBreakdown) === 0): ?>
              <div class="alert alert-info">No marks found for this exam.</div>
            <?php else: ?>
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Subject</th>
                    <th>MCQ</th>
                    <th>Written</th>
                    <th>Total</th>
                    <th>Grading</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($marksBreakdown as $m): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($m['subject_code']); ?></strong><br/>
                      <span style="color: var(--muted);"><?php echo htmlspecialchars($m['subject_name']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($m['mcq_obtained']); ?></td>
                    <td><?php echo htmlspecialchars($m['written_obtained']); ?></td>
                    <td><?php echo htmlspecialchars($m['total_obtained']); ?></td>
                    <td><?php echo htmlspecialchars((string)($m['grading'] ?? '-')); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>

              <div style="display:flex; gap: 12px; flex-wrap: wrap; margin-top: 14px;">
                <button class="btn btn-success" disabled title="Certificate generation not implemented in current repo">Download Certificate</button>
                <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
