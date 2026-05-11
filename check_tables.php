<?php
require __DIR__ . '/connect.php';

$res = mysqli_query($link, 'SHOW TABLES');
if (!$res) {
  echo 'SHOW TABLES failed: ' . mysqli_error($link) . PHP_EOL;
  exit(1);
}

while ($row = mysqli_fetch_row($res)) {
  echo $row[0] . PHP_EOL;
}
