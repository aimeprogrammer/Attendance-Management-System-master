<?php
declare(strict_types=1);
require_once __DIR__ . '/../connect.php';

$stId = 'STU001';

function oneRowCount(string $sql, ?string $param = null): int {
  global $link;
  if ($param !== null) {
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "s", $param);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return (int)($row['c'] ?? 0);
  }
  $res = mysqli_query($link, $sql);
  $row = $res ? mysqli_fetch_assoc($res) : ['c' => 0];
  return (int)($row['c'] ?? 0);
}

echo "student_enrollments (all statuses) for {$stId}\n";
$q1 = "SELECT status, COUNT(*) AS c FROM student_enrollments WHERE st_id=? GROUP BY status";
$stmt = mysqli_prepare($link, $q1);
mysqli_stmt_bind_param($stmt, "s", $stId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) {
  echo " - {$r['status']}: " . (int)($r['c'] ?? 0) . "\n";
}
mysqli_stmt_close($stmt);

$feeCount = oneRowCount("SELECT COUNT(*) AS c FROM student_fee_balances WHERE st_id=?", $stId);
echo "student_fee_balances for {$stId}: {$feeCount}\n";

echo "Programs with name 'Seed Program'\n";
$q3 = "SELECT program_id, program_name FROM programs WHERE program_name='Seed Program' LIMIT 5";
$res3 = mysqli_query($link, $q3);
while ($r3 = mysqli_fetch_assoc($res3)) {
  echo " - {$r3['program_id']} ({$r3['program_name']})\n";
}

echo "Batches with name 'Seed Batch'\n";
$q4 = "SELECT batch_id, batch_name, program_id FROM batches WHERE batch_name='Seed Batch' LIMIT 5";
$res4 = mysqli_query($link, $q4);
while ($r4 = mysqli_fetch_assoc($res4)) {
  echo " - {$r4['batch_id']} ({$r4['batch_name']}) program={$r4['program_id']}\n";
}
?>
