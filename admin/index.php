<?php
ob_start();
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header('location: ../index.php');
  exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

include('connect.php');

// Data insertion
try {
    if(isset($_POST['std'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
          throw new Exception("Security validation failed (CSRF).");
        }

        $stmt = mysqli_prepare($link, "INSERT INTO students (st_id, st_name, st_dept, st_batch, st_sem, st_email, leave_balance) VALUES (?, ?, ?, ?, ?, ?, 15)");
        mysqli_stmt_bind_param($stmt, "sssiis", $_POST['st_id'], $_POST['st_name'], $_POST['st_dept'], $_POST['st_batch'], $_POST['st_sem'], $_POST['st_email']);
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "✓ Student added successfully.";
        }
        mysqli_stmt_close($stmt);
    }

    if(isset($_POST['tcr'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
          throw new Exception("Security validation failed (CSRF).");
        }

        $stmt = mysqli_prepare($link, "INSERT INTO teachers (tc_id, tc_name, tc_dept, tc_email, tc_course) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", $_POST['tc_id'], $_POST['tc_name'], $_POST['tc_dept'], $_POST['tc_email'], $_POST['tc_course']);
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "✓ Teacher added successfully.";
        }
        mysqli_stmt_close($stmt);
    }
}
catch(Exception $e) {
    $error_msg = $e->getMessage();
}

// Get counts
$student_count = 0;
$teacher_count = 0;

$stmt = mysqli_prepare($link, "SELECT COUNT(*) as count FROM students");
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $student_count);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($link, "SELECT COUNT(*) as count FROM teachers");
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $teacher_count);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// ===== REAL DATA FOR CHARTS =====

// 1. BAR CHART: Students & Teachers by Department
$deptQuery = "SELECT 
    st_dept as dept,
    COUNT(st_id) as student_count,
    0 as teacher_count
FROM students
GROUP BY st_dept
UNION ALL
SELECT 
    tc_dept as dept,
    0 as student_count,
    COUNT(tc_id) as teacher_count
FROM teachers
GROUP BY tc_dept";

$deptResult = mysqli_query($link, $deptQuery);
$deptLabels = [];
$deptStudents = [];
$deptTeachers = [];

// Organize by department
$deptData = [];
while($row = mysqli_fetch_assoc($deptResult)) {
    $dept = $row['dept'];
    if (!isset($deptData[$dept])) {
        $deptData[$dept] = ['students' => 0, 'teachers' => 0];
    }
    $deptData[$dept]['students'] += (int)$row['student_count'];
    $deptData[$dept]['teachers'] += (int)$row['teacher_count'];
}

foreach ($deptData as $dept => $counts) {
    $deptLabels[] = $dept;
    $deptStudents[] = $counts['students'];
    $deptTeachers[] = $counts['teachers'];
}

        // 2. LINE GRAPH: Daily Attendance Trends (Last 30 Days)
$dateQuery = "SELECT 
    stat_date,
    COUNT(CASE WHEN LOWER(st_status) = 'present' THEN 1 END) as present_count,
    COUNT(*) as total_count
FROM attendance
WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY stat_date
ORDER BY stat_date ASC";

$dateResult = mysqli_query($link, $dateQuery);
$dates = [];
$presentCounts = [];
$totalCounts = [];

while($row = mysqli_fetch_assoc($dateResult)) {
    $dates[] = date('M d', strtotime($row['stat_date']));
    $presentCounts[] = (int)$row['present_count'];
    $totalCounts[] = (int)$row['total_count'];
}


// 3. PIE CHART: Overall Attendance Distribution
$pieQuery = "SELECT 
    LOWER(st_status) as status,
    COUNT(*) as count
FROM attendance
WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY LOWER(st_status)";

$pieResult = mysqli_query($link, $pieQuery);
$pieLabels = [];
$pieCounts = [];
$pieColors = [];
$colorMap = ['present' => '#27ae60', 'absent' => '#e74c3c', 'leave' => '#f39c12', 'late' => '#3498db'];

while($row = mysqli_fetch_assoc($pieResult)) {
    $status = strtolower($row['status']);
    $pieLabels[] = ucfirst($status);
    $pieCounts[] = (int)$row['count'];
    $pieColors[] = $colorMap[$status] ?? '#95a5a6';
}

