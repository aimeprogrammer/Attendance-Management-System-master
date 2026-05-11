<?php
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
  header('location: ../index.php');
  exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

include('connect.php');

// Get teacher info from session or parameter
$tc_id = $_GET['tc_id'] ?? 'TCH001'; // Default for testing

$teacher_info = null;
$class_stats = null;

// Get teacher information
$stmt = mysqli_prepare($link, "SELECT * FROM teachers WHERE tc_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $tc_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$teacher_info = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$teacher_info) {
    $teacher_info = array(
        'tc_id' => 'TCH001',
        'tc_name' => 'Unknown Teacher',
        'tc_dept' => 'N/A',
        'tc_email' => 'unknown@faculty.com',
        'tc_course' => 'N/A'
    );
}

/**
 * Dashboard metrics (teacher) — production-ready to match your spec:
 * - Total students under supervision
 * - Today's present/absent status
 * - Classes last 30 days count
 * - Recent attendance records (last 10 days)
 *
 * Uses teacher_batches if that table exists; otherwise falls back to global data.
 */
$class_stats = [
    'total_students_supervision' => 0,
    'today_present' => 0,
    'today_absent' => 0,
    'classes_last_30_days' => 0,
];
$recent_attendance = [];

$today = date('Y-m-d');

// Determine supervision batches (if teacher_batches exists)
$batchIds = [];
$hasTeacherBatches = false;
$tblCheck = mysqli_query($link, "SHOW TABLES LIKE 'teacher_batches'");
if ($tblCheck && mysqli_num_rows($tblCheck) > 0) {
    $hasTeacherBatches = true;
    $teacherId = $_SESSION['teacher_id'] ?? null;

    if ($teacherId !== null) {
        $stmt = mysqli_prepare($link, "SELECT batch_id FROM teacher_batches WHERE teacher_id=?");
        mysqli_stmt_bind_param($stmt, "i", $teacherId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($res)) $batchIds[] = $r['batch_id'];
        mysqli_stmt_close($stmt);
    }
}

$inClause = '';
$inTypes = '';
$inParams = [];

if ($hasTeacherBatches && !empty($batchIds)) {
    $inClause = " IN (" . implode(',', array_fill(0, count($batchIds), '?')) . ")";
    $inTypes = str_repeat('s', count($batchIds));
    $inParams = $batchIds;
}

// Total students under supervision
if (!empty($batchIds)) {
    $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
    $q = "
      SELECT COUNT(DISTINCT s.id) AS cnt
      FROM students s
      JOIN student_batches sb ON sb.student_id=s.id
      WHERE sb.batch_id IN ($placeholders)
    ";
    $stmt = mysqli_prepare($link, $q);
    mysqli_stmt_bind_param($stmt, $inTypes, ...$inParams);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $cnt);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    $class_stats['total_students_supervision'] = (int)($cnt ?? 0);
} else {
    $stmt = mysqli_prepare($link, "SELECT COUNT(*) as cnt FROM students");
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $cnt);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    $class_stats['total_students_supervision'] = (int)($cnt ?? 0);
}

// Today's present/absent
if (!empty($batchIds)) {
    $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
    $q = "
      SELECT
        SUM(CASE WHEN a.st_status IN ('present','Present') THEN 1 ELSE 0 END) AS present_cnt,
        SUM(CASE WHEN a.st_status IN ('absent','Absent') THEN 1 ELSE 0 END) AS absent_cnt
      FROM attendance a
      JOIN students s ON s.st_id=a.stat_id
      JOIN student_batches sb ON sb.student_id=s.id
      WHERE a.stat_date=? AND sb.batch_id IN ($placeholders)
    ";
    $stmt = mysqli_prepare($link, $q);
    $types = 's' . $inTypes;
    $params = array_merge([$today], $inParams);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $presentCnt, $absentCnt);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $class_stats['today_present'] = (int)($presentCnt ?? 0);
    $class_stats['today_absent'] = (int)($absentCnt ?? 0);
} else {
    $q = "
      SELECT
        SUM(CASE WHEN st_status IN ('present','Present') THEN 1 ELSE 0 END) AS present_cnt,
        SUM(CASE WHEN st_status IN ('absent','Absent') THEN 1 ELSE 0 END) AS absent_cnt
      FROM attendance
      WHERE stat_date=?
    ";
    $stmt = mysqli_prepare($link, $q);
    mysqli_stmt_bind_param($stmt, "s", $today);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $presentCnt, $absentCnt);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $class_stats['today_present'] = (int)($presentCnt ?? 0);
    $class_stats['today_absent'] = (int)($absentCnt ?? 0);
}

