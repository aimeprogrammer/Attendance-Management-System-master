<?php
ob_start();
session_start();

if (!isset($_SESSION['name']) || $_SESSION['name'] != 'oasis') {
  header('location: ../index.php');
  exit;
}

include('connect.php');

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

$success_msg = '';
$error_msg = '';

$examId = isset($_GET['exam_id']) ? trim($_GET['exam_id']) : '';
$subjectId = isset($_GET['subject_id']) ? trim($_GET['subject_id']) : '';
if ($examId === '' && isset($_POST['exam_id'])) $examId = trim($_POST['exam_id']);
if ($subjectId === '' && isset($_POST['subject_id'])) $subjectId = trim($_POST['subject_id']);

if ($examId === '' || $subjectId === '') {
  // allow landing page to show exam/subjects picker
}

function fetchTeacherExamList(mysqli $link): array {
  // Fallback: show all exams if teacher batching is not configured.
  $has = mysqli_query($link, "SHOW TABLES LIKE 'teacher_batches'");
  if ($has && mysqli_num_rows($has) > 0) {
    $teacherId = $_SESSION['teacher_id'] ?? null;
    if ($teacherId !== null) {
      $stmt = mysqli_prepare($link, "SELECT batch_id FROM teacher_batches WHERE teacher_id=?");
      mysqli_stmt_bind_param($stmt, "i", $teacherId);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      $batchIds = [];
      while ($row = mysqli_fetch_assoc($res)) $batchIds[] = $row['batch_id'];

      if (!empty($batchIds)) {
        $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
        $types = str_repeat('s', count($batchIds));
        $sql = "
          SELECT e.exam_id, e.exam_name, e.exam_date, e.batch_id, b.batch_name,
                 e.exam_category_id, c.exam_category_name, e.program_id, p.program_name
          FROM exams e
          LEFT JOIN batches b ON b.batch_id=e.batch_id
          LEFT JOIN exam_categories c ON c.exam_category_id=e.exam_category_id
          LEFT JOIN programs p ON p.program_id=e.program_id
          WHERE e.batch_id IN ($placeholders)
          ORDER BY e.exam_date DESC, e.exam_name ASC
        ";
        $stmt2 = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt2, $types, ...$batchIds);
        mysqli_stmt_execute($stmt2);
        $r2 = mysqli_stmt_get_result($stmt2);
        $out = [];
        while ($x = mysqli_fetch_assoc($r2)) $out[] = $x;
        return $out;
      }
    }
  }

  $sql = "
    SELECT e.exam_id, e.exam_name, e.exam_date, e.batch_id, b.batch_name,
           e.exam_category_id, c.exam_category_name, e.program_id, p.program_name
    FROM exams e
    LEFT JOIN batches b ON b.batch_id=e.batch_id
    LEFT JOIN exam_categories c ON c.exam_category_id=e.exam_category_id
    LEFT JOIN programs p ON p.program_id=e.program_id
    ORDER BY e.exam_date DESC, e.exam_name ASC
  ";
  $res = mysqli_query($link, $sql);
  $out = [];
  while ($row = mysqli_fetch_assoc($res)) $out[] = $row;
  return $out;
}

$exams = fetchTeacherExamList($link);

$exam = null;
$subject = null;
$examSubjects = [];

if ($examId !== '') {
  $stmt = mysqli_prepare($link, "SELECT * FROM exams WHERE exam_id=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "s", $examId);
  mysqli_stmt_execute($stmt);
  $exam = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
  mysqli_stmt_close($stmt);

  $stmt = mysqli_prepare(
    $link,
    "SELECT es.subject_id, s.subject_name, s.subject_code, es.mcq_allocation, es.written_allocation
     FROM exam_subjects es
     JOIN subjects s ON s.subject_id=es.subject_id
     WHERE es.exam_id=?
     ORDER BY s.subject_code ASC"
  );
  mysqli_stmt_bind_param($stmt, "s", $examId);
  mysqli_stmt_execute($stmt);
  $r = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($r)) $examSubjects[] = $row;
  mysqli_stmt_close($stmt);
}

if ($examId !== '' && $subjectId !== '') {
  $stmt = mysqli_prepare(
    $link,
    "SELECT s.subject_name, s.subject_code, es.mcq_allocation, es.written_allocation
     FROM exam_subjects es
     JOIN subjects s ON s.subject_id=es.subject_id
     WHERE es.exam_id=? AND es.subject_id=?
     LIMIT 1"
  );
  mysqli_stmt_bind_param($stmt, "ss", $examId, $subjectId);
  mysqli_stmt_execute($stmt);
  $subject = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
  mysqli_stmt_close($stmt);
}

$students = [];
$marksByStudent = []; // st_id => mcq,written,total,grading

