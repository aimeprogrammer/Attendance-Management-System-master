<?php
require __DIR__ . '/connect.php';

$res = mysqli_query($link, "SELECT 1 FROM student_payments LIMIT 1");
if ($res === false) {
  echo "student_payments MISSING\n";
  echo mysqli_error($link) . "\n";
  exit(0);
}
echo "student_payments EXISTS\n";
