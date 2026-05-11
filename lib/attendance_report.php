<?php
// Shared attendance reporting helper (mysqli-based)
// Weighted attendance rules:
//   present/late => 1.0
//   half-day      => 0.5
//   absent        => 0

if (!isset($link)) {
    // Expect $link from including file
    // (Do not fail hard here; helpers accept $link when called)
}

function ams_weighted_present_sql(string $statusCol = 'status_type'): string {
    // Returns weighted sum expression for use in SQL SELECT.
    // Note: assumes status_type values are: present, late, half-day, absent
    return "SUM(CASE 
        WHEN {$statusCol} IN ('present','late') THEN 1
        WHEN {$statusCol}='half-day' THEN 0.5
        WHEN {$statusCol}='absent' THEN 0
        ELSE 0
    END)";
}

function ams_weighted_total_sql(string $statusCol = 'status_type'): string {
    // Total count of attendance rows (days)
    return "COUNT(*)";
}

function ams_getAttendanceSummary(mysqli $link, string $statId, string $course): ?array {

    // Total classes (days) for that student + course
    $stmt = mysqli_prepare($link, "SELECT COUNT(*) as total_days FROM attendance WHERE stat_id = ? AND course = ?");
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, "ss", $statId, $course);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $totalDays);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Present/late count (legacy uses only Present/present; keep consistent with current teacher marks too)
    $stmt = mysqli_prepare($link, "SELECT 
            SUM(CASE WHEN st_status IN ('Present','present') THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN st_status IN ('Absent','absent') THEN 1 ELSE 0 END) as absent_days
        FROM attendance
        WHERE stat_id = ? AND course = ?");
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, "ss", $statId, $course);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $presentDaysLegacy, $absentDaysLegacy);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Weighted percentage based on status_type
    $weightedExpr = ams_weighted_present_sql('status_type');

    $weightedSql = "SELECT {$weightedExpr} as weighted_present, COUNT(*) as total_days
                    FROM attendance
                    WHERE stat_id = ? AND course = ?";

    $stmt = mysqli_prepare($link, $weightedSql);
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, "ss", $statId, $course);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $weightedPresent, $totalDays2);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $totalDays = (int)$totalDays;
    $totalDays2 = (int)$totalDays2;
    if ($totalDays === 0 && $totalDays2 > 0) $totalDays = $totalDays2;

    $attendancePct = $totalDays > 0 ? round(((float)$weightedPresent / (float)$totalDays) * 100, 1) : 0.0;

    // Absent count: prefer total - weighted_present day-units isn’t equal to absent days,
    // so for UI keep legacy present/absent counts.
    $presentDays = (int)($presentDaysLegacy ?? 0);
    $absentDays = (int)($absentDaysLegacy ?? 0);

    if ($presentDays === 0 && $absentDays === 0 && $totalDays > 0) {
        // If legacy st_status is inconsistent, fall back to counting by status_type.
        $stmt = mysqli_prepare($link, "SELECT 
                    SUM(CASE WHEN status_type IN ('present','late') THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status_type='absent' THEN 1 ELSE 0 END) as absent_days
                FROM attendance
                WHERE stat_id = ? AND course = ?");
        mysqli_stmt_bind_param($stmt, "ss", $statId, $course);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $presentDaysFallback, $absentDaysFallback);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        $presentDays = (int)($presentDaysFallback ?? 0);
        $absentDays = (int)($absentDaysFallback ?? 0);
    }

    return [
        'stat_id' => $statId,
        'course' => $course,
        'total_classes' => (int)$totalDays,
        'present_days' => (int)$presentDays,
        'absent_days' => (int)$absentDays,
        'attendance_percentage' => (float)$attendancePct,
    ];
}

function ams_getAttendanceRecords(mysqli $link, string $statId, string $course, ?int $limit = null): array {
    $sql = "SELECT stat_date, st_status, status_type, check_in_time, remarks
            FROM attendance
            WHERE stat_id = ? AND course = ?
            ORDER BY stat_date DESC";

    if ($limit !== null) {
        $sql .= " LIMIT ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $statId, $course, $limit);
    } else {
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $statId, $course);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function ams_courseLabel(string $course): string {
    $map = [
        'algo' => 'Analysis of Algorithms',
        'algolab' => 'Analysis of Algorithms Lab',
        'dbms' => 'Database Management System',
        'dbmslab' => 'Database Management System Lab',
        'weblab' => 'Web Programming Lab',
        'os' => 'Operating System',
        'oslab' => 'Operating System Lab',
        'obm' => 'Object Based Modeling',
        'softcomp' => 'Soft Computing',
        // legacy / teacher filter mismatch (seen in teacher/reports.php)
        'webdev' => 'Web Development',
    ];
    return $map[$course] ?? ucfirst($course);
}

?>

