<?php
header('Content-Type: application/json');
include('connect.php');

$query = "
    SELECT 
        b.batch_name,
        ROUND(
            (SUM(CASE WHEN a.st_status = 'Present' THEN 1 ELSE 0 END) * 100.0) / 
            NULLIF(COUNT(a.stat_id), 0), 2
        ) as attendance_rate
    FROM batches b
    LEFT JOIN student_enrollments se ON b.batch_id = se.batch_id
    LEFT JOIN students s ON se.st_id = s.st_id
    LEFT JOIN attendance a ON s.st_id = a.stat_id
    WHERE a.stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY b.batch_id
    HAVING attendance_rate IS NOT NULL
    ORDER BY b.start_year DESC
";

$result = mysqli_query($link, $query);
$batches = [];
$attendance_rates = [];

while($row = mysqli_fetch_assoc($result)) {
    $batches[] = $row['batch_name'];
    $attendance_rates[] = floatval($row['attendance_rate']);
}

echo json_encode([
    'batches' => $batches,
    'attendance_rates' => $attendance_rates
]);
?>