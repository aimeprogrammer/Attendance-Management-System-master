<?php
set_time_limit(0);
require __DIR__ . '/connect.php';

$sqlFile = __DIR__ . '/database/attsystem.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) {
  echo "Failed to read {$sqlFile}\n";
  exit(1);
}

$targets = [
  'sms_gateway_settings',
  'sms_outbox'
];

function extractCreate(string $sql, string $table): ?string {
  $needle = "CREATE TABLE `{$table}`";
  $start = strpos($sql, $needle);
  if ($start === false) return null;

  $enginePos = strpos($sql, "ENGINE=", $start);
  if ($enginePos === false) return null;

  $semiPos = strpos($sql, ";", $enginePos);
  if ($semiPos === false) return null;

  return trim(substr($sql, $start, ($semiPos - $start) + 1));
}

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

$created = 0;
foreach ($targets as $t) {
  // If exists, skip
  $res = mysqli_query($link, "SHOW TABLES LIKE '" . mysqli_real_escape_string($link, $t) . "'");
  if ($res && mysqli_fetch_row($res)) {
    echo "SKIP {$t} (already exists)\n";
    continue;
  }

  $createSql = extractCreate($sql, $t);
  if ($createSql === null) {
    echo "MISSING CREATE TABLE for {$t} in atsystem.sql\n";
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

  $created++;
  echo "OK created {$t}\n";
}

echo "DONE. Created {$created} table(s)\n";
?>
