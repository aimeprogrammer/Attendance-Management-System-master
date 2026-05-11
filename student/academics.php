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
  // Fallback: some legacy pages use students id from session name area; but we require option 1.
  $stId = $_GET['st_id'] ?? '';
}

$programs = [];
$batches = [];
$subjects = [];
$academicCalendar = []; // stub-safe

if ($stId !== '') {
  // Enrolled programs/courses
  $sql = "SELECT p.program_id, p.program_name, se.semester, se.admission_date, se.status
          FROM student_enrollments se
          JOIN programs p ON se.program_id = p.program_id
          WHERE se.st_id = ? AND se.status = 'active'
          ORDER BY se.admission_date DESC";
  $stmt = mysqli_prepare($link, $sql);
  mysqli_stmt_bind_param($stmt, "s", $stId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($res)) {
    $programs[] = $row;
  }
  mysqli_stmt_close($stmt);

  // Batch/class schedule (based on enrollment batch)
  if (count($programs) > 0) {
    $batchSql = "SELECT b.batch_id, b.batch_name, b.start_year, b.end_year, se.program_id
                  FROM student_enrollments se
                  JOIN batches b ON se.batch_id = b.batch_id
                  WHERE se.st_id = ? AND se.status='active'
                  ORDER BY se.admission_date DESC";
    $stmt = mysqli_prepare($link, $batchSql);
    mysqli_stmt_bind_param($stmt, "s", $stId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
      $batches[] = $row;
    }
    mysqli_stmt_close($stmt);
  }

  // Subjects enrolled:
  // We derive subjects from batch_subjects (batch_id -> subject_id) joined to subjects.
  // If a student has multiple batches, we aggregate subjects across their batches.
  $subjectSql = "SELECT DISTINCT s.subject_id, s.subject_code, s.subject_name, b.batch_id
                 FROM student_enrollments se
                 JOIN batches b ON se.batch_id = b.batch_id
                 JOIN batch_subjects bs ON bs.batch_id = b.batch_id
                 JOIN subjects s ON s.subject_id = bs.subject_id
                 WHERE se.st_id = ? AND se.status='active'
                 ORDER BY s.subject_code ASC";
  $stmt = mysqli_prepare($link, $subjectSql);
  mysqli_stmt_bind_param($stmt, "s", $stId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($res)) {
    $subjects[] = $row;
  }
  mysqli_stmt_close($stmt);
}

