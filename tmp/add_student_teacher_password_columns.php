<?php
require __DIR__ . '/../connect.php';

$targets = [
  'students' => 'ALTER TABLE `students` ADD COLUMN `password` varchar(255) NOT NULL DEFAULT ""',
  'teachers' => 'ALTER TABLE `teachers` ADD COLUMN `password` varchar(255) NOT NULL DEFAULT ""',
];

foreach ($targets as $table => $sql) {
  $existsRes = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE 'password'");
  if ($existsRes === false) {
    echo "SHOW COLUMNS failed for $table: " . mysqli_error($link) . PHP_EOL;
    exit(1);
  }
  if (mysqli_num_rows($existsRes) > 0) {
    echo "SKIP $table.password already exists" . PHP_EOL;
    continue;
  }

  if (!mysqli_query($link, $sql)) {
    echo "FAIL adding $table.password: " . mysqli_error($link) . PHP_EOL;
    exit(1);
  }
  echo "OK added $table.password" . PHP_EOL;
}

mysqli_close($link);
