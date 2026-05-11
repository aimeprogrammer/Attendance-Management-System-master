<?php
set_time_limit(0);
require __DIR__ . '/connect.php';

function tableExists(mysqli $link, string $table): bool {
  $safeTable = mysqli_real_escape_string($link, $table);
  $sql = "SHOW TABLES LIKE '{$safeTable}'";
  $res = mysqli_query($link, $sql);
  if ($res === false) return false;
  $row = mysqli_fetch_row($res);
  return $row !== null;
}

function extractCreate(string $sql, string $table): ?string {
  $needles = [
    "CREATE TABLE `{$table}`",
    "CREATE TABLE IF NOT EXISTS `{$table}`"
  ];

  $start = false;
  foreach ($needles as $needle) {
    $pos = strpos($sql, $needle);
    if ($pos !== false) {
      $start = $pos;
      break;
    }
  }
  if ($start === false) return null;

  $enginePos = strpos($sql, "ENGINE=", $start);
  if ($enginePos === false) return null;

  $semiPos = strpos($sql, ";", $enginePos);
  if ($semiPos === false) return null;

  return trim(substr($sql, $start, ($semiPos - $start) + 1));
}

$targets = [
    'student_enrollments',
    'payment_schedules',
    'student_fee_balances',
    'payments',
    'student_payments',
    'activity_logs',
    'course_materials',
    'course_material_subjects'
];

$sqlMainPath = __DIR__ . '/database/attsystem.sql';
$sqlExtPath = __DIR__ . '/database/schema_extensions.sql';

$sqlMain = file_get_contents($sqlMainPath);
if ($sqlMain === false) {
  echo "Failed to read: {$sqlMainPath}\n";
  exit(1);
}
$sqlExt = file_get_contents($sqlExtPath);
if ($sqlExt === false) {
  echo "Failed to read: {$sqlExtPath}\n";
  exit(1);
}

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

$executed = 0;
foreach ($targets as $t) {
  if (tableExists($link, $t)) {
    echo "SKIP {$t} (already exists)\n";
    continue;
  }

  $createSql = extractCreate($sqlMain, $t);
  if ($createSql === null) {
    $createSql = extractCreate($sqlExt, $t);
  }
  if ($createSql === null) {
    echo "MISSING CREATE definition for {$t} in SQL files\n";
    continue;
  }

  if (!mysqli_query($link, "DROP TABLE IF EXISTS `{$t}`")) {
    echo "DROP failed for {$t}: " . mysqli_error($link) . "\n";
    exit(1);
  }

  $ok = mysqli_query($link, $createSql);
  if ($ok === false) {
    echo "FAIL creating {$t}: " . mysqli_error($link) . "\n";
    continue;
  }
  $executed++;
  echo "OK created {$t}\n";
}

echo "DONE. Created tables: {$executed}\n";