// Classes last 30 days (distinct attendance dates within supervision)
if (!empty($batchIds)) {
    $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
    $q = "
      SELECT COUNT(DISTINCT DATE(a.stat_date)) AS cnt
      FROM attendance a
      JOIN students s ON s.st_id=a.stat_id
      JOIN student_batches sb ON sb.student_id=s.id
      WHERE a.stat_date>=DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND sb.batch_id IN ($placeholders)
    ";
    $stmt = mysqli_prepare($link, $q);
    mysqli_stmt_bind_param($stmt, $inTypes, ...$inParams);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $cnt30);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    $class_stats['classes_last_30_days'] = (int)($cnt30 ?? 0);
} else {
    $stmt = mysqli_prepare($link, "SELECT COUNT(DISTINCT DATE(stat_date)) as cnt FROM attendance WHERE stat_date>=DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $cnt30);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    $class_stats['classes_last_30_days'] = (int)($cnt30 ?? 0);
}

// Recent attendance records table (last 10 days)
if (!empty($batchIds)) {
    $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
    $q = "
      SELECT
        DATE(a.stat_date) AS date,
        COUNT(*) AS total,
        SUM(CASE WHEN a.st_status IN ('present','Present') THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN a.st_status IN ('absent','Absent') THEN 1 ELSE 0 END) AS absent
      FROM attendance a
      JOIN students s ON s.st_id=a.stat_id
      JOIN student_batches sb ON sb.student_id=s.id
      WHERE a.stat_date>=DATE_SUB(CURDATE(), INTERVAL 10 DAY)
        AND sb.batch_id IN ($placeholders)
      GROUP BY DATE(a.stat_date)
      ORDER BY DATE(a.stat_date) DESC
      LIMIT 10
    ";
    $stmt = mysqli_prepare($link, $q);
    mysqli_stmt_bind_param($stmt, $inTypes, ...$inParams);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $total = (int)($row['total'] ?? 0);
        $present = (int)($row['present'] ?? 0);
        $percentage = $total > 0 ? ($present / $total) * 100 : 0;
        $recent_attendance[] = [
            'date' => $row['date'],
            'total' => $total,
            'present' => $present,
            'absent' => (int)($row['absent'] ?? 0),
            'percentage' => $percentage,
        ];
    }
    mysqli_stmt_close($stmt);
} else {
    $q = "
      SELECT
        DATE(stat_date) AS date,
        COUNT(*) AS total,
        SUM(CASE WHEN st_status IN ('present','Present') THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN st_status IN ('absent','Absent') THEN 1 ELSE 0 END) AS absent
      FROM attendance
      WHERE stat_date>=DATE_SUB(CURDATE(), INTERVAL 10 DAY)
      GROUP BY DATE(stat_date)
      ORDER BY DATE(stat_date) DESC
      LIMIT 10
    ";
    $res = mysqli_query($link, $q);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $total = (int)($row['total'] ?? 0);
            $present = (int)($row['present'] ?? 0);
            $percentage = $total > 0 ? ($present / $total) * 100 : 0;
            $recent_attendance[] = [
                'date' => $row['date'],
                'total' => $total,
                'present' => $present,
                'absent' => (int)($row['absent'] ?? 0),
                'percentage' => $percentage,
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Attendance Management</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard-container">
    <?php include('includes/sidebar.php'); ?>

    <div class="main-content">
        <div class="top-header">
            <div>
                <h1><i class="fas fa-chalkboard-teacher"></i> Welcome, <?php echo htmlspecialchars($teacher_info['tc_name']); ?></h1>
                <p style="color: var(--muted); margin: 0;">Manage attendance and track student progress</p>
            </div>
            <div class="user-menu">
                <span>👨‍🏫 <?php echo htmlspecialchars($teacher_info['tc_id']); ?></span>
                <a href="../logout.php">Logout</a>
            </div>
        </div>

        <div class="page-content">
            <div class="stats-grid">
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-content">
                        <div class="stat-label">Total Students under Supervision</div>
                        <div class="stat-value"><?php echo (int)($class_stats['total_students_supervision'] ?? 0); ?></div>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-content">
                        <div class="stat-label">Students Present (Today)</div>
                        <div class="stat-value"><?php echo (int)($class_stats['today_present'] ?? 0); ?></div>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-list"></i></div>
                    <div class="stat-content">
                        <div class="stat-label">Students Absent (Today)</div>
                        <div class="stat-value"><?php echo (int)($class_stats['today_absent'] ?? 0); ?></div>
                    </div>
                </div>

                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-content">
                        <div class="stat-label">Classes Last 30 Days</div>
                        <div class="stat-value"><?php echo (int)($class_stats['classes_last_30_days'] ?? 0); ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-id-card"></i> Your Information
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div>
                            <p style="color: var(--muted); font-size: var(--font-xs); text-transform: uppercase; margin-bottom: 5px; font-weight: 600;">Teacher ID</p>
                            <p style="font-size: var(--font-lg); font-weight: 600; margin: 0;"><?php echo htmlspecialchars($teacher_info['tc_id']); ?></p>
                        </div>
                        <div>
                            <p style="color: var(--muted); font-size: var(--font-xs); text-transform: uppercase; margin-bottom: 5px; font-weight: 600;">Full Name</p>
                            <p style="font-size: var(--font-lg); font-weight: 600; margin: 0;"><?php echo htmlspecialchars($teacher_info['tc_name']); ?></p>
                        </div>
                        <div>
                            <p style="color: var(--muted); font-size: var(--font-xs); text-transform: uppercase; margin-bottom: 5px; font-weight: 600;">Department</p>
                            <p style="font-size: var(--font-lg); font-weight: 600; margin: 0;"><?php echo htmlspecialchars($teacher_info['tc_dept']); ?></p>
                        </div>
                        <div>
                            <p style="color: var(--muted); font-size: var(--font-xs); text-transform: uppercase; margin-bottom: 5px; font-weight: 600;">Course</p>
                            <p style="font-size: var(--font-lg); font-weight: 600; margin: 0;"><?php echo htmlspecialchars($teacher_info['tc_course']); ?></p>
                        </div>
                        <div>
                            <p style="color: var(--muted); font-size: var(--font-xs); text-transform: uppercase; margin-bottom: 5px; font-weight: 600;">Email</p>
                            <p style="font-size: var(--font-base); margin: 0;"><a href="mailto:<?php echo $teacher_info['tc_email']; ?>"><?php echo htmlspecialchars($teacher_info['tc_email']); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bolt"></i> Quick Actions
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="attendance.php" class="btn btn-primary">
                            <i class="fas fa-check-square"></i> Mark Attendance
                        </a>
                        <a href="students.php" class="btn btn-success">
                            <i class="fas fa-users"></i> Students
                        </a>
                        <a href="exams.php" class="btn btn-info">
                            <i class="fas fa-book-open"></i> Exams
                        </a>
                        <a href="results.php" class="btn btn-secondary">
                            <i class="fas fa-clipboard-list"></i> Enter / Recheck Marks
                        </a>
                        <a href="leave_requests.php" class="btn btn-warning">
                            <i class="fas fa-clipboard-list"></i> Leave Requests
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> Attendance Analytics
                </div>
                <div class="card-body">
                    <?php
                      $avgPercentage = 0.0;
                      $den = 0;
                      foreach ($recent_attendance as $ra) {
                        $avgPercentage += (float)$ra['percentage'];
                        $den++;
                      }
                      if ($den > 0) $avgPercentage = $avgPercentage / $den;
                    ?>
                    <div style="display:flex; gap:14px; flex-wrap:wrap; margin-bottom:14px;">
                        <div style="background:var(--bg-light); border:2px solid var(--border); border-radius:12px; padding:12px; min-width:220px;">
                            <div style="color:var(--muted); font-size:12px; font-weight:800; text-transform:uppercase;">10-day Avg Attendance %</div>
                            <div style="font-size:28px; font-weight:900; color:var(--primary);"><?php echo number_format($avgPercentage, 0); ?>%</div>
                        </div>
                        <div style="background:var(--bg-light); border:2px solid var(--border); border-radius:12px; padding:12px; min-width:220px;">
                            <div style="color:var(--muted); font-size:12px; font-weight:800; text-transform:uppercase;">Today Status</div>
                            <div style="font-size:16px; font-weight:800;">
                                Present: <?php echo (int)($class_stats['today_present'] ?? 0); ?> |
                                Absent: <?php echo (int)($class_stats['today_absent'] ?? 0); ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($recent_attendance)): ?>
                        <?php
                          $chartLabels = [];
                          $presentData = [];
                          $absentData = [];
                          foreach ($recent_attendance as $row) {
                              $chartLabels[] = (string)($row['date'] ?? '');
                              $presentData[] = (int)($row['present'] ?? 0);
                              $absentData[] = (int)($row['absent'] ?? 0);
                          }
                          $labelsJson = json_encode($chartLabels, JSON_UNESCAPED_UNICODE);
                          $presentJson = json_encode($presentData, JSON_UNESCAPED_UNICODE);
                          $absentJson = json_encode($absentData, JSON_UNESCAPED_UNICODE);
                        ?>

                        <div style="margin-bottom:14px; background:var(--bg-light); border:2px solid var(--border); border-radius:12px; padding:12px;">
                          <div style="color:var(--muted); font-size:12px; font-weight:800; text-transform:uppercase; margin-bottom:8px;">
                            Chart: Present vs Absent (Last 10 Days)
                          </div>
                          <div style="max-width:900px;">
                            <canvas id="attendanceChart" height="110"></canvas>
                          </div>

                          <script>
                            (function(){
                              const el = document.getElementById('attendanceChart');
                              if (!el || typeof Chart === 'undefined') return;

                              const labels = <?php echo $labelsJson; ?>;
                              const present = <?php echo $presentJson; ?>;
                              const absent = <?php echo $absentJson; ?>;

                              new Chart(el, {
                                type: 'line',
                                data: {
                                  labels: labels,
                                  datasets: [
                                    {
                                      label: 'Present',
                                      data: present,
                                      borderWidth: 2,
                                      pointRadius: 3,
                                      tension: 0.25,
                                      borderColor: '#22c55e',
                                      backgroundColor: 'rgba(34,197,94,0.15)'
                                    },
                                    {
                                      label: 'Absent',
                                      data: absent,
                                      borderWidth: 2,
                                      pointRadius: 3,
                                      tension: 0.25,
                                      borderColor: '#ef4444',
                                      backgroundColor: 'rgba(239,68,68,0.15)'
                                    }
                                  ]
                                },
                                options: {
                                  responsive: true,
                                  maintainAspectRatio: false,
                                  plugins: {
                                    legend: { labels: { font: { size: 12, weight: '800' } } },
                                    tooltip: { enabled: true }
                                  },
                                  scales: {
                                    x: { ticks: { maxRotation: 0, minRotation: 0, font: { size: 11, weight: '700' } } },
                                    y: { beginAtZero: true, ticks: { font: { size: 11, weight: '700' } } }
                                  }
                                }
                              });
                            })();
                          </script>
                        </div>

                        <div style="display:grid; gap:10px;">
                            <?php foreach ($recent_attendance as $row):
                                $present = (int)($row['present'] ?? 0);
                                $absent = (int)($row['absent'] ?? 0);
                                $total = max(1, (int)($row['total'] ?? ($present + $absent)));
                                $pPct = ($present / $total) * 100;
                                $aPct = ($absent / $total) * 100;
                            ?>
                                <div style="background:#fff; border:2px solid var(--border); border-radius:12px; padding:10px;">
                                    <div style="display:flex; justify-content:space-between; gap:10px; margin-bottom:8px; flex-wrap:wrap;">
                                        <div style="font-weight:900;"><?php echo htmlspecialchars($row['date']); ?></div>
                                        <div style="color:var(--muted); font-weight:800;">
                                            Present <?php echo $present; ?> | Absent <?php echo $absent; ?> (<?php echo (int)round((float)$row['percentage'],0); ?>%)
                                        </div>
                                    </div>
                                    <div style="height:14px; background:var(--border); border-radius:999px; overflow:hidden;">
                                        <div style="height:100%; width:<?php echo number_format($pPct,0); ?>%; background:var(--success); float:left;"></div>
                                        <div style="height:100%; width:<?php echo number_format($aPct,0); ?>%; background:var(--danger); float:left;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="color:var(--muted);">No attendance analytics available yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Attendance Records (Last 10 days) -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-table"></i> Recent Attendance Records
                </div>
                <div class="card-body">
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Total Students</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_attendance)): ?>
                                    <?php foreach ($recent_attendance as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                                            <td><?php echo (int)$row['total']; ?></td>
                                            <td><?php echo (int)$row['present']; ?></td>
                                            <td><?php echo (int)$row['absent']; ?></td>
                                            <td><?php echo number_format((float)$row['percentage'], 0); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5">No attendance records yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pending Results / Upcoming Exams / Announcements -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-hourglass-half"></i> Pending Result Entries
                </div>
                <div class="card-body">
                    <?php
                    $pendingCount = 0;
                    $hasExamSubjects = false;
                    $tblES = mysqli_query($link, "SHOW TABLES LIKE 'exam_subjects'");
                    if ($tblES && mysqli_num_rows($tblES) > 0) {
                        $hasExamSubjects = true;
                    }

                    if ($hasExamSubjects) {
                        $batchFilter = '';
                        $batchIds2 = [];
                        $types2 = '';

                        $tblCheck2 = mysqli_query($link, "SHOW TABLES LIKE 'teacher_batches'");
                        if ($tblCheck2 && mysqli_num_rows($tblCheck2) > 0) {
                            $teacherId2 = $_SESSION['teacher_id'] ?? null;
                            if ($teacherId2 !== null) {
                                $stmtTb = mysqli_prepare($link, "SELECT batch_id FROM teacher_batches WHERE teacher_id=?");
                                mysqli_stmt_bind_param($stmtTb, "i", $teacherId2);
                                mysqli_stmt_execute($stmtTb);
                                $resTb = mysqli_stmt_get_result($stmtTb);
                                while ($r = mysqli_fetch_assoc($resTb)) $batchIds2[] = $r['batch_id'];
                                mysqli_stmt_close($stmtTb);
                            }
                        }

                        if (!empty($batchIds2)) {
                            $placeholders2 = implode(',', array_fill(0, count($batchIds2), '?'));
                            $types2 = str_repeat('s', count($batchIds2));
                            $batchFilter = " WHERE e.batch_id IN ($placeholders2) ";
                        }

                        $sqlPending = "SELECT COUNT(*) AS cnt
                            FROM exam_subjects es
                            JOIN exams e ON e.exam_id=es.exam_id
                            {$batchFilter}
                            AND es.subject_id IS NOT NULL
                        ";

                        if (!empty($batchIds2)) {
                            $stmtP = mysqli_prepare($link, $sqlPending);
                            mysqli_stmt_bind_param($stmtP, $types2, ...$batchIds2);
                            mysqli_stmt_execute($stmtP);
                            $resP = mysqli_stmt_get_result($stmtP);
                            $rowP = mysqli_fetch_assoc($resP);
                            mysqli_stmt_close($stmtP);
                            $pendingCount = (int)($rowP['cnt'] ?? 0);
                        } else {
                            $resP = mysqli_query($link, $sqlPending);
                            if ($resP) {
                                $rowP = mysqli_fetch_assoc($resP);
                                $pendingCount = (int)($rowP['cnt'] ?? 0);
                            }
                        }
                    }
                    ?>
                    <div style="font-size: 34px; font-weight: 800; color: var(--info);">
                        <?php echo (int)$pendingCount; ?>
                    </div>
                    <div style="color: var(--muted); margin-top:6px;">
                        Items needing attention in your exam pipeline.
                    </div>
                    <div style="margin-top:12px;">
                        <a class="btn btn-primary" href="results.php"><i class="fas fa-pen"></i> Go to Results</a>
                    </div>
                </div>
            </div>

            <!-- Upcoming Exams -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-alt"></i> Upcoming Exams
                </div>
                <div class="card-body">
                    <?php
                    $upcoming = [];
                    $batchIds3 = [];

                    $tblCheck3 = mysqli_query($link, "SHOW TABLES LIKE 'teacher_batches'");
                    if ($tblCheck3 && mysqli_num_rows($tblCheck3) > 0) {
                        $teacherId3 = $_SESSION['teacher_id'] ?? null;
                        if ($teacherId3 !== null) {
                            $stmtTb3 = mysqli_prepare($link, "SELECT batch_id FROM teacher_batches WHERE teacher_id=?");
                            mysqli_stmt_bind_param($stmtTb3, "i", $teacherId3);
                            mysqli_stmt_execute($stmtTb3);
                            $resTb3 = mysqli_stmt_get_result($stmtTb3);
                            while ($r = mysqli_fetch_assoc($resTb3)) $batchIds3[] = $r['batch_id'];
                            mysqli_stmt_close($stmtTb3);
                        }
                    }

                    $whereUp = '';
                    $paramsUp = [];
                    $typesUp = '';

                    if (!empty($batchIds3)) {
                        $placeholders3 = implode(',', array_fill(0, count($batchIds3), '?'));
                        $whereUp = " WHERE e.batch_id IN ($placeholders3) ";
                        $typesUp = str_repeat('s', count($batchIds3));
                        $paramsUp = $batchIds3;
                    }

                    $sqlUp = "
                        SELECT e.exam_id, e.exam_name, e.exam_date, b.batch_name
                        FROM exams e
                        LEFT JOIN batches b ON b.batch_id=e.batch_id
                        $whereUp
                        AND e.exam_date >= CURDATE()
                        ORDER BY e.exam_date ASC
                        LIMIT 5
                    ";

                    if (!empty($batchIds3)) {
                        $stmtUp = mysqli_prepare($link, $sqlUp);
                        mysqli_stmt_bind_param($stmtUp, $typesUp, ...$paramsUp);
                        mysqli_stmt_execute($stmtUp);
                        $resUp = mysqli_stmt_get_result($stmtUp);
                        while ($r = mysqli_fetch_assoc($resUp)) $upcoming[] = $r;
                        mysqli_stmt_close($stmtUp);
                    } else {
                        $resUp = mysqli_query($link, preg_replace('/\\sWHERE\\s/', ' WHERE ', $sqlUp));
                        if ($resUp) while ($r = mysqli_fetch_assoc($resUp)) $upcoming[] = $r;
                    }
                    ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Date</th>
                                    <th>Batch</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($upcoming)): ?>
                                    <tr><td colspan="3">No upcoming exams found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($upcoming as $ex): ?>
                                        <tr>
                                            <td>
                                                <a class="btn btn-light" href="exams.php?exam_id=<?php echo urlencode($ex['exam_id']); ?>">
                                                    <i class="fas fa-eye"></i> <?php echo htmlspecialchars($ex['exam_name']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($ex['exam_date']); ?></td>
                                            <td><?php echo htmlspecialchars($ex['batch_name'] ?? $ex['exam_id']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Announcements -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bullhorn"></i> Class Announcements
                </div>
                <div class="card-body">
                    <?php
                    $announcements = [];
                    $resAnn = mysqli_query(
                      $link,
                      "SELECT announcement_id, title, content, published_date
                       FROM announcements
                       WHERE is_active = 1
                       ORDER BY published_date DESC
                       LIMIT 5"
                    );
                    if ($resAnn) while ($r = mysqli_fetch_assoc($resAnn)) $announcements[] = $r;
                    ?>
                    <?php if (empty($announcements)): ?>
                        <div style="color: var(--muted);">No announcements.</div>
                    <?php else: ?>
                        <?php foreach ($announcements as $a): ?>
                            <div style="background:#fff; border:2px solid var(--border); border-radius:12px; padding:12px; margin-bottom:12px;">
                                <div style="font-weight:800; margin-bottom:6px;">
                                    <?php echo htmlspecialchars($a['title']); ?>
                                </div>
                                <div style="color: var(--muted);">
                                    <?php echo nl2br(htmlspecialchars($a['content'])); ?>
                                </div>
                                <div style="color: var(--muted); margin-top:8px; font-size:12px;">
                                    <?php echo htmlspecialchars($a['published_date']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-lightbulb"></i> Quick Tips
                </div>
                <div class="card-body">
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 12px; border-bottom: 1px solid var(--border-light); display: flex; gap: 10px; align-items: flex-start;">
                            <i class="fas fa-check-circle" style="color: var(--success); margin-top: 2px;"></i>
                            <span><strong>Daily Marking:</strong> Mark attendance daily for accurate tracking of student presence.</span>
                        </li>
                        <li style="padding: 12px; border-bottom: 1px solid var(--border-light); display: flex; gap: 10px; align-items: flex-start;">
                            <i class="fas fa-info-circle" style="color: var(--info); margin-top: 2px;"></i>
                            <span><strong>Review Reports:</strong> Check the attendance reports regularly to identify trends.</span>
                        </li>
                        <li style="padding: 12px; display: flex; gap: 10px; align-items: flex-start;">
                            <i class="fas fa-user-circle" style="color: var(--primary); margin-top: 2px;"></i>
                            <span><strong>Manage Leaves:</strong> Review and approve student leave requests promptly.</span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            if (link.getAttribute('href').split('?')[0] === currentPage) {
                link.classList.add('active');
            }
        });
    });
</script>
</body>
</html>
