<?php
set_time_limit(0);
require __DIR__ . '/connect.php';

$sqlFiles = [
  __DIR__ . '/database/attsystem.sql',
  __DIR__ . '/database/schema_extensions.sql',
];

$targets = [
  'system_settings'
];

$found = false;

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

foreach ($sqlFiles as $sqlFile) {
  $sql = file_get_contents($sqlFile);
  if ($sql === false) continue;

  foreach ($targets as $t) {
    // Prefer the IF NOT EXISTS block
    $needle = "CREATE TABLE IF NOT EXISTS `{$t}`";
    $start = strpos($sql, $needle);

    // Fallback (older dumps)
    if ($start === false) {
      $needle2 = "CREATE TABLE `{$t}`";
      $start = strpos($sql, $needle2);
    }

    if ($start === false) continue;

    $enginePos = strpos($sql, "ENGINE=", $start);
    if ($enginePos === false) continue;

    $semiPos = strpos($sql, ";", $enginePos);
    if ($semiPos === false) continue;

    $createSql = trim(substr($sql, $start, ($semiPos - $start) + 1));

    // Do not DROP. We only want idempotent creation.
    $ok = mysqli_query($link, $createSql);
    if ($ok === false) {
      echo "FAIL applying {$t}: " . mysqli_error($link) . "\n";
      exit(1);
    }

    echo "OK ensured {$t} exists\n";
    $found = true;
  }
}

if (!$found) {
  echo "Could not find CREATE TABLE definition for system_settings in schema files.\n";
  exit(1);
}
?>
