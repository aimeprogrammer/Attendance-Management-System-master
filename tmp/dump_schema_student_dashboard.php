<?php
declare(strict_types=1);

require_once __DIR__ . '/../connect.php';

$tables = [
  'student_enrollments',
  'batch_subjects',
  'student_fee_balances',
  'attendance',
  'exams',
  'exam_categories',
  'marks_entries',
  'results_publish',
];

$lines = [];
foreach ($tables as $t) {
  $lines[] = "=== TABLE: {$t} ===";
  $res = mysqli_query($link, "SHOW TABLES LIKE '".mysqli_real_escape_string($link, $t)."'");
  if (!$res || mysqli_num_rows($res) === 0) {
    $lines[] = "Status: MISSING";
    continue;
  }
  $cols = mysqli_query($link, "SHOW COLUMNS FROM {$t}");
  if ($cols) {
    while ($row = mysqli_fetch_assoc($cols)) {
      $lines[] = "- {$row['Field']} ({$row['Type']}) null=" . ($row['Null'] ?? '');
    }
  }
}
$outPath = __DIR__ . '/_schema_dump_student_dashboard.txt';
file_put_contents($outPath, implode(PHP_EOL, $lines));
echo "WROTE: {$outPath}" . PHP_EOL;
?>
