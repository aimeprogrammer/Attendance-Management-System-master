<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../connect.php';

function tableExists(mysqli $link, string $table): bool {
  $t = mysqli_real_escape_string($link, $table);
  $res = mysqli_query($link, "SHOW TABLES LIKE '$t'");
  if (!$res) return false;
  return mysqli_num_rows($res) > 0;
}

function ensureProgram(mysqli $link, string $programId, string $programName): void {
  if (!tableExists($link, 'programs')) return;
  $stmt = mysqli_prepare($link, "SELECT program_id FROM programs WHERE program_id=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "s", $programId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if ($res && mysqli_num_rows($res) > 0) { mysqli_stmt_close($stmt); return; }
  mysqli_stmt_close($stmt);

  $stmt2 = mysqli_prepare($link, "INSERT INTO programs (program_id, program_name) VALUES (?, ?)");
  mysqli_stmt_bind_param($stmt2, "ss", $programId, $programName);
  mysqli_stmt_execute($stmt2);
  mysqli_stmt_close($stmt2);
}

function ensureBatch(mysqli $link, string $batchId, string $programId, string $batchName, int $startYear, int $endYear): void {
  if (!tableExists($link, 'batches')) return;
  $stmt = mysqli_prepare($link, "SELECT batch_id FROM batches WHERE batch_id=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "s", $batchId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if ($res && mysqli_num_rows($res) > 0) { mysqli_stmt_close($stmt); return; }
  mysqli_stmt_close($stmt);

  $stmt2 = mysqli_prepare($link, "INSERT INTO batches (batch_id, program_id, batch_name, start_year, end_year) VALUES (?, ?, ?, ?, ?)");
  mysqli_stmt_bind_param($stmt2, "sssii", $batchId, $programId, $batchName, $startYear, $endYear);
  mysqli_stmt_execute($stmt2);
  mysqli_stmt_close($stmt2);
}

function ensureSubject(mysqli $link, string $subjectId, string $programId, string $subjectCode, string $subjectName): void {
  if (!tableExists($link, 'subjects')) return;

  // subject_code is unique (uq_subject_code), so check by code first.
  $stmt = mysqli_prepare($link, "SELECT subject_id FROM subjects WHERE subject_code=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "s", $subjectCode);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if ($res && mysqli_num_rows($res) > 0) { mysqli_stmt_close($stmt); return; }
  mysqli_stmt_close($stmt);

  // Insert using the provided subjectId (can be any non-empty value if code is missing).
  $stmt2 = mysqli_prepare($link, "INSERT INTO subjects (subject_id, program_id, subject_code, subject_name) VALUES (?, ?, ?, ?)");
  mysqli_stmt_bind_param($stmt2, "ssss", $subjectId, $programId, $subjectCode, $subjectName);
  mysqli_stmt_execute($stmt2);
  mysqli_stmt_close($stmt2);
}

function ensureEnrollmentAndFees(mysqli $link, string $stId, string $programId, string $batchId): void {
  if (!tableExists($link, 'student_enrollments') || !tableExists($link, 'student_fee_balances')) return;

  // Enrollment active
  $stmt = mysqli_prepare($link, "SELECT 1 FROM student_enrollments WHERE st_id=? AND program_id=? AND batch_id=? AND status='active' LIMIT 1");
  mysqli_stmt_bind_param($stmt, "sss", $stId, $programId, $batchId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $existsEnroll = $res && mysqli_num_rows($res) > 0;
  mysqli_stmt_close($stmt);

  if (!$existsEnroll) {
    $stmt2 = mysqli_prepare(
      $link,
      "INSERT INTO student_enrollments (st_id, program_id, batch_id, admission_date, semester, status)
       VALUES (?, ?, ?, CURDATE(), 1, 'active')"
    );
    mysqli_stmt_bind_param($stmt2, "sss", $stId, $programId, $batchId);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
  }

  // Fee balance
  $stmt3 = mysqli_prepare($link, "SELECT 1 FROM student_fee_balances WHERE st_id=? AND program_id=? AND batch_id=? LIMIT 1");
  mysqli_stmt_bind_param($stmt3, "sss", $stId, $programId, $batchId);
  mysqli_stmt_execute($stmt3);
  $res3 = mysqli_stmt_get_result($stmt3);
  $existsFees = $res3 && mysqli_num_rows($res3) > 0;
  mysqli_stmt_close($stmt3);

  if (!$existsFees) {
    $totalDue = 1000.00;
    $totalPaid = 200.00;
    $stmt4 = mysqli_prepare(
      $link,
      "INSERT INTO student_fee_balances (st_id, program_id, batch_id, total_due, total_paid) VALUES (?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt4, "sssdd", $stId, $programId, $batchId, $totalDue, $totalPaid);
    mysqli_stmt_execute($stmt4);
    mysqli_stmt_close($stmt4);
  }
}

// ---- run ----
$stId = 'STU001';

$programId = 'SEED-PROG';
$programName = 'Seed Program';

$batchId = 'SEED-BATCH';
$batchName = 'Seed Batch';
$startYear = (int)date('Y');
$endYear = $startYear + 1;

$subjectId = 'SEED-SUB';
$subjectCode = 'SUB101';
$subjectName = 'Seed Subject';

ensureProgram($link, $programId, $programName);
ensureBatch($link, $batchId, $programId, $batchName, $startYear, $endYear);
ensureSubject($link, $subjectId, $programId, $subjectCode, $subjectName);

ensureEnrollmentAndFees($link, $stId, $programId, $batchId);

echo "seed_student_enrollments_fees_only done.\n";
?>
