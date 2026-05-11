<?php
declare(strict_types=1);

require_once __DIR__ . '/../connect.php';

$stId = 'STU001';

$queries = [
  'student_enrollments' => "SELECT COUNT(*) AS c FROM student_enrollments WHERE st_id=? AND status='active'",
  'batch_subjects' => "SELECT COUNT(*) AS c FROM batch_subjects",
  'student_fee_balances' => "SELECT COUNT(*) AS c FROM student_fee_balances WHERE st_id=?",
  'attendance' => "SELECT COUNT(*) AS c FROM attendance WHERE stat_id=?",
  'announcements' => "SELECT COUNT(*) AS c FROM announcements WHERE title='Seed Announcement' AND is_active=1",
  'notifications' => "SELECT COUNT(*) AS c FROM notifications WHERE user_id=?",
];

$out = [];

foreach ($queries as $name => $sql) {
  $stmt = null;
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

echo implode(PHP_EOL, $out) . PHP_EOL;
?>
