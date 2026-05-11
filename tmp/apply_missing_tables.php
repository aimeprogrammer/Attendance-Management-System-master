<?php
set_time_limit(0);
require __DIR__ . '/../connect.php';

$sqlFile1 = __DIR__ . '/../database/attsystem.sql';
$sqlFile2 = __DIR__ . '/../database/schema_extensions.sql';

$files = [];
if (file_exists($sqlFile1)) $files[] = $sqlFile1;
if (file_exists($sqlFile2)) $files[] = $sqlFile2;

if (empty($files)) {
  echo "No SQL files found.\n";
  exit(1);
}

function listExistingTables(mysqli $link): array {
  $tables = [];
  $res = mysqli_query($link, "SHOW TABLES");
  while ($row = mysqli_fetch_row($res)) {
    $tables[strtolower($row[0])] = true;
  }
  return $tables;
}

function splitStatements(string $sql): array {
  // Split on semicolon followed by optional whitespace/newline.
  // This is a pragmatic splitter for this project SQL style.
  return preg_split('/;\s*(\r\n|\n|\r)/', $sql);
}

function extractCreateTableStatements(string $sql): array {
  // Returns array of ['table' => <name>, 'sql' => <create table sql>]
  $out = [];

  $pattern = '/CREATE TABLE\s+`([^`]+)`\s*\((.*?)\)\s*ENGINE=.*?;\s*/is';
  if (preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $m) {
      $table = $m[1];
      $createSql = 'CREATE TABLE `' . $table . '`(' . $m[2] . ') ' . $m[0];
      // The whole match already includes the full "CREATE TABLE ... ;" including engine.
      // So rebuild carefully by taking the exact matched block:
      $createSql = trim($m[0]);
      $out[] = ['table' => $table, 'sql' => $createSql];
    }
  }

  return $out;
}

$existing = listExistingTables($link);

$toApply = [];

foreach ($files as $f) {
  $sql = file_get_contents($f);
  if ($sql === false) {
    echo "Failed to read SQL file: {$f}\n";
    exit(1);
  }

  // Collect CREATE TABLE blocks (only)
  $createBlocks = extractCreateTableStatements($sql);
  foreach ($createBlocks as $b) {
    $tbl = strtolower($b['table']);
    if (!isset($existing[$tbl])) {
      $toApply[$tbl] = $b['sql']; // last one wins, but names should be unique
    }
  }
}

if (empty($toApply)) {
  echo "No missing CREATE TABLE statements found.\n";
  exit(0);
}

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

$applied = 0;
foreach ($toApply as $tbl => $createSql) {
  echo "Creating missing table: {$tbl}\n";
  if (!mysqli_query($link, $createSql)) {
    echo "FAIL creating {$tbl}: " . mysqli_error($link) . "\n";
    // show a small snippet to aid debugging
    echo "SQL snippet: " . substr(trim($createSql), 0, 200) . "...\n";
    exit(1);
  }
  $applied++;
}

echo "DONE. Applied CREATE TABLE for {$applied} missing tables.\n";
