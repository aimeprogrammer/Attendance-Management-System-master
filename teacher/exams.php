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

/**
 * NOTE:
 * Current app has teacher identity as $_SESSION['name'] == 'oasis'.
 * There is no existing teacher<->batch assignment logic used by exam migrations.
 * For production-readiness, we filter by teacher batches if a teacher_batches table exists.
 * If not, we fall back to showing all exams (safe fallback).
 */
function teacher_batch_ids(mysqli $link): array {
  $teacherId = $_SESSION['teacher_id'] ?? null;
  if ($teacherId === null) return [];

  $has = mysqli_query($link, "SHOW TABLES LIKE 'teacher_batches'");
  if ($has && mysqli_num_rows($has) > 0) {
    $stmt = mysqli_prepare($link, "SELECT batch_id FROM teacher_batches WHERE teacher_id=?");
    mysqli_stmt_bind_param($stmt, "i", $teacherId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ids = [];
    while ($row = mysqli_fetch_assoc($res)) $ids[] = $row['batch_id'];
    return $ids;
  }
  return [];
}

$batchIds = teacher_batch_ids($link);

$where = '';
$params = [];
$types = '';

if (!empty($batchIds)) {
  $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
  $where = "WHERE e.batch_id IN ($placeholders)";
  $types = str_repeat('s', count($batchIds));
  $params = $batchIds;
}

$exams = [];
$sql = "
  SELECT
    e.exam_id,
    e.exam_name,
    e.exam_date,
    e.mcq_marks,
    e.written_marks,
    e.program_id,
    p.program_name,
    e.batch_id,
    b.batch_name,
    e.exam_category_id,
    c.exam_category_name
  FROM exams e
  LEFT JOIN programs p ON p.program_id = e.program_id
  LEFT JOIN batches b ON b.batch_id = e.batch_id
  LEFT JOIN exam_categories c ON c.exam_category_id = e.exam_category_id
  $where
  ORDER BY e.exam_date DESC, e.exam_name ASC
";

$stmt = null;
if (!empty($where)) {
  $stmt = mysqli_prepare($link, $sql);
  mysqli_stmt_bind_param($stmt, $types, ...$params);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
} else {
  $res = mysqli_query($link, $sql);
}

if ($res) {
  while ($row = mysqli_fetch_assoc($res)) {
    $exams[] = $row;
  }
}

$examId = isset($_GET['exam_id']) ? trim($_GET['exam_id']) : '';
$activeExam = null;
$examSubjects = [];

if ($examId !== '') {
  $stmt = mysqli_prepare($link, "SELECT * FROM exams WHERE exam_id=?");
  mysqli_stmt_bind_param($stmt, "s", $examId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $activeExam = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);

  $stmt = mysqli_prepare(
    $link,
    "SELECT
      es.subject_id,
      s.subject_name,
      s.subject_code,
      es.mcq_allocation,
      es.written_allocation
    FROM exam_subjects es
    JOIN subjects s ON s.subject_id = es.subject_id
    WHERE es.exam_id=?
    ORDER BY s.subject_code ASC"
  );
  mysqli_stmt_bind_param($stmt, "s", $examId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  while ($row = mysqli_fetch_assoc($res)) $examSubjects[] = $row;
  mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exams - Teacher Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .layout-split { display: grid; grid-template-columns: 1fr 1.2fr; gap: 18px; align-items: start; }
    @media (max-width: 900px) { .layout-split { grid-template-columns: 1fr; } }
    .badge-soft { background:#f4f6f8; color:#111; border:1px solid #e5e7eb; padding:4px 8px; border-radius:999px; font-size:12px; }
    .table-wrap { overflow-x:auto; }
    .empty-state { padding: 14px; background: var(--bg-light); border:2px dashed var(--border); border-radius: 12px; color: var(--muted); }
  </style>
</head>
<body>
<div class="dashboard-container">
  <?php include('includes/sidebar.php'); ?>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-book-open"></i> Exam Management</h1>
        <p style="color: var(--muted); margin: 0;">Select an exam to view subjects and allocate marks</p>
      </div>
      <div class="user-menu">
        <span>👨‍🏫 <?php echo htmlspecialchars($_SESSION['name']); ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <div class="layout-split">
        <div>
          <div class="card">
            <div class="card-header"><i class="fas fa-list"></i> Assigned Exams</div>
            <div class="card-body">
              <?php if (empty($exams)): ?>
                <div class="empty-state">
                  <strong>No exams found.</strong>
                  <div style="margin-top:6px;">If teacher-batch assignment is not configured, enable batch filtering in the code.</div>
                </div>
              <?php else: ?>
                <div class="table-wrap">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Exam</th>
                        <th>Date</th>
                        <th>Batch</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($exams as $e): ?>
                        <tr>
                          <td>
                            <a class="btn btn-light" style="display:inline-block" href="exams.php?exam_id=<?php echo urlencode($e['exam_id']); ?>">
                              <i class="fas fa-eye"></i> <?php echo htmlspecialchars($e['exam_name']); ?>
                            </a>
                          </td>
                          <td><?php echo htmlspecialchars($e['exam_date']); ?></td>
                          <td><span class="badge-soft"><?php echo htmlspecialchars($e['batch_name'] ?? $e['batch_id']); ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div>
          <div class="card">
            <div class="card-header"><i class="fas fa-list-check"></i> Exam Details</div>
            <div class="card-body">
              <?php if (!$activeExam): ?>
                <div class="empty-state">
                  <strong>Select an exam from the left.</strong>
                  <div style="margin-top:6px;">We will display its subject allocations here.</div>
                </div>
              <?php else: ?>
                <div style="display:grid; gap:12px; margin-bottom: 14px;">
                  <div>
                    <strong>Exam ID:</strong> <?php echo htmlspecialchars($activeExam['exam_id']); ?>
                  </div>
                  <div>
                    <strong>Exam Category:</strong> <?php echo htmlspecialchars($activeExam['exam_category_id']); ?>
                  </div>
                  <div>
                    <strong>Exam Date:</strong> <?php echo htmlspecialchars($activeExam['exam_date']); ?>
                  </div>
                  <div>
                    <strong>Max Marks:</strong>
                    <span class="badge-soft">MCQ: <?php echo htmlspecialchars($activeExam['mcq_marks']); ?></span>
                    <span class="badge-soft">Written: <?php echo htmlspecialchars($activeExam['written_marks']); ?></span>
                  </div>
                </div>

                <?php if (empty($examSubjects)): ?>
                  <div class="empty-state">
                    <strong>No subjects allocated for this exam.</strong>
                  </div>
                <?php else: ?>
                  <div class="table-wrap">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th>Subject</th>
                          <th>MCQ Allocation</th>
                          <th>Written Allocation</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($examSubjects as $s): ?>
                          <tr>
                            <td>
                              <strong><?php echo htmlspecialchars($s['subject_code']); ?></strong>
                              <div style="color:var(--muted); font-size:12px;"><?php echo htmlspecialchars($s['subject_name']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($s['mcq_allocation']); ?></td>
                            <td><?php echo htmlspecialchars($s['written_allocation']); ?></td>
                            <td>
                              <a class="btn btn-primary" href="enter_results.php?exam_id=<?php echo urlencode($examId); ?>&subject_id=<?php echo urlencode($s['subject_id']); ?>">
                                <i class="fas fa-pen"></i> Enter Marks
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