// 4. SCATTER PLOT: Student Batch vs Average Attendance
$scatterQuery = "SELECT 
    s.st_batch,
    COUNT(DISTINCT s.st_id) as student_count,
    ROUND(100.0 * COUNT(CASE WHEN LOWER(a.st_status) = 'present' THEN 1 END) / NULLIF(COUNT(a.stat_id), 0), 2) as attendance_pct
FROM students s
LEFT JOIN attendance a ON s.st_id = a.stat_id
WHERE a.stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) OR a.stat_date IS NULL
GROUP BY s.st_batch
ORDER BY s.st_batch";

$scatterResult = mysqli_query($link, $scatterQuery);
$scatterBatches = [];
$scatterStudents = [];
$scatterAttendance = [];

while($row = mysqli_fetch_assoc($scatterResult)) {
    $scatterBatches[] = $row['st_batch'];
    $scatterStudents[] = (int)$row['student_count'];
    $scatterAttendance[] = floatval($row['attendance_pct'] ?? 0);
}

// 5. INFOGRAPHICS DATA: Key Metrics
$totalAttendanceQuery = "SELECT 
    COUNT(*) as total_records,
    COUNT(CASE WHEN st_status = 'present' OR st_status = 'Present' THEN 1 END) as total_present,
    ROUND(100.0 * COUNT(CASE WHEN st_status = 'present' OR st_status = 'Present' THEN 1 END) / NULLIF(COUNT(*), 0), 2) as overall_attendance_pct
FROM attendance
WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

$infResult = mysqli_query($link, $totalAttendanceQuery);
$infData = mysqli_fetch_assoc($infResult);

$avgAttendanceQuery = "SELECT 
    ROUND(AVG(daily_attendance), 2) as avg_daily_attendance
FROM (
    SELECT 
        stat_date,
        ROUND(100.0 * COUNT(CASE WHEN st_status = 'present' OR st_status = 'Present' THEN 1 END) / COUNT(*), 2) as daily_attendance
    FROM attendance
    WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY stat_date
) as daily_avg";

$avgResult = mysqli_query($link, $avgAttendanceQuery);
$avgData = mysqli_fetch_assoc($avgResult);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Attendance Management</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

