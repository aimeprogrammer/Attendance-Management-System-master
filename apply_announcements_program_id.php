<?php
set_time_limit(0);
require __DIR__ . '/connect.php';

$table = 'announcements';
$col = 'program_id';

$checkSql = "SELECT COUNT(*) AS cnt
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?";

$stmt = mysqli_prepare($link, $checkSql);
mysqli_stmt_bind_param($stmt, "ss", $table, $col);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!empty($row['cnt'])) {
  echo "SKIP {$table}.{$col} already exists\n";
  exit(0);
}

$alterSql = "ALTER TABLE `{$table}`
             ADD COLUMN `program_id` varchar(30) DEFAULT NULL,
             ADD KEY `idx_announcements_program_id` (`program_id`)";

if (!mysqli_query($link, $alterSql)) {
  echo "FAIL adding {$table}.{$col}: " . mysqli_error($link) . "\n";
  exit(1);
}

echo "OK added {$table}.{$col}\n";
