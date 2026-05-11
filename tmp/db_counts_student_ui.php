<?php
declare(strict_types=1);
require_once __DIR__ . '/../connect.php';

$stId = 'STU001';

$queries = [
  'student_enrollments_active' => "SELECT COUNT(*) c FROM student_enrollments WHERE st_id=? AND status='active'",
  'batch_subjects' => "SELECT COUNT(*) c FROM batch_subjects",
  'student_fee_balances' => "SELECT COUNT(*) c FROM student_fee_balances WHERE st_id=?",
  'attendance_rows' => "SELECT COUNT(*) c FROM attendance WHERE stat_id=?",
  'exams_total' => "SELECT COUNT(*) c FROM exams",
  'marks_entries' => "SELECT COUNT(*) c FROM marks_entries WHERE st_id=?",
  'results_publish' => "SELECT COUNT(*) c FROM results_publish",
  'exam_categories' => "SELECT COUNT(*) c FROM exam_categories",
];

$out = [];
foreach ($queries as $name => $sql) {
  if (strpos($sql, '?') !== false) {
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "s", $stId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    $out[] = $name . ':' . (string)($row['c'] ?? 0);
    mysqli_stmt_close($stmt);
  } else {
    $res = mysqli_query($link, $sql);
    $row = $res ? mysqli_fetch_assoc($res) : ['c' => 0];
    $out[] = $name . ':' . (string)($row['c'] ?? 0);
  }
}

$path = __DIR__ . '/_db_counts_student_ui.txt';
file_put_contents($path, implode(PHP_EOL, $out) . PHP_EOL);
echo "WROTE: {$path}\n";
?>
