<?php
require __DIR__ . '/connect.php';

$res = mysqli_query($link, 'SHOW CREATE TABLE programs');
if (!$res) {
  echo 'SHOW CREATE TABLE programs failed: ' . mysqli_error($link) . PHP_EOL;
  exit(1);
}
$row = mysqli_fetch_assoc($res);
echo $row['Create Table'] . PHP_EOL;
