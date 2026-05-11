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

if ($examId === '' || $subjectId === '') {
  header('location: exams.php');
  exit;
}

// Handle mark entry save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error_msg = 'Security validation failed (CSRF).';
  } else {
    $enteredBy = $_SESSION['name'] ?? 'teacher';

    // Expect arrays keyed by student id
    $mcqByStudent = isset($_POST['mcq']) && is_array($_POST['mcq']) ? $_POST['mcq'] : [];
    $writtenByStudent = isset($_POST['written']) && is_array($_POST['written']) ? $_POST['written'] : [];

    $stmtCheck = mysqli_prepare($link, "SELECT st_id FROM student_batches WHERE batch_id = (SELECT batch_id FROM exams WHERE exam_id=?)");
    // Optional: we won't rely on this for filtering; we'll just use students table joined with student_batches for the exam's batch.

    // Get exam batch to restrict which students we show/accept
    $examBatch = null;
    $stmt = mysqli_prepare($link, "SELECT batch_id FROM exams WHERE exam_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $examId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    if ($row) $examBatch = $row['batch_id'];

    if (!$examBatch) {
      $error_msg = 'Could not resolve exam batch.';
    } else {
      // Get students list for this exam batch (teacher batch filtering not available here; consistent with exams.php fallback)
      $students = [];
      $qStudents = "
        SELECT s.st_id
        FROM students s
        JOIN student_batches sb ON sb.student_id = s.id
        WHERE sb.batch_id=?
        ORDER BY s.id ASC
      ";
      $stmtStu = mysqli_prepare($link, $qStudents);
      mysqli_stmt_bind_param($stmtStu, "s", $examBatch);
      mysqli_stmt_execute($stmtStu);
      $resStu = mysqli_stmt_get_result($stmtStu);
      while ($r = mysqli_fetch_assoc($resStu)) {
        $students[] = $r['st_id'];
      }
      mysqli_stmt_close($stmtStu);

      // Upsert each student's marks
      $successCount = 0;
      foreach ($students as $stId) {
        $mcqVal = isset($mcqByStudent[$stId]) ? $mcqByStudent[$stId] : '';
        $writtenVal = isset($writtenByStudent[$stId]) ? $writtenByStudent[$stId] : '';

        // Empty => treat as 0
        $mcq = ($mcqVal === '' || $mcqVal === null) ? 0.0 : (float)$mcqVal;
        $written = ($writtenVal === '' || $writtenVal === null) ? 0.0 : (float)$writtenVal;
        $total = $mcq + $written;

        // Simple grading rule for now; production-ready grading should come from settings/table.
        $grading = null;
        if ($total <= 0) {
          $grading = null;
        } else {
          // 0-49 => fail, 50-64 => pass, 65+ => credit (placeholder)
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
        if (!$stmtUp) continue;

        mysqli_stmt_bind_param(
          $stmtUp,
          "sssidss",
          $examId,
          $subjectId,
          $stId,
          $mcq,
          $written,
          $total,
          $grading,
          $enteredBy
        );
        $ok = mysqli_stmt_execute($stmtUp);
        mysqli_stmt_close($stmtUp);
        if ($ok) $successCount++;
      }

      $success_msg = "✓ Marks saved successfully for {$successCount} students.";
    }
  }
}

// Load exam + subject details
$stmtExam = mysqli_prepare($link, "SELECT exam_name, exam_date, mcq_marks, written_marks FROM exams WHERE exam_id=? LIMIT 1");
mysqli_stmt_bind_param($stmtExam, "s", $examId);
mysqli_stmt_execute($stmtExam);
$resExam = mysqli_stmt_get_result($stmtExam);
$exam = mysqli_fetch_assoc($resExam);
mysqli_stmt_close($stmtExam);

$stmtSub = mysqli_prepare($link, "SELECT s.subject_name, s.subject_code FROM exam_subjects es JOIN subjects s ON s.subject_id=es.subject_id WHERE es.exam_id=? AND es.subject_id=? LIMIT 1");
mysqli_stmt_bind_param($stmtSub, "ss", $examId, $subjectId);
mysqli_stmt_execute($stmtSub);
$resSub = mysqli_stmt_get_result($stmtSub);
$subject = mysqli_fetch_assoc($resSub);
mysqli_stmt_close($stmtSub);

// Resolve exam batch to list students
$examBatch = null;
$stmtBatch = mysqli_prepare($link, "SELECT batch_id FROM exams WHERE exam_id=? LIMIT 1");
mysqli_stmt_bind_param($stmtBatch, "s", $examId);
mysqli_stmt_execute($stmtBatch);
$resBatch = mysqli_stmt_get_result($stmtBatch);
$rBatch = mysqli_fetch_assoc($resBatch);
mysqli_stmt_close($stmtBatch);
if ($rBatch) $examBatch = $rBatch['batch_id'];

$students = [];
$marksByStudent = []; // st_id => [mcq, written, total]
if ($examBatch) {
  $qStudents = "
    SELECT s.id, s.st_id, s.first_name, s.last_name
    FROM students s
    JOIN student_batches sb ON sb.student_id = s.id
    WHERE sb.batch_id=?
    ORDER BY s.first_name, s.last_name
  ";
  $stmtStu = mysqli_prepare($link, $qStudents);
  mysqli_stmt_bind_param($stmtStu, "s", $examBatch);
  mysqli_stmt_execute($stmtStu);
  $resStu = mysqli_stmt_get_result($stmtStu);
  while ($sr = mysqli_fetch_assoc($resStu)) {
    $students[] = $sr;
  }
  mysqli_stmt_close($stmtStu);

  // Existing marks
  if (!empty($students)) {
    $in = implode(',', array_fill(0, count($students), '?'));
    $types = str_repeat('s', count($students));
    $stIds = array_map(fn($x) => $x['st_id'], $students);

    $qMarks = "
      SELECT st_id, mcq_obtained, written_obtained, total_obtained
      FROM marks_entries
      WHERE exam_id=? AND subject_id=? AND st_id IN ($in)
    ";
    $stmtMarks = mysqli_prepare($link, $qMarks);
    $bindParams = array_merge([$examId, $subjectId], $stIds);
    // build dynamic bind
    $allTypes = 'ss' . $types;
    mysqli_stmt_bind_param($stmtMarks, $allTypes, ...$bindParams);
    mysqli_stmt_execute($stmtMarks);
    $resMarks = mysqli_stmt_get_result($stmtMarks);
    while ($mr = mysqli_fetch_assoc($resMarks)) {
      $marksByStudent[$mr['st_id']] = [
        'mcq' => (float)$mr['mcq_obtained'],
        'written' => (float)$mr['written_obtained'],
        'total' => (float)$mr['total_obtained'],
      ];
    }
    mysqli_stmt_close($stmtMarks);
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enter Results - Teacher Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .grid-two { display:grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .table-wrap { overflow-x:auto; }
    .num-input { width: 110px; }
  </style>
</head>
<body>
<div class="dashboard-container">
  <?php include('includes/sidebar.php'); ?>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-pen-nib"></i> Enter Results</h1>
        <p style="color: var(--muted); margin: 0;">Exam: <?php echo htmlspecialchars($exam['exam_name'] ?? $examId); ?> • Subject: <?php echo htmlspecialchars($subject['subject_code'] ?? $subjectId); ?></p>
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
        <div class="card-header"><i class="fas fa-info-circle"></i> Marks Entry</div>
        <div class="card-body">
          <div class="grid-two" style="margin-bottom: 14px;">
            <div>
              <strong>Max Marks</strong>
              <div style="margin-top:6px;">
                <span class="badge-soft">MCQ: <?php echo htmlspecialchars($exam['mcq_marks'] ?? 0); ?></span>
                <span class="badge-soft">Written: <?php echo htmlspecialchars($exam['written_marks'] ?? 0); ?></span>
              </div>
            </div>
            <div>
              <strong>Auto Total</strong>
              <div style="margin-top:6px; color: var(--muted); font-size: 13px;">Total = MCQ + Written. Grading uses a placeholder threshold until you add a grading-settings table.</div>
            </div>
          </div>

          <?php if (empty($students)): ?>
            <div class="empty-state">
              <strong>No students found for this exam batch.</strong>
              <div style="margin-top:6px;">If you recently added a batch/exam, ensure students are linked in `student_batches`.</div>
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
                      <th>Total (auto)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($students as $stu):
                      $stId = $stu['st_id'];
                      $mcq = isset($marksByStudent[$stId]) ? $marksByStudent[$stId]['mcq'] : 0;
                      $written = isset($marksByStudent[$stId]) ? $marksByStudent[$stId]['written'] : 0;
                      $total = isset($marksByStudent[$stId]) ? $marksByStudent[$stId]['total'] : 0;
                    ?>
                      <tr>
                        <td><strong><?php echo htmlspecialchars($stId); ?></strong></td>
                        <td><?php echo htmlspecialchars(($stu['first_name'] ?? '').' '.($stu['last_name'] ?? '')); ?></td>
                        <td>
                          <input class="form-control num-input" type="number" step="0.01"
                            name="mcq[<?php echo htmlspecialchars($stId); ?>]"
                            value="<?php echo htmlspecialchars((string)$mcq); ?>"
                            oninput="recalcRow('<?php echo htmlspecialchars($stId); ?>')"
                          >
                        </td>
                        <td>
                          <input class="form-control num-input" type="number" step="0.01"
                            name="written[<?php echo htmlspecialchars($stId); ?>]"
                            value="<?php echo htmlspecialchars((string)$written); ?>"
                            oninput="recalcRow('<?php echo htmlspecialchars($stId); ?>')"
                          >
                        </td>
                        <td>
                          <span id="total_<?php echo htmlspecialchars($stId); ?>"><?php echo htmlspecialchars((string)$total); ?></span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div style="display:flex; justify-content:flex-end; gap: 10px; margin-top: 14px; flex-wrap:wrap;">
                <a class="btn btn-secondary" href="exams.php?exam_id=<?php echo urlencode($examId); ?>">
                  <i class="fas fa-arrow-left"></i> Back
                </a>
                <button type="submit" name="save_marks" class="btn btn-primary">
                  <i class="fas fa-save"></i> Save/Update Marks
                </button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function recalcRow(stId) {
    const mcq = document.querySelector('input[name="mcq[' + stId + ']"]');
    const written = document.querySelector('input[name="written[' + stId + ']"]');
    const totalEl = document.getElementById('total_' + stId);
    const mcqVal = mcq ? parseFloat(mcq.value || '0') : 0;
    const writtenVal = written ? parseFloat(written.value || '0') : 0;
    const total = (mcqVal + writtenVal);
    if (totalEl) totalEl.textContent = total.toFixed(2);
  }

  document.addEventListener('DOMContentLoaded', function () {
    // ensure initial totals have consistent format
    <?php foreach ($students as $stu):
      $stId = $stu['st_id'];
      $mcq = isset($marksByStudent[$stId]) ? (float)$marksByStudent[$stId]['mcq'] : 0;
      $written = isset($marksByStudent[$stId]) ? (float)$marksByStudent[$stId]['written'] : 0;
      $total = $mcq + $written;
    ?>
      recalcRow('<?php echo htmlspecialchars($stId); ?>');
    <?php endforeach; ?>
  });
</script>
</body>
</html>