</head>
<body>
<div class="dashboard-container">
    <!-- Shared Sidebar Navigation -->
    <?php $activePage = 'index.php'; include('sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p style="color: var(--muted); margin: 0;">Manage students, teachers, and system settings</p>
            </div>
            <div class="user-menu">
                <span>👨‍💼 Admin</span>
                <a href="../logout.php">Logout</a>
            </div>
        </div>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Status Messages -->
            <?php if(isset($success_msg)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if(isset($error_msg)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-content">
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value"><?php echo $student_count; ?></div>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-content">
                        <div class="stat-label">Total Teachers</div>
                        <div class="stat-value"><?php echo $teacher_count; ?></div>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-content">
                        <div class="stat-label">System Status</div>
                        <div class="stat-value" style="font-size: 18px;">Running</div>
                    </div>
                </div>
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-tachometer-alt"></i></div>
                    <div class="stat-content">
                        <div class="stat-label">System Load</div>
                        <div class="stat-value" style="font-size: 18px;">Normal</div>
                    </div>
                </div>
            </div>

            <!-- INFOGRAPHICS SECTION -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Key Metrics Overview
                    <span style="font-size: 13px; color: var(--muted); font-weight: 500; margin-left: 10px;">(Last 30 Days)</span>
                </div>
                <div class="card-body">
            <div class="infographics-grid">
                        <div class="infographic-box success">
                            <div class="infographic-icon"><i class="fas fa-user-check"></i></div>
                            <div class="infographic-label">Total Present</div>
                            <div class="infographic-value"><?php echo number_format($infData['total_present'] ?? 0); ?></div>
                        </div>
                        <div class="infographic-box danger">
                            <div class="infographic-icon"><i class="fas fa-user-times"></i></div>
                            <div class="infographic-label">Total Absent</div>
                            <div class="infographic-value"><?php echo number_format(($infData['total_records'] - $infData['total_present']) ?? 0); ?></div>
                        </div>
                        <div class="infographic-box primary">
                            <div class="infographic-icon"><i class="fas fa-percentage"></i></div>
                            <div class="infographic-label">Overall Attendance</div>
                            <div class="infographic-value"><?php echo $infData['overall_attendance_pct'] ?? 0; ?>%</div>
                        </div>
                        <div class="infographic-box warning">
                            <div class="infographic-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="infographic-label">Avg Daily Attendance</div>
                            <div class="infographic-value"><?php echo $avgData['avg_daily_attendance'] ?? 0; ?>%</div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- CHARTS SECTION -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> Advanced Analytics
                </div>
                <div class="card-body">
                    <div class="charts-grid">
                        <!-- BAR CHART: Students & Teachers by Department -->
                        <div class="chart-card">
                            <div class="chart-title"><i class="fas fa-building"></i> Students & Teachers by Department</div>
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="deptChart"></canvas>
                            </div>
                        </div>

                        <!-- PIE CHART: Attendance Distribution -->
                        <div class="chart-card">
                            <div class="chart-title"><i class="fas fa-chart-pie"></i> Attendance Distribution</div>
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="pieChart"></canvas>
                            </div>
                        </div>

                        <!-- LINE GRAPH: Daily Attendance Trends -->
                        <div class="chart-card" style="grid-column: 1 / -1;">
                            <div class="chart-title"><i class="fas fa-chart-line"></i> Daily Attendance Trends</div>
                            <div class="chart-container" style="height: 350px;">
                                <canvas id="lineChart"></canvas>
                            </div>
                        </div>

                        <!-- BAR CHART: Batch vs Average Attendance -->
                        <div class="chart-card" style="grid-column: 1 / -1;">
                            <div class="chart-title"><i class="fas fa-chart-bar"></i> Student Batch vs Average Attendance Rate</div>
                            <div class="chart-container" style="height: 350px;">
                                <canvas id="batchAttendanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bolt"></i> Quick Actions
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="signup.php" class="btn btn-primary">
                            <i class="fas fa-user-shield"></i> Create User
                        </a>
                        <a href="#student" class="btn btn-secondary">
                            <i class="fas fa-user-plus"></i> Add Student
                        </a>
                        <a href="#teacher" class="btn btn-success">
                            <i class="fas fa-chalkboard-teacher"></i> Add Teacher
                        </a>
                        <a href="../logout.php" class="btn btn-light">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>


            <!-- Two Column Layout for Forms -->
            <div class="two-column-form">
                <!-- Add Student Form -->
                <div class="card" id="student">
                    <div class="card-header">
                        <i class="fas fa-user-graduate"></i> Add Student's Information
                    </div>
                    <form method="post" class="card-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="st_id">Registration No. *</label>
                            <input type="text" name="st_id" id="st_id" class="form-control" placeholder="e.g., ST001" required />
                        </div>

                        <div class="form-group">
                            <label for="st_name">Full Name *</label>
                            <input type="text" name="st_name" id="st_name" class="form-control" placeholder="Student's full name" required />
                        </div>

                        <div class="form-group">
                            <label for="st_dept">Department *</label>
                            <input type="text" name="st_dept" id="st_dept" class="form-control" placeholder="e.g., CSE, ME" required />
                        </div>

                        <div class="form-group">
                            <label for="st_batch">Batch *</label>
                            <input type="text" name="st_batch" id="st_batch" class="form-control" placeholder="e.g., 2020" required />
                        </div>

                        <div class="form-group">
                            <label for="st_sem">Semester *</label>
                            <input type="text" name="st_sem" id="st_sem" class="form-control" placeholder="e.g., Fall-2023" required />
                        </div>

                        <div class="form-group">
                            <label for="st_email">Email Address *</label>
                            <input type="email" name="st_email" id="st_email" class="form-control" placeholder="student@example.com" required />
                        </div>

                        <button type="submit" name="std" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Add Student
                        </button>
                    </form>
                </div>

                <!-- Add Teacher Form -->
                <div class="card" id="teacher">
                    <div class="card-header">
                        <i class="fas fa-chalkboard-teacher"></i> Add Teacher's Information
                    </div>
                    <form method="post" class="card-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="tc_id">Teacher ID *</label>
                            <input type="text" name="tc_id" id="tc_id" class="form-control" placeholder="e.g., TCH001" required />
                        </div>

                        <div class="form-group">
                            <label for="tc_name">Full Name *</label>
                            <input type="text" name="tc_name" id="tc_name" class="form-control" placeholder="Teacher's full name" required />
                        </div>

                        <div class="form-group">
                            <label for="tc_dept">Department *</label>
                            <input type="text" name="tc_dept" id="tc_dept" class="form-control" placeholder="e.g., CSE" required />
                        </div>

                        <div class="form-group">
                            <label for="tc_email">Email Address *</label>
                            <input type="email" name="tc_email" id="tc_email" class="form-control" placeholder="teacher@example.com" required />
                        </div>

                        <div class="form-group">
                            <label for="tc_course">Subject Name *</label>
                            <input type="text" name="tc_course" id="tc_course" class="form-control" placeholder="e.g., Software Engineering" required />
                        </div>

                        <button type="submit" name="tcr" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Add Teacher
                        </button>
                    </form>
                </div>
            </div>

            <!-- System Info -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> System Information
                </div>
                <div class="card-body">
                    <table class="table">
                        <tbody>
                            <tr>
                                <td><strong>System Name:</strong></td>
                                <td>Online Attendance Management System v1.0</td>
                            </tr>
                            <tr>
                                <td><strong>Current Date & Time:</strong></td>
                                <td><?php echo date('F j, Y - H:i:s'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Users:</strong></td>
                                <td><?php echo $student_count + $teacher_count; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Last Backup:</strong></td>
                                <td>N/A (Manual backup recommended)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });

        // ===== REAL DATA FROM PHP =====
        const deptLabels = <?php echo json_encode($deptLabels); ?>;
        const deptStudents = <?php echo json_encode($deptStudents); ?>;
        const deptTeachers = <?php echo json_encode($deptTeachers); ?>;
        const dates = <?php echo json_encode($dates); ?>;
        const presentCounts = <?php echo json_encode($presentCounts); ?>;
        const totalCounts = <?php echo json_encode($totalCounts); ?>;
        const pieLabels = <?php echo json_encode($pieLabels); ?>;
        const pieCounts = <?php echo json_encode($pieCounts); ?>;
        const pieColors = <?php echo json_encode($pieColors); ?>;
        const scatterBatches = <?php echo json_encode($scatterBatches); ?>;
        const scatterStudents = <?php echo json_encode($scatterStudents); ?>;
        const scatterAttendance = <?php echo json_encode($scatterAttendance); ?>;

        // 1. BAR CHART: Students & Teachers by Department
        if (deptLabels.length > 0) {
            const deptCtx = document.getElementById('deptChart');
            if (deptCtx) {
                new Chart(deptCtx, {
                    type: 'bar',
                    data: {
                        labels: deptLabels,
                        datasets: [
                            {
                                label: 'Students',
                                data: deptStudents,
                                backgroundColor: 'rgba(52, 152, 219, 0.8)',
                                borderColor: '#2980b9',
                                borderWidth: 1,
                                borderRadius: 4
                            },
                            {
                                label: 'Teachers',
                                data: deptTeachers,
                                backgroundColor: 'rgba(39, 174, 96, 0.8)',
                                borderColor: '#1e8449',
                                borderWidth: 1,
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    precision: 0
                                },
                                grid: {
                                    display: true,
                                    color: 'rgba(0,0,0,0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        }

        // 2. PIE CHART: Attendance Distribution
        if (pieLabels.length > 0) {
            const pieCtx = document.getElementById('pieChart');
            if (pieCtx) {
                new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: pieLabels,
                        datasets: [{
                            data: pieCounts,
                            backgroundColor: pieColors,
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            }
                        }
                    }
                });
            }
        }

        // 3. LINE GRAPH: Daily Attendance Trends
        if (dates.length > 0) {
            const lineCtx = document.getElementById('lineChart');
            if (lineCtx) {
                new Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: [
                            {
                                label: 'Present',
                                data: presentCounts,
                                borderColor: '#27ae60',
                                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 3,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Total Count',
                                data: totalCounts,
                                borderColor: '#3498db',
                                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 3,
                                pointHoverRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0,0,0,0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        }

        // 4. BAR CHART: Batch vs Attendance %
        if (scatterBatches.length > 0) {
            const batchCtx = document.getElementById('batchAttendanceChart');
            if (batchCtx) {
                new Chart(batchCtx, {
                    type: 'bar',
                    data: {
                        labels: scatterBatches,
                        datasets: [{
                            label: 'Average Attendance %',
                            data: scatterAttendance,
                            backgroundColor: '#e74c3c',
                            borderColor: '#c0392b',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    usePointStyle: true
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                title: {
                                    display: true,
                                    text: 'Attendance %'
                                },
                                grid: {
                                    color: 'rgba(0,0,0,0.05)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Batch'
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        }
    });
</script>
</body>
</html>
