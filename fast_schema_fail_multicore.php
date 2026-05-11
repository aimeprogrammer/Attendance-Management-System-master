<?php
require __DIR__ . '/connect.php';

$sqlFile = __DIR__ . '/database/attsystem.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) { echo "Failed to read SQL file\n"; exit(1); }

$dbName = 'attsystem';

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");
mysqli_query($link, "DROP DATABASE IF EXISTS `{$dbName}`");
mysqli_query($link, "CREATE DATABASE `{$dbName}`");
mysqli_query($link, "USE `{$dbName}`");
mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

echo "DB reset done. Starting schema import...\n";

$maxLen = 50000;
$len = strlen($sql);
$pos = 0;
$chunk = 0;

while ($pos < $len) {
  $chunk++;
  $piece = substr($sql, $pos, $maxLen);
  $pos += $maxLen;

  // multi_query buffers multiple statements; must clear results
  if (!mysqli_multi_query($link, $piece)) {
    echo "FAIL at chunk #{$chunk}\n";
    echo "Error: " . mysqli_error($link) . "\n";
    exit(1);
  }

  do {
    $result = mysqli_store_result($link);
    if ($result !== false) {
      mysqli_free_result($result);
    }
  } while (mysqli_more_results($link) && mysqli_next_result($link));

  if ($chunk % 20 === 0) {
    echo "Executed chunks: {$chunk}...\n";
  }
}

echo "DONE multi_query import.\n";