// Class stats + export
$stats = [
  'total' => 0,
  'pass' => 0,
  'fail' => 0,
  'credit' => 0,
  'pass_rate' => 0.0,
];

if ($examId !== '' && $subjectId !== '') {
  // Build student list for this exam's batch (same as below when loading marks)
  $stmt = mysqli_prepare($link, "SELECT batch_id FROM exams WHERE exam_id=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "s", $examId);
  mysqli_stmt_execute($stmt);
  $batchRes = mysqli_stmt_get_result($stmt);
  $batchRow = mysqli_fetch_assoc($batchRes);
  mysqli_stmt_close($stmt);

  $batchIdForStats = $batchRow['batch_id'] ?? null;

  if ($batchIdForStats) {
    // load students
    $qStudentsStats = "
      SELECT s.id, s.st_id
      FROM students s
      JOIN student_batches sb ON sb.student_id = s.id
      WHERE sb.batch_id=?
    ";
    $stmtStuStats = mysqli_prepare($link, $qStudentsStats);
    mysqli_stmt_bind_param($stmtStuStats, "s", $batchIdForStats);
    mysqli_stmt_execute($stmtStuStats);
    $resStuStats = mysqli_stmt_get_result($stmtStuStats);

    $stIdsForStats = [];
    while ($sr = mysqli_fetch_assoc($resStuStats)) $stIdsForStats[] = $sr['st_id'];
    mysqli_stmt_close($stmtStuStats);

    $stats['total'] = count($stIdsForStats);

    if (!empty($stIdsForStats)) {
      $placeholders = implode(',', array_fill(0, count($stIdsForStats), '?'));
      $types = str_repeat('s', count($stIdsForStats));
      $qAgg = "
        SELECT
          SUM(CASE WHEN grading='pass' THEN 1 ELSE 0 END) AS pass_cnt,
          SUM(CASE WHEN grading='fail' THEN 1 ELSE 0 END) AS fail_cnt,
          SUM(CASE WHEN grading='credit' THEN 1 ELSE 0 END) AS credit_cnt
        FROM marks_entries
        WHERE exam_id=? AND subject_id=?
          AND st_id IN ($placeholders)
      ";
      $stmtAgg = mysqli_prepare($link, $qAgg);
      $bindParamsAgg = array_merge([$examId, $subjectId], $stIdsForStats);
      mysqli_stmt_bind_param($stmtAgg, 'ss' . $types, ...$bindParamsAgg);
      mysqli_stmt_execute($stmtAgg);
      $resAgg = mysqli_stmt_get_result($stmtAgg);
      $rowAgg = mysqli_fetch_assoc($resAgg);
      mysqli_stmt_close($stmtAgg);

      $stats['pass'] = (int)($rowAgg['pass_cnt'] ?? 0);
      $stats['fail'] = (int)($rowAgg['fail_cnt'] ?? 0);
      $stats['credit'] = (int)($rowAgg['credit_cnt'] ?? 0);

      $den = max(1, $stats['total']);
      $stats['pass_rate'] = (($stats['pass'] + $stats['credit']) / $den) * 100;
    }
  }
}

// CSV export handler
if (isset($_GET['download']) && $_GET['download'] === 'csv' && $examId !== '' && $subjectId !== '') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="results_'.$examId.'_'.$subjectId.'.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Exam ID', 'Subject Code', 'Reg No', 'Student Name', 'MCQ', 'Written', 'Total', 'Grading']);

  $subjectCodeForCsv = $subject['subject_code'] ?? $subjectId;

  // fetch students for the exam's batch
  $stmtCsvBatch = mysqli_prepare($link, "SELECT batch_id FROM exams WHERE exam_id=? LIMIT 1");
  mysqli_stmt_bind_param($stmtCsvBatch, "s", $examId);
  mysqli_stmt_execute($stmtCsvBatch);
  $resCsvBatch = mysqli_stmt_get_result($stmtCsvBatch);
  $batchRowCsv = mysqli_fetch_assoc($resCsvBatch);
  mysqli_stmt_close($stmtCsvBatch);

  $batchIdCsv = $batchRowCsv['batch_id'] ?? null;

  if (!$batchIdCsv) {
    fputcsv($out, ['ERROR', 'Missing exam batch']);
    fclose($out);
    exit;
  }

  $qCsvStudents = "
    SELECT s.st_id, s.first_name, s.last_name
    FROM students s
    JOIN student_batches sb ON sb.student_id = s.id
    WHERE sb.batch_id=?
    ORDER BY s.first_name, s.last_name
  ";
  $stmtCsvStu = mysqli_prepare($link, $qCsvStudents);
  mysqli_stmt_bind_param($stmtCsvStu, "s", $batchIdCsv);
  mysqli_stmt_execute($stmtCsvStu);
  $resCsvStu = mysqli_stmt_get_result($stmtCsvStu);

  while ($stu = mysqli_fetch_assoc($resCsvStu)) {
    $stIdCsv = $stu['st_id'];

    $mcq = $marksByStudent[$stIdCsv]['mcq'] ?? 0;
    $written = $marksByStudent[$stIdCsv]['written'] ?? 0;
    $total = $marksByStudent[$stIdCsv]['total'] ?? ($mcq + $written);
    $grading = $marksByStudent[$stIdCsv]['grading'] ?? '';

    $name = trim(($stu['first_name'] ?? '') . ' ' . ($stu['last_name'] ?? ''));
    fputcsv($out, [$examId, $subjectCodeForCsv, $stIdCsv, $name, $mcq, $written, $total, $grading]);
  }

  mysqli_stmt_close($stmtCsvStu);
  fclose($out);
  exit;
}

