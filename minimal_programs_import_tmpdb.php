<?php
set_time_limit(30);
require __DIR__ . '/connect.php';

function fail(string $msg): void {
  echo "FAIL: {$msg}\n";
  exit(1);
}

function extractCreateBlock(string $sql, string $tableName): string {
  $needle = "CREATE TABLE `{$tableName}`";
  $startPos = strpos($sql, $needle);
  if ($startPos === false) fail("Could not find {$needle}");

  $enginePos = strpos($sql, "ENGINE=", $startPos);
  if ($enginePos === false) fail("ENGINE= not found for {$tableName}");
  $semiPos = strpos($sql, ";", $enginePos);
  if ($semiPos === false) fail("';' not found after ENGINE= for {$tableName}");

  return trim(substr($sql, $startPos, ($semiPos - $startPos) + 1));
}

echo "Reading SQL file...\n";
$sql = file_get_contents(__DIR__ . '/database/attsystem.sql');
if ($sql === false) fail("Could not read SQL file");

$tmpDb = 'attsystem_tmp';

echo "Creating temp DB...\n";
mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");
if (!mysqli_query($link, "DROP DATABASE IF EXISTS `{$tmpDb}`")) fail(mysqli_error($link));
if (!mysqli_query($link, "CREATE DATABASE `{$tmpDb}`")) fail(mysqli_error($link));
if (!mysqli_query($link, "USE `{$tmpDb}`")) fail(mysqli_error($link));

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

echo "Extracting CREATE TABLE programs...\n";
$programsCreate = extractCreateBlock($sql, 'programs');

echo "Extracting CREATE TABLE program_fees...\n";
$feesCreate = extractCreateBlock($sql, 'program_fees');

echo "Creating programs...\n";
if (!mysqli_query($link, $programsCreate)) {
  fail("programs create error: " . mysqli_error($link));
}
echo "OK programs\n";

echo "Creating program_fees...\n";
if (!mysqli_query($link, $feesCreate)) {
  fail("program_fees create error: " . mysqli_error($link));
}
echo "OK program_fees\n";

echo "DONE OK in temp DB.\n";
