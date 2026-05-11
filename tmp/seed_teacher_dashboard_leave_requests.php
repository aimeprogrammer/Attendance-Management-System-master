<?php
require_once __DIR__ . '/../connect.php';

function fetchFirstStudentId(mysqli $link): ?string {
  $res = mysqli_query($link, "SELECT st_id FROM students ORDER BY st_id ASC LIMIT 1");
  if (!$res) return null;
  $row = mysqli_fetch_assoc($res);
  return $row['st_id'] ?? null;
}

$existingPending = 0;
$res = mysqli_query($link, "SELECT COUNT(*) c FROM leave_requests WHERE status='pending'");
if ($res) {
  $row = mysqli_fetch_assoc($res);
  $existingPending = (int)($row['c'] ?? 0);
}

if ($existingPending > 0) {
  echo "Pending leave_requests already exist: {$existingPending}\n";
  exit(0);
}

$stId = fetchFirstStudentId($link);
if (!$stId) {
  die("No students found; cannot seed leave_requests.\n");
}

$leaveDate = date('Y-m-d', strtotime('+3 days'));
$reason = 'Sample pending leave request for dashboard UI testing.';

$stmt = mysqli_prepare(
  $link,
  "INSERT INTO leave_requests (st_id, leave_date, reason, status) VALUES (?, ?, ?, 'pending')"
);
if (!$stmt) {
  die("Prepare failed: " . mysqli_error($link) . "\n");
}

mysqli_stmt_bind_param($stmt, "sss", $stId, $leaveDate, $reason);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
  die("Insert failed: " . mysqli_error($link) . "\n");
}

echo "Seeded 1 pending leave_request for student {$stId} on {$leaveDate}\n";
