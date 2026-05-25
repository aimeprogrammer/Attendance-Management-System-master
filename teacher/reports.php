<?php
ob_start();
session_start();
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
  header('location: ../index.php');
  exit;
}
include('connect.php');

$tcId = $_SESSION['tc_id'] ?? '';
$tcDept = '';
$stmt = mysqli_prepare($link, "SELECT tc_dept FROM teachers WHERE tc_id = ?");
mysqli_stmt_bind_param($stmt, "s", $tcId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $tcDept);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Analytics - Teacher Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <?php include('includes/sidebar.php'); ?>

    <div class="main-content">
        <div class="top-header">
            <div>
                <h1>Attendance Analytics</h1>
                <p style="color: var(--muted); margin: 0;">In-depth performance and trend analysis</p>
            </div>
            <div class="user-menu">
                <span>👨‍🏫 <?php echo htmlspecialchars($tcId); ?></span>
                <a href="../logout.php">Logout</a>
            </div>
        </div>

        <div class="page-content">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-filter"></i> Filter Analytics Range
                </div>
                <div class="card-body">
                    <form method="get" style="display: flex; gap: 20px; align-items: flex-end;">
                        <div class="form-group" style="margin: 0;">
                            <label>From Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $_GET['start_date'] ?? date('Y-m-01'); ?>">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>To Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $_GET['end_date'] ?? date('Y-m-d'); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                        <a href="reports.php" class="btn btn-light">Reset</a>
                    </form>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 3fr; gap: 20px; margin-top: 30px;">
                <!-- Class Trends Sidebar -->
                <div class="card">
                    <div class="card-header"><i class="fas fa-chart-line"></i> Class Trends</div>
                    <div class="card-body" style="padding: 0;">
                        <div class="list-group">
                            <?php
                            $trend_res = mysqli_query($link, "SELECT course, ROUND(SUM(CASE WHEN status_type IN ('present', 'late') THEN 1 WHEN status_type='half-day' THEN 0.5 ELSE 0 END)/COUNT(*)*100,1) as avg FROM attendance GROUP BY course");
                            while($t = mysqli_fetch_assoc($trend_res)): ?>
                                <div style="padding: 15px; border-bottom: 1px solid var(--border-light);">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <strong><?php echo strtoupper($t['course']); ?></strong>
                                        <span><?php echo $t['avg']; ?>%</span>
                                    </div>
                                    <div class="progress"><div class="progress-bar" style="width:<?php echo $t['avg']; ?>%"></div></div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <!-- Main Analytics Table -->
                <div class="card">
                    <div class="card-header"><i class="fas fa-table"></i> Monthly Attendance Breakdown</div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Reg No.</th>
                                    <th>Student Name</th>
                                    <th>Month</th>
                                    <th>Present</th>
                                    <th>Late</th>
                                    <th>Half-Day</th>
                                    <th>Absent</th>
                                    <th>Total</th>
                                    <th>Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $start = mysqli_real_escape_string($link, $_GET['start_date'] ?? date('Y-01-01'));
                                $end = mysqli_real_escape_string($link, $_GET['end_date'] ?? date('Y-m-d'));

                                $sql = "SELECT 
                                            s.st_id, s.st_name,
                                            MONTHNAME(a.stat_date) as month_name,
                                            COUNT(CASE WHEN a.status_type = 'present' THEN 1 END) as p_count,
                                            COUNT(CASE WHEN a.status_type = 'late' THEN 1 END) as l_count,
                                            COUNT(CASE WHEN a.status_type = 'half-day' THEN 1 END) as h_count,
                                            COUNT(CASE WHEN a.status_type = 'absent' THEN 1 END) as a_count,
                                            COUNT(*) as total_days
                                        FROM students s
                                        JOIN attendance a ON s.st_id = a.stat_id
                                        WHERE a.stat_date BETWEEN '$start' AND '$end' AND s.st_dept = ?
                                        GROUP BY s.st_id, MONTH(a.stat_date)
                                        ORDER BY MONTH(a.stat_date) DESC, s.st_id ASC";
                                
                                $stmtA = mysqli_prepare($link, $sql);
                                mysqli_stmt_bind_param($stmtA, "s", $tcDept);
                                mysqli_stmt_execute($stmtA);
                                $resA = mysqli_stmt_get_result($stmtA);
                                while($row = mysqli_fetch_assoc($resA)):
                                    $weighted_present = $row['p_count'] + $row['l_count'] + ($row['h_count'] * 0.5);
                                    $percentage = ($weighted_present / $row['total_days']) * 100;
                                    $row_class = ($percentage < 75) ? "danger" : "";
                                ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td><?php echo htmlspecialchars($row['st_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['st_name']); ?></td>
                                    <td><?php echo $row['month_name']; ?></td>
                                    <td><?php echo $row['p_count']; ?></td>
                                    <td><?php echo $row['l_count']; ?></td>
                                    <td><?php echo $row['h_count']; ?></td>
                                    <td><?php echo $row['a_count']; ?></td>
                                    <td><?php echo $row['total_days']; ?></td>
                                    <td><strong><?php echo number_format($percentage, 1); ?>%</strong></td>
                                </tr>
                                <?php endwhile; mysqli_stmt_close($stmtA); ?>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 20px; font-size: 13px; color: var(--muted);">
                            <p><strong>Note:</strong> Rate = (Present + Late + 0.5 * HalfDay) / Total. Rows highlighted in red are below the 75% threshold.</p>
                        </div>
                    </div>
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
    });
</script>
</body>
</html>
