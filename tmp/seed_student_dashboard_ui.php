<?php
declare(strict_types=1);

/**
 * Seeds minimum data for student dashboard pages to show content instead of empty states.
 * Idempotent: uses INSERT ... WHERE NOT EXISTS patterns via SELECT checks.
 *
 * Defensive and strict SQL error reporting so we can fix schema mismatches.
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../connect.php';

function tableExists(mysqli $link, string $table): bool {
  $t = mysqli_real_escape_string($link, $table);
  $res = mysqli_query($link, "SHOW TABLES LIKE '$t'");
  if (!$res) return false;
  return mysqli_num_rows($res) > 0;
}

function ensureExamCategory(mysqli $link): void {
  if (!tableExists($link, 'exam_categories')) return;

  $categoryName = 'Mid Term';
  $stmt = mysqli_prepare($link, "SELECT exam_category_id FROM exam_categories WHERE exam_category_name=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "s", $categoryName);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if ($res && mysqli_num_rows($res) > 0) { mysqli_stmt_close($stmt); return; }
  mysqli_stmt_close($stmt);

  $stmt2 = mysqli_prepare($link, "INSERT INTO exam_categories (exam_category_name) VALUES (?)");
  mysqli_stmt_bind_param($stmt2, "s", $categoryName);
  mysqli_stmt_execute($stmt2);
  mysqli_stmt_close($stmt2);
}

function getOrCreateStudent(mysqli $link, string $stId): array {
  $defaults = [
    'st_name' => 'Student Seed',
    'st_dept' => 'General Studies',
    'st_batch' => '2026',
    'st_sem' => '1',
    'st_email' => 'student.seed@example.com',
  ];

  if (tableExists($link, 'students')) {
    $stmt = mysqli_prepare($link, "SELECT st_id FROM students WHERE st_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $stId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
      mysqli_stmt_close($stmt);
      return $defaults;
    }
    mysqli_stmt_close($stmt);

    $stmt2 = mysqli_prepare(
      $link,
      "INSERT INTO students (st_id, st_name, st_dept, st_batch, st_sem, st_email) VALUES (?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param(
      $stmt2,
      "ssssss",
      $stId,
      $defaults['st_name'],
      $defaults['st_dept'],
      $defaults['st_batch'],
      $defaults['st_sem'],
      $defaults['st_email']
    );
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
  }

  return $defaults;
}

function getOrCreateProgramBatchSubject(mysqli $link): array {
  $programName = 'Seed Program';
  $programIdSeed = 'SEED-PROG';

  $batchName = 'Seed Batch';
  $batchIdSeed = 'SEED-BATCH';

  $batchStart = (string)date('Y');
  $batchEnd = (string)((int)date('Y') + 1);

  $subjectCode = 'SUB101';
  $subjectIdSeed = 'SEED-SUB';
  $subjectName = 'Seed Subject';

  $programId = null;
  if (tableExists($link, 'programs')) {
    $stmt = mysqli_prepare($link, "SELECT program_id FROM programs WHERE program_name=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $programName);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
      $row = mysqli_fetch_assoc($res);
      $programId = (string)$row['program_id'];
      if ($programId === '') $programId = null;
    }
    mysqli_stmt_close($stmt);

    if ($programId === null) {
      $stmt2 = mysqli_prepare($link, "INSERT INTO programs (program_name) VALUES (?)");
      mysqli_stmt_bind_param($stmt2, "s", $programName);
      mysqli_stmt_execute($stmt2);
      mysqli_stmt_close($stmt2);

      $stmt3 = mysqli_prepare($link, "SELECT program_id FROM programs WHERE program_name=? LIMIT 1");
      mysqli_stmt_bind_param($stmt3, "s", $programName);
      mysqli_stmt_execute($stmt3);
      $res3 = mysqli_stmt_get_result($stmt3);
      $row3 = mysqli_fetch_assoc($res3);
      $programId = (string)$row3['program_id'];
      mysqli_stmt_close($stmt3);
    }
  }

  $batchId = null;
  if (tableExists($link, 'batches')) {
    $stmt = mysqli_prepare($link, "SELECT batch_id FROM batches WHERE batch_name=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $batchName);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
      $row = mysqli_fetch_assoc($res);
      $batchId = (string)$row['batch_id'];
    }
    mysqli_stmt_close($stmt);

    if ($batchId === null) {
      $stmt2 = mysqli_prepare($link, "INSERT INTO batches (program_id, batch_name, start_year, end_year) VALUES (?, ?, ?, ?)");
      mysqli_stmt_bind_param($stmt2, "ssss", (string)$programId, $batchName, $batchStart, $batchEnd);
      mysqli_stmt_execute($stmt2);
      mysqli_stmt_close($stmt2);

      $stmt3 = mysqli_prepare($link, "SELECT batch_id FROM batches WHERE batch_name=? LIMIT 1");
      mysqli_stmt_bind_param($stmt3, "s", $batchName);
      mysqli_stmt_execute($stmt3);
      $res3 = mysqli_stmt_get_result($stmt3);
      $row3 = mysqli_fetch_assoc($res3);
      $batchId = (string)$row3['batch_id'];
      mysqli_stmt_close($stmt3);
    }
  }

  $subjectId = null;
  if (tableExists($link, 'subjects')) {
    $stmt = mysqli_prepare($link, "SELECT subject_id FROM subjects WHERE subject_code=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $subjectCode);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
      $row = mysqli_fetch_assoc($res);
      $subjectId = (string)$row['subject_id'];
    }
    mysqli_stmt_close($stmt);

    if ($subjectId === null) {
      $stmt2 = mysqli_prepare($link, "INSERT INTO subjects (subject_code, subject_name) VALUES (?, ?)");
      mysqli_stmt_bind_param($stmt2, "ss", $subjectCode, $subjectName);
      mysqli_stmt_execute($stmt2);
      mysqli_stmt_close($stmt2);

      $stmt3 = mysqli_prepare($link, "SELECT subject_id FROM subjects WHERE subject_code=? LIMIT 1");
      mysqli_stmt_bind_param($stmt3, "s", $subjectCode);
      mysqli_stmt_execute($stmt3);
      $res3 = mysqli_stmt_get_result($stmt3);
      $row3 = mysqli_fetch_assoc($res3);
      $subjectId = (string)$row3['subject_id'];
      mysqli_stmt_close($stmt3);
    }
  }

  return [
    'program_id' => $programId,
    'batch_id' => $batchId,
    'subject_id' => $subjectId,
  ];
}

try {
  $stId = 'STU001';

  $programBatchSubject = getOrCreateProgramBatchSubject($link);

  getOrCreateStudent($link, $stId);

  // student_enrollments
  if (tableExists($link, 'student_enrollments')) {
    if (!empty($programBatchSubject['program_id']) && !empty($programBatchSubject['batch_id'])) {
      $stmt = mysqli_prepare(
        $link,
        "SELECT st_id FROM student_enrollments WHERE st_id=? AND program_id=? AND batch_id=? AND status='active' LIMIT 1"
      );
      mysqli_stmt_bind_param($stmt, "sss", $stId, $programBatchSubject['program_id'], $programBatchSubject['batch_id']);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      $exists = $res && mysqli_num_rows($res) > 0;
      mysqli_stmt_close($stmt);

      if (!$exists) {
        $stmt2 = mysqli_prepare(
          $link,
          "INSERT INTO student_enrollments (st_id, program_id, batch_id, admission_date, semester, status)
           VALUES (?, ?, ?, CURDATE(), 1, 'active')"
        );
        mysqli_stmt_bind_param($stmt2, "sss", $stId, $programBatchSubject['program_id'], $programBatchSubject['batch_id']);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
      }
    }
  }

  // batch_subjects
  if (tableExists($link, 'batch_subjects')) {
    if (!empty($programBatchSubject['batch_id']) && !empty($programBatchSubject['subject_id'])) {
      $stmt = mysqli_prepare($link, "SELECT 1 FROM batch_subjects WHERE batch_id=? AND subject_id=? LIMIT 1");
      mysqli_stmt_bind_param($stmt, "ss", $programBatchSubject['batch_id'], $programBatchSubject['subject_id']);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      $exists = $res && mysqli_num_rows($res) > 0;
      mysqli_stmt_close($stmt);

      if (!$exists) {
        $stmt2 = mysqli_prepare($link, "INSERT INTO batch_subjects (batch_id, subject_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt2, "ss", $programBatchSubject['batch_id'], $programBatchSubject['subject_id']);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
      }
    }
  }

  // student_fee_balances
  if (tableExists($link, 'student_fee_balances')) {
    if (!empty($programBatchSubject['program_id']) && !empty($programBatchSubject['batch_id'])) {
      $stmt = mysqli_prepare($link, "SELECT 1 FROM student_fee_balances WHERE st_id=? AND program_id=? AND batch_id=? LIMIT 1");
      mysqli_stmt_bind_param($stmt, "sss", $stId, $programBatchSubject['program_id'], $programBatchSubject['batch_id']);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      $exists = $res && mysqli_num_rows($res) > 0;
      mysqli_stmt_close($stmt);

      if (!$exists) {
        $totalDue = 1000;
        $totalPaid = 200;

        $stmt2 = mysqli_prepare(
          $link,
          "INSERT INTO student_fee_balances (st_id, program_id, batch_id, total_due, total_paid) VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt2, "sssid", $stId, $programBatchSubject['program_id'], $programBatchSubject['batch_id'], $totalDue, $totalPaid);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
      }
    }
  }

  // attendance
  if (tableExists($link, 'attendance')) {
    if (!empty($programBatchSubject['batch_id'])) {
      $course = 'Seed Subject';
      if (tableExists($link, 'subjects')) {
        $stmt = mysqli_prepare($link, "SELECT subject_code FROM subjects LIMIT 1");
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && mysqli_num_rows($res) > 0) {
          $row = mysqli_fetch_assoc($res);
          $course = (string)($row['subject_code'] ?? $course);
        }
        mysqli_stmt_close($stmt);
      }

      $already = false;
      $stmt = mysqli_prepare($link, "SELECT 1 FROM attendance WHERE stat_id=? AND course=? LIMIT 1");
      mysqli_stmt_bind_param($stmt, "ss", $stId, $course);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      if ($res && mysqli_num_rows($res) > 0) $already = true;
      mysqli_stmt_close($stmt);

      if (!$already) {
        $stmt2 = mysqli_prepare(
          $link,
          "INSERT INTO attendance (stat_id, course, st_status, stat_date, check_in_time, remarks)
           VALUES (?, ?, 'present', CURDATE(), '09:00', 'Seed data')"
        );
        mysqli_stmt_bind_param($stmt2, "ss", $stId, $course);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
      }
    }
  }

  // announcements
  if (tableExists($link, 'announcements')) {
    $title = 'Seed Announcement';
    $stmt = mysqli_prepare($link, "SELECT 1 FROM announcements WHERE title=? AND is_active=1 LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $title);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $exists = $res && mysqli_num_rows($res) > 0;
    mysqli_stmt_close($stmt);

    if (!$exists) {
      $content = 'This is seeded content so student dashboard pages are not empty.';
      $type = 'announcement';
      $priority = 'medium';
      $visibility = 'all';
      $publishedBy = 'admin';

      $stmt2 = mysqli_prepare(
        $link,
        "INSERT INTO announcements (title, content, announcement_type, priority, visibility, published_by, published_date, is_active, expiry_date)
         VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 1, NULL)"
      );
      mysqli_stmt_bind_param($stmt2, "ssssss", $title, $content, $type, $priority, $visibility, $publishedBy);
      mysqli_stmt_execute($stmt2);
      mysqli_stmt_close($stmt2);
    }
  }

  // notifications
  if (tableExists($link, 'notifications')) {
    $stmt = mysqli_prepare($link, "SELECT 1 FROM notifications WHERE user_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $stId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $exists = $res && mysqli_num_rows($res) > 0;
    mysqli_stmt_close($stmt);

    if (!$exists) {
      $msg = 'Seed notification for student dashboard UI.';
      $stmt2 = mysqli_prepare($link, "INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
      mysqli_stmt_bind_param($stmt2, "ss", $stId, $msg);
      mysqli_stmt_execute($stmt2);
      mysqli_stmt_close($stmt2);
    }
  }

  // exams + marks + results (only if tables exist)
  ensureExamCategory($link);

  if (tableExists($link, 'exams') && tableExists($link, 'marks_entries') && tableExists($link, 'exam_categories')) {
    $categoryId = null;
    $catName = 'Mid Term';

    $stmt = mysqli_prepare($link, "SELECT exam_category_id FROM exam_categories WHERE exam_category_name=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $catName);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
      $row = mysqli_fetch_assoc($res);
      $categoryId = (string)$row['exam_category_id'];
    }
    mysqli_stmt_close($stmt);

    if ($categoryId !== null && !empty($programBatchSubject['program_id']) && !empty($programBatchSubject['batch_id'])) {
      $examName = 'Seed Exam';
      $examId = null;

      $stmtX = mysqli_prepare($link, "SELECT exam_id FROM exams WHERE exam_name=? AND exam_category_id=? LIMIT 1");
      mysqli_stmt_bind_param($stmtX, "ss", $examName, $categoryId);
      mysqli_stmt_execute($stmtX);
      $resX = mysqli_stmt_get_result($stmtX);
      if ($resX && mysqli_num_rows($resX) > 0) {
        $rowX = mysqli_fetch_assoc($resX);
        $examId = (string)$rowX['exam_id'];
      }
      mysqli_stmt_close($stmtX);

      if ($examId === null) {
        $mcqMarks = 20;
        $writtenMarks = 30;
        $examDate = date('Y-m-d', strtotime('+7 days'));

        $stmt2 = mysqli_prepare(
          $link,
          "INSERT INTO exams (exam_category_id, exam_name, exam_date, mcq_marks, written_marks)
           VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt2, "sssii", $categoryId, $examName, $examDate, $mcqMarks, $writtenMarks);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        $stmt3 = mysqli_prepare($link, "SELECT exam_id FROM exams WHERE exam_name=? AND exam_category_id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt3, "ss", $examName, $categoryId);
        mysqli_stmt_execute($stmt3);
        $res3 = mysqli_stmt_get_result($stmt3);
        $row3 = mysqli_fetch_assoc($res3);
        $examId = (string)($row3['exam_id'] ?? '');
        mysqli_stmt_close($stmt3);
      }

      if ($examId !== null && $examId !== '' && tableExists($link, 'subjects')) {
        $subStmt = mysqli_query($link, "SELECT subject_id FROM subjects LIMIT 2");
        while ($sub = mysqli_fetch_assoc($subStmt)) {
          $subId = (string)$sub['subject_id'];

          $existsStmt = mysqli_prepare($link, "SELECT 1 FROM marks_entries WHERE st_id=? AND exam_id=? AND subject_id=? LIMIT 1");
          mysqli_stmt_bind_param($existsStmt, "sss", $stId, $examId, $subId);
          mysqli_stmt_execute($existsStmt);
          $resE = mysqli_stmt_get_result($existsStmt);
          $existsM = $resE && mysqli_num_rows($resE) > 0;
          mysqli_stmt_close($existsStmt);

          if (!$existsM) {
            $mcqObtained = 10;
            $writtenObtained = 20;
            $totalObtained = $mcqObtained + $writtenObtained;

            $stmt4 = mysqli_prepare(
              $link,
              "INSERT INTO marks_entries (st_id, exam_id, subject_id, mcq_obtained, written_obtained, total_obtained, grading)
               VALUES (?, ?, ?, ?, ?, ?, 'A')"
            );
            mysqli_stmt_bind_param($stmt4, "ssssii", $stId, $examId, $subId, $mcqObtained, $writtenObtained, $totalObtained);
            mysqli_stmt_execute($stmt4);
            mysqli_stmt_close($stmt4);
          }
        }
      }

      if (tableExists($link, 'results_publish') && $examId !== null && $examId !== '') {
        $stmtRp = mysqli_prepare($link, "SELECT 1 FROM results_publish WHERE exam_id=? AND published=1 LIMIT 1");
        mysqli_stmt_bind_param($stmtRp, "s", $examId);
        mysqli_stmt_execute($stmtRp);
        $resRp = mysqli_stmt_get_result($stmtRp);
        $existsRp = $resRp && mysqli_num_rows($resRp) > 0;
        mysqli_stmt_close($stmtRp);

        if (!$existsRp) {
          $stmtRp2 = mysqli_prepare($link, "INSERT INTO results_publish (exam_id, published, published_at) VALUES (?, 1, NOW())");
          mysqli_stmt_bind_param($stmtRp2, "s", $examId);
          mysqli_stmt_execute($stmtRp2);
          mysqli_stmt_close($stmtRp2);
        }
      }
    }
  }

  echo "Student dashboard seed completed.\n";
} catch (Throwable $e) {
  echo "SEED FAILED: " . $e->getMessage() . "\n";
  exit(1);
}
?>
