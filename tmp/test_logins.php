<?php
require __DIR__ . '/../connect.php';

function okPasswordOne(string $sql, string $bindType, string $value, string $pass): bool {
  global $link;

  $stmt = mysqli_prepare($link, $sql);
  if (!$stmt) {
    echo "PREPARE_FAIL: " . mysqli_error($link) . PHP_EOL;
    return false;
  }

  if ($bindType === 's') {
    mysqli_stmt_bind_param($stmt, 's', $value);
  } else {
    echo "UNSUPPORTED_BIND_TYPE: {$bindType}" . PHP_EOL;
    mysqli_stmt_close($stmt);
    return false;
  }

  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $stored);

  $ok = false;
  if (mysqli_stmt_fetch($stmt)) {
    $ok = password_verify($pass, $stored) || $pass === $stored;
  }

  mysqli_stmt_close($stmt);
  return $ok;
}

function okPasswordTwo(string $sql, string $v1, string $v2, string $pass): bool {
  global $link;

  $stmt = mysqli_prepare($link, $sql);
  if (!$stmt) {
    echo "PREPARE_FAIL: " . mysqli_error($link) . PHP_EOL;
    return false;
  }

  mysqli_stmt_bind_param($stmt, 'ss', $v1, $v2);

  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $stored);

  $ok = false;
  if (mysqli_stmt_fetch($stmt)) {
    $ok = password_verify($pass, $stored) || $pass === $stored;
  }

  mysqli_stmt_close($stmt);
  return $ok;
}

$adminOk = okPasswordTwo(
  "SELECT password FROM admininfo WHERE username=? AND type=? LIMIT 1",
  "oasis",
  "admin",
  "admin123"
);

$studentOk = okPasswordOne(
  "SELECT password FROM students WHERE st_id=? LIMIT 1",
  's',
  "STU001",
  "student123"
);

$teacherOk = okPasswordOne(
  "SELECT password FROM teachers WHERE tc_id=? LIMIT 1",
  's',
  "TCH001",
  "teacher123"
);

echo "admin=" . ($adminOk ? "OK" : "FAIL") . PHP_EOL;
echo "student=" . ($studentOk ? "OK" : "FAIL") . PHP_EOL;
echo "teacher=" . ($teacherOk ? "OK" : "FAIL") . PHP_EOL;
