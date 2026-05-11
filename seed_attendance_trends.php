<?php
set_time_limit(0);
require __DIR__ . '/connect.php';

$daysBack = 10;      // last N days to seed
$studentsLimit = 10; // seed for first N students (keep it light)

$courses = ['algo', 'dbms', 'os', 'webdev']; // matches teacher UI dropdown values

// Get a stable list of students
$studentRes = mysqli_query($link, "SELECT st_id FROM students ORDER BY st_id ASC LIMIT {$studentsLimit}");
if ($studentRes === false) {
  echo "Failed to load students: " . mysqli_error($link) . "\n";
  exit(1);
}
$students = [];
while ($row = mysqli_fetch_assoc($studentRes)) {
  $students[] = $row['st_id'];
}

if (count($students) === 0) {
  echo "No students found in `students`. Seed aborted.\n";
  exit(0);
}

$start = new DateTime('today');
mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

$inserted = 0;

for ($d = 0; $d < $daysBack; $d++) {
  $date = clone $start;
  $date->modify("-{$d} day");
  $attDate = $date->format('Y-m-d');

  foreach ($students as $idx => $stId) {
    $course = $courses[$idx % count($courses)];

    // Mix present/absent to make a non-flat chart:
    // - alternate present/absent based on day+index
    $isPresent = (($idx + $d) % 3 !== 0); // ~2/3 present
    $stStatus = $isPresent ? 'Present' : 'Absent';

    // Teacher UI deletes duplicates first; do the same here.
    $del = mysqli_prepare($link, "DELETE FROM attendance WHERE stat_id=? AND course=? AND stat_date=?");
    mysqli_stmt_bind_param($del, "sss", $stId, $course, $attDate);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    $checkIn = $isPresent ? '09:00:00' : null;
    $statusType = $isPresent ? 'present' : 'absent';
    $remarks = $isPresent ? null : 'Seeded absent';

    // Insert (matches teacher schema usage)
    $ins = mysqli_prepare(
      $link,
      "INSERT INTO attendance (stat_id, course, st_status, stat_date, check_in_time, status_type, remarks)
       VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    // status_type/remarks can be NULL depending on status
    $checkInParam = $checkIn === null ? null : $checkIn;

    mysqli_stmt_bind_param(
      $ins,
      "sssssss",
      $stId,
      $course,
      $stStatus,
      $attDate,
      $checkInParam,
      $statusType,
      $remarks
    );

    $ok = mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);

    if ($ok) $inserted++;
  }
}

echo "Seed complete. Inserted rows: {$inserted}\n";

// Quick verification: how many rows exist in last 30 days?
$verify = mysqli_query($link, "SELECT COUNT(*) AS c FROM attendance WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$row = mysqli_fetch_assoc($verify);
echo "Attendance rows in last 30 days: " . ($row['c'] ?? 0) . "\n";