// Academic calendar is not fully modeled in schema; keep stub page.
$academicCalendar = [
  ['event' => 'Academic calendar (coming soon)', 'date' => date('Y-m-d'), 'type' => 'info'],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Academic Information - Student Dashboard</title>
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
      <li><a href="academics.php" class="active"><i class="fas fa-book-open"></i> Academic Information</a></li>
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
        <h1><i class="fas fa-book-open"></i> Academic Information</h1>
        <p style="color: var(--muted); margin: 0;">Programs, batch/class schedule, subjects, and academic calendar</p>
      </div>
      <div class="user-menu">
        <span>👨‍🎓 <?php echo htmlspecialchars($_SESSION['st_id'] ?? $stId); ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <?php if ($stId === ''): ?>
        <div class="alert alert-danger">Missing student id in session. Please login again.</div>
      <?php else: ?>

        <div class="card">
          <div class="card-header"><i class="fas fa-layer-group"></i> Enrolled Programs / Courses</div>
          <div class="card-body">
            <?php if (count($programs) === 0): ?>
              <div class="alert alert-info">No active program enrollment found.</div>
            <?php else: ?>
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Program ID</th>
                    <th>Program Name</th>
                    <th>Semester</th>
                    <th>Admission Date</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($programs as $p): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($p['program_id']); ?></td>
                    <td><?php echo htmlspecialchars($p['program_name']); ?></td>
                    <td><?php echo htmlspecialchars((string)$p['semester']); ?></td>
                    <td><?php echo htmlspecialchars($p['admission_date']); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>

        <div class="card" style="margin-top: 30px;">
          <div class="card-header"><i class="fas fa-graduation-cap"></i> Batch/Class Schedule</div>
          <div class="card-body">
            <?php if (count($batches) === 0): ?>
              <div class="alert alert-info">No batch schedule found.</div>
            <?php else: ?>
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Batch ID</th>
                    <th>Batch Name</th>
                    <th>Start Year</th>
                    <th>End Year</th>
                    <th>Program ID</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($batches as $b): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($b['batch_id']); ?></td>
                    <td><?php echo htmlspecialchars($b['batch_name']); ?></td>
                    <td><?php echo htmlspecialchars((string)($b['start_year'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string)($b['end_year'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($b['program_id']); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>

        <div class="card" style="margin-top: 30px;">
          <div class="card-header"><i class="fas fa-list-ul"></i> Subjects Enrolled</div>
          <div class="card-body">
            <?php if (count($subjects) === 0): ?>
              <div class="alert alert-info">No subjects assigned for your enrolled batch(es).</div>
            <?php else: ?>
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Batch ID</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($subjects as $s): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($s['subject_code']); ?></td>
                    <td><?php echo htmlspecialchars($s['subject_name']); ?></td>
                    <td><?php echo htmlspecialchars($s['batch_id']); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>

            <?php
            // Course materials for subjects tied to the student's enrolled batch(es)
            $materials = [];

            if (count($subjects) > 0) {
              // Build a subject_id set from subjects already fetched.
              $subjectIds = array_values(array_unique(array_map(function ($s) {
                return $s['subject_id'];
              }, $subjects)));

              // If we have batches, prefer filtering by batch_id too.
              $batchIds = [];
              if (count($batches) > 0) {
                $batchIds = array_values(array_unique(array_map(function ($b) {
                  return $b['batch_id'];
                }, $batches)));
              }

              // Note: MariaDB doesn't allow binding arrays directly; build placeholders carefully.
              $placeholdersSub = implode(',', array_fill(0, count($subjectIds), '?'));

              if (count($batchIds) > 0) {
                $placeholdersBatch = implode(',', array_fill(0, count($batchIds), '?'));

                $sqlMaterials = "SELECT cm.material_id,
                                        cm.subject_id,
                                        cm.batch_id,
                                        cm.title,
                                        cm.description,
                                        cm.file_path,
                                        cm.file_type,
                                        cm.external_url,
                                        cm.uploaded_at,
                                        cm.is_active,
                                        s.subject_code,
                                        s.subject_name
                                 FROM course_materials cm
                                 JOIN subjects s ON s.subject_id = cm.subject_id
                                 WHERE cm.is_active = 1
                                   AND cm.subject_id IN ($placeholdersSub)
                                   AND (cm.batch_id IN ($placeholdersBatch) OR cm.batch_id IS NULL)
                                 ORDER BY cm.uploaded_at DESC";

                $stmt = mysqli_prepare($link, $sqlMaterials);

                // Bind params: first subject_ids then batch_ids.
                $types = str_repeat('s', count($subjectIds) + count($batchIds));
                $bindValues = array_merge($subjectIds, $batchIds);
                mysqli_stmt_bind_param($stmt, $types, ...$bindValues);

                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($res)) {
                  $materials[] = $row;
                }
                mysqli_stmt_close($stmt);
              } else {
                $sqlMaterials = "SELECT cm.material_id,
                                        cm.subject_id,
                                        cm.batch_id,
                                        cm.title,
                                        cm.description,
                                        cm.file_path,
                                        cm.file_type,
                                        cm.external_url,
                                        cm.uploaded_at,
                                        cm.is_active,
                                        s.subject_code,
                                        s.subject_name
                                 FROM course_materials cm
                                 JOIN subjects s ON s.subject_id = cm.subject_id
                                 WHERE cm.is_active = 1
                                   AND cm.subject_id IN ($placeholdersSub)
                                 ORDER BY cm.uploaded_at DESC";

                $stmt = mysqli_prepare($link, $sqlMaterials);
                $types = str_repeat('s', count($subjectIds));
                mysqli_stmt_bind_param($stmt, $types, ...$subjectIds);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($res)) {
                  $materials[] = $row;
                }
                mysqli_stmt_close($stmt);
              }
            }
            ?>

            <div style="margin-top: 18px;" class="card" >
              <div class="card-header"><i class="fas fa-folder-open"></i> Course Materials</div>
              <div class="card-body">
                <?php if (count($materials) === 0): ?>
                  <div class="alert alert-info">No course materials uploaded for your subjects yet.</div>
                <?php else: ?>
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Batch</th>
                        <th>Uploaded</th>
                        <th>File</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($materials as $m): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($m['title']); ?></td>
                          <td><?php echo htmlspecialchars($m['subject_code'] . ' - ' . $m['subject_name']); ?></td>
                          <td><?php echo htmlspecialchars((string)($m['batch_id'] ?? 'All')); ?></td>
                          <td><?php echo htmlspecialchars($m['uploaded_at'] ?? ''); ?></td>
                          <td>
                            <?php
                              $filePath = $m['file_path'] ?? '';
                              $externalUrl = $m['external_url'] ?? '';
                              $hasLocal = $filePath !== '';
                              $hasExternal = $externalUrl !== '';

                              if ($hasLocal): ?>
                                <a href="<?php echo htmlspecialchars('../' . $filePath); ?>" target="_blank" rel="noopener">
                                  <i class="fas fa-file-download"></i> Download
                                </a>
                              <?php elseif ($hasExternal): ?>
                                <a href="<?php echo htmlspecialchars($externalUrl); ?>" target="_blank" rel="noopener">
                                  <i class="fas fa-link"></i> Open
                                </a>
                              <?php else: ?>
                                <span style="color: var(--muted);">—</span>
                              <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="card" style="margin-top: 30px;">
          <div class="card-header"><i class="fas fa-calendar-alt"></i> Academic Calendar</div>
          <div class="card-body">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Event</th>
                  <th>Type</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($academicCalendar as $e): ?>
                <tr>
                  <td><?php echo htmlspecialchars($e['date']); ?></td>
                  <td><?php echo htmlspecialchars($e['event']); ?></td>
                  <td><?php echo htmlspecialchars($e['type']); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
