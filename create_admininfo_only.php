<?php
require __DIR__ . '/connect.php';

$sql = file_get_contents(__DIR__ . '/database/attsystem.sql');
if ($sql === false) { echo "Failed to read SQL file\n"; exit(1); }

$table = 'admininfo';
$needle = "CREATE TABLE `{$table}`";
$start = strpos($sql, $needle);
if ($start === false) { echo "Missing {$needle}\n"; exit(1); }

$enginePos = strpos($sql, "ENGINE=", $start);
if ($enginePos === false) { echo "ENGINE= not found for admininfo\n"; exit(1); }
$semiPos = strpos($sql, ";", $enginePos);
if ($semiPos === false) { echo "';' not found after ENGINE= for admininfo\n"; exit(1); }

$createSql = trim(substr($sql, $start, ($semiPos - $start) + 1));

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");
mysqli_query($link, "DROP TABLE IF EXISTS `{$table}`");

echo "Creating {$table}...\n";
if (!mysqli_query($link, $createSql)) {
  echo "FAIL: " . mysqli_error($link) . "\n";
  echo "SQL snippet: " . substr($createSql, 0, 300) . "\n";
  exit(1);
}
echo "OK created {$table}\n";