if ($examId !== '' && $subjectId !== '') {
  $stmt = mysqli_prepare($link, "SELECT batch_id FROM exams WHERE exam_id=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "s", $examId);
  mysqli_stmt_execute($stmt);
  $batchRes = mysqli_stmt_get_result($stmt);
  $batchRow = mysqli_fetch_assoc($batchRes);
  mysqli_stmt_close($stmt);

  $batchId = $batchRow['batch_id'] ?? null;
  if ($batchId) {
    $qStudents = "
      SELECT s.id, s.st_id, s.first_name, s.last_name
      FROM students s
      JOIN student_batches sb ON sb.student_id = s.id
      WHERE sb.batch_id=?
      ORDER BY s.first_name, s.last_name
    ";
    $stmtStu = mysqli_prepare($link, $qStudents);
    mysqli_stmt_bind_param($stmtStu, "s", $batchId);
    mysqli_stmt_execute($stmtStu);
    $resStu = mysqli_stmt_get_result($stmtStu);
    while ($sr = mysqli_fetch_assoc($resStu)) $students[] = $sr;
    mysqli_stmt_close($stmtStu);

    if (!empty($students)) {
      $placeholders = implode(',', array_fill(0, count($students), '?'));
      $types = str_repeat('s', count($students));
      $stIds = array_map(fn($x) => $x['st_id'], $students);

      $qMarks = "
        SELECT st_id, mcq_obtained, written_obtained, total_obtained, grading
        FROM marks_entries
        WHERE exam_id=? AND subject_id=? AND st_id IN ($placeholders)
      ";
      $stmtMarks = mysqli_prepare($link, $qMarks);
      $bindParams = array_merge([$examId, $subjectId], $stIds);
      mysqli_stmt_bind_param($stmtMarks, 'ss' . $types, ...$bindParams);
      mysqli_stmt_execute($stmtMarks);
      $resMarks = mysqli_stmt_get_result($stmtMarks);

      while ($mr = mysqli_fetch_assoc($resMarks)) {
        $marksByStudent[$mr['st_id']] = [
          'mcq' => (float)$mr['mcq_obtained'],
          'written' => (float)$mr['written_obtained'],
          'total' => (float)$mr['total_obtained'],
          'grading' => $mr['grading'],
        ];
      }
      mysqli_stmt_close($stmtMarks);
    }
  }
}

// Save updates (recheck/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_recheck'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error_msg = 'Security validation failed (CSRF).';
  } else {
    $enteredBy = $_SESSION['name'] ?? 'teacher';
    $mcqByStudent = isset($_POST['mcq']) && is_array($_POST['mcq']) ? $_POST['mcq'] : [];
    $writtenByStudent = isset($_POST['written']) && is_array($_POST['written']) ? $_POST['written'] : [];

    $updated = 0;
    foreach ($students as $stu) {
      $stId = $stu['st_id'];
      $mcqVal = isset($mcqByStudent[$stId]) ? $mcqByStudent[$stId] : '';
      $writtenVal = isset($writtenByStudent[$stId]) ? $writtenByStudent[$stId] : '';

      $mcq = ($mcqVal === '' || $mcqVal === null) ? 0.0 : (float)$mcqVal;
      $written = ($writtenVal === '' || $writtenVal === null) ? 0.0 : (float)$writtenVal;
      $total = $mcq + $written;

      $grading = null;
      if ($total > 0) {
        if ($total < 50) $grading = 'fail';
        else if ($total < 65) $grading = 'pass';
        else $grading = 'credit';
      }

      $up = "
        INSERT INTO marks_entries
          (exam_id, subject_id, st_id, mcq_obtained, written_obtained, total_obtained, grading, entered_by)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          mcq_obtained=VALUES(mcq_obtained),
          written_obtained=VALUES(written_obtained),
          total_obtained=VALUES(total_obtained),
          grading=VALUES(grading),
          entered_by=VALUES(entered_by),
          updated_at=NOW()
      ";
      $stmtUp = mysqli_prepare($link, $up);
      mysqli_stmt_bind_param($stmtUp, "sssidss", $examId, $subjectId, $stId, $mcq, $written, $total, $grading, $enteredBy);
      mysqli_stmt_execute($stmtUp);
      mysqli_stmt_close($stmtUp);
      $updated++;
    }

    $success_msg = "✓ Recheck/edit saved for {$updated} students.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Results - Teacher Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .table-wrap { overflow-x:auto; }
    .num-input { width: 110px; }
    .row-tight { margin-top: 10px; }
  </style>
</head>
<body>
<div class="dashboard-container">
  <?php include('includes/sidebar.php'); ?>
  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-clipboard-list"></i> Results (History & Recheck)</h1>
        <p style="color: var(--muted); margin: 0;">Select exam + subject to view and edit marks</p>
      </div>
      <div class="user-menu">
        <span>👨‍🏫 <?php echo htmlspecialchars($_SESSION['name']); ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
      <?php endif; ?>
      <?php if ($error_msg): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header"><i class="fas fa-filter"></i> Select Exam</div>
        <div class="card-body">
          <form method="get" class="form-row">
            <label>Exam</label>
            <select name="exam_id" onchange="this.form.submit()">
              <option value="">-- Select Exam --</option>
              <?php foreach ($exams as $e): ?>
                <option value="<?php echo htmlspecialchars($e['exam_id']); ?>" <?php echo ($examId === $e['exam_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($e['exam_name']); ?> (<?php echo htmlspecialchars($e['exam_date']); ?>)
                </option>
              <?php endforeach; ?>
            </select>

            <?php if (!empty($examSubjects)): ?>
              <label>Subject</label>
              <select name="subject_id" onchange="this.form.submit()">
                <option value="">-- Select Subject --</option>
                <?php foreach ($examSubjects as $s): ?>
                  <option value="<?php echo htmlspecialchars($s['subject_id']); ?>" <?php echo ($subjectId === $s['subject_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s['subject_code']); ?> - <?php echo htmlspecialchars($s['subject_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($examId); ?>">
            <?php endif; ?>
            <noscript><button class="btn btn-primary" type="submit">Load</button></noscript>
          </form>
        </div>
      </div>

      <?php if ($examId !== '' && $subjectId !== ''): ?>
        <div class="card row-tight">
          <div class="card-header">
            <i class="fas fa-pen"></i> Marks for
            <?php echo htmlspecialchars($subject['subject_code'] ?? $subjectId); ?>
          </div>
          <div class="card-body">
            <?php if (empty($students)): ?>
              <div class="empty-state">
                <strong>No students found.</strong>
                <div style="margin-top:6px;">Ensure exam batch has students in `student_batches`.</div>
              </div>
            <?php else: ?>
              <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($examId); ?>">
                <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($subjectId); ?>">

                <div class="table-wrap">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Reg No</th>
                        <th>Student</th>
                        <th>MCQ</th>
                        <th>Written</th>
                        <th>Total</th>
                        <th>Grading</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($students as $stu):
                        $stId = $stu['st_id'];
                        $mcq = $marksByStudent[$stId]['mcq'] ?? 0;
                        $written = $marksByStudent[$stId]['written'] ?? 0;
                        $total = $marksByStudent[$stId]['total'] ?? ($mcq + $written);
                        $grading = $marksByStudent[$stId]['grading'] ?? '';
                      ?>
                        <tr>
                          <td><strong><?php echo htmlspecialchars($stId); ?></strong></td>
                          <td><?php echo htmlspecialchars(($stu['first_name'] ?? '').' '.($stu['last_name'] ?? '')); ?></td>
                          <td>
                            <input class="form-control num-input" type="number" step="0.01"
                              name="mcq[<?php echo htmlspecialchars($stId); ?>]"
                              value="<?php echo htmlspecialchars((string)$mcq); ?>">
                          </td>
                          <td>
                            <input class="form-control num-input" type="number" step="0.01"
                              name="written[<?php echo htmlspecialchars($stId); ?>]"
                              value="<?php echo htmlspecialchars((string)$written); ?>">
                          </td>
                          <td>
                            <?php echo htmlspecialchars(number_format((float)$total, 2, '.', '')); ?>
                          </td>
                          <td><?php echo htmlspecialchars((string)$grading); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px; flex-wrap:wrap;">
                  <a class="btn btn-secondary" href="exams.php"><i class="fas fa-arrow-left"></i> Back</a>
                  <button type="submit" name="save_recheck" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Recheck/Edit
                  </button>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
