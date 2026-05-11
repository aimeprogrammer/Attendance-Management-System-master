<?php
// Cron-style script to generate low attendance notifications for students (< 75%).
// Intended to be run periodically (e.g., every day or after attendance is updated).

require_once __DIR__ . '/connect.php';

$THRESHOLD = 75;

// Use mysqli SQL (this file runs with mysqli connection from connect.php)
// Insert a notification for each student that is below threshold and has not been notified today.
$today = date('Y-m-d');

// 1) Calculate attendance percentage per student using status_type weighting:
// Present=1, Late=1, Half-day=0.5, Absent=0.
$sql = "SELECT s.st_id, s.st_name,
               ROUND(
                 (SUM(CASE WHEN a.status_type IN ('present','late') THEN 1
                           WHEN a.status_type='half-day' THEN 0.5
                           WHEN a.status_type='absent' THEN 0
                           ELSE 0 END)
                 / NULLIF(COUNT(*),0)) * 100, 2
               ) AS attendance_pct,
               COUNT(*) AS total_days
        FROM students s
        JOIN attendance a ON a.stat_id = s.st_id
        GROUP BY s.st_id, s.st_name";

$res = mysqli_query($link, $sql);
if (!$res) {
  die('Query failed: ' . mysqli_error($link));
}

while ($row = mysqli_fetch_array($res)) {
  $st_id = $row['st_id'];
  $st_name = $row['st_name'];
  $pct = (float)$row['attendance_pct'];

  if ($pct < $THRESHOLD) {
    // Prevent duplicates: check if a notification containing "Low attendance" was already created today.
    $check = mysqli_prepare($link, "SELECT notif_id FROM notifications WHERE user_id = ? AND message LIKE ? AND DATE(created_at)=DATE(?) LIMIT 1");
    $like = "%Low attendance%";
    mysqli_stmt_bind_param($check, "sss", $st_id, $like, $today);
    mysqli_stmt_execute($check);
    $existing = mysqli_stmt_get_result($check);

    if ($existing && mysqli_num_rows($existing) > 0) {
      continue;
    }
    mysqli_stmt_close($check);

    $msg = sprintf(
      "Low attendance alert: Hi %s, your attendance is %.2f%% (below %d%%). Please review your attendance records.",
      $st_name,
      $pct,
      $THRESHOLD
    );

    $ins = mysqli_prepare($link, "INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
    mysqli_stmt_bind_param($ins, "ss", $st_id, $msg);
    mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);
  }
}

echo "Low attendance notifications generation completed.";

