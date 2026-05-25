<?php
session_start();
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('location: ../index.php');
    exit;
}
include('connect.php');
$tcId = $_SESSION['tc_id'] ?? '';

// 1. Fetch Teacher Info & Department
$tcDept = '';
$stmt = mysqli_prepare($link, "SELECT tc_dept FROM teachers WHERE tc_id = ?");
mysqli_stmt_bind_param($stmt, "s", $tcId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $tcDept);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// 2. Summary Stats
$resStudents = mysqli_query($link, "SELECT COUNT(*) as total FROM students WHERE st_dept = '$tcDept'");
$totalStudents = mysqli_fetch_assoc($resStudents)['total'] ?? 0;

$resLeaves = mysqli_query($link, "SELECT COUNT(*) as pending FROM leave_requests lr JOIN students s ON lr.st_id = s.st_id WHERE s.st_dept = '$tcDept' AND lr.status = 'pending'");
$pendingLeaves = mysqli_fetch_assoc($resLeaves)['pending'] ?? 0;

// Calculate Department Average Pass Rate
$deptPassRate = 0;
$passSql = "SELECT ROUND(SUM(CASE WHEN me.grading IN ('pass', 'credit') THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as pass_rate 
            FROM marks_entries me 
            JOIN students s ON me.st_id = s.st_id 
            WHERE s.st_dept = ?";
$stmtP = mysqli_prepare($link, $passSql);
mysqli_stmt_bind_param($stmtP, "s", $tcDept);
mysqli_stmt_execute($stmtP);
mysqli_stmt_bind_result($stmtP, $deptPassRate);
mysqli_stmt_fetch($stmtP);
mysqli_stmt_close($stmtP);
if ($deptPassRate === null) $deptPassRate = 0;

// 3. Chart: Attendance by Batch (Top 5 in Dept)
$batches = [];
$attRates = [];
$batchSql = "SELECT s.st_batch, ROUND(SUM(CASE WHEN a.st_status IN ('Present','late','half-day') THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as rate
             FROM attendance a JOIN students s ON a.stat_id = s.st_id WHERE s.st_dept = ? GROUP BY s.st_batch LIMIT 5";
$stmtB = mysqli_prepare($link, $batchSql);
mysqli_stmt_bind_param($stmtB, "s", $tcDept);
mysqli_stmt_execute($stmtB);
$resB = mysqli_stmt_get_result($stmtB);
while($row = mysqli_fetch_assoc($resB)) {
    $batches[] = $row['st_batch'];
    $attRates[] = (float)$row['rate'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard - AMS</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard-container">
    <?php include('includes/sidebar.php'); ?>
    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-chalkboard-teacher"></i> Faculty Overview</h1>
            <div class="user-menu"><span><?php echo htmlspecialchars($tcDept); ?> Department</span></div>
        </div>

        <div class="page-content">
            <div class="stats-grid">
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-content">
                        <div class="stat-label">Students Under Supervision</div>
                        <div class="stat-value"><?php echo $totalStudents; ?></div>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-envelope-open-text"></i></div>
                    <div class="stat-content">
                        <div class="stat-label">Pending Leave Requests</div>
                        <div class="stat-value"><?php echo $pendingLeaves; ?></div>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="stat-content">
                        <div class="stat-label">Dept. Pass Rate</div>
                        <div class="stat-value"><?php echo $deptPassRate; ?>%</div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-top: 30px;">
                <div class="card">
                    <div class="card-header"><i class="fas fa-chart-bar"></i> Batch Attendance Performance (%)</div>
                    <div class="card-body">
                        <canvas id="batchChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><i class="fas fa-bolt"></i> Quick Tasks</div>
                    <div class="card-body">
                        <div class="quick-actions-grid" style="grid-template-columns: 1fr;">
                            <a href="attendance.php" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Mark Attendance</a>
                            <a href="manage_leaves.php" class="btn btn-warning btn-block"><i class="fas fa-calendar-check"></i> Review Leaves</a>
                            <a href="enter_results.php" class="btn btn-success btn-block"><i class="fas fa-poll"></i> Entry Exam Results</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
new Chart(document.getElementById('batchChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($batches); ?>,
        datasets: [{
            label: 'Avg. Attendance %',
            data: <?php echo json_encode($attRates); ?>,
            backgroundColor: '#3498db'
        }]
    },
    options: { 
        responsive: true, 
        scales: { y: { beginAtZero: true, max: 100 } }
    }
});
</script>
</body>
</html>