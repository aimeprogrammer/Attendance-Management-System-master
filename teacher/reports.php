<?php
ob_start();
session_start();
if($_SESSION['name']!='oasis') {
  header('location: ../index.php');
  exit;
}
include('../connect.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>AMS</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
</head>
<body>
<header>
    <h1 style="margin-left:27rem;"> Attendance Management System </h1>
    <div class="navbar">
        <a href="index.php">Home</a>
        <a href="attendance.php">Take Attendance</a>
        <a href="reports.php">Reports</a>
        <a href="leave_requests.php">Leave Requests</a>
        <a href="../logout.php">Logout</a>
    </div>
</header>

<div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <?php include('includes/sidebar.php'); ?>

    <div class="main-content">
        <div class="page-content">
            <center><h3>Attendance Dashboard & Analytics</h3></center>

    <div class="well">
        <form method="get" class="form-inline text-center">
            <div class="form-group">
                <label>From:</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $_GET['start_date'] ?? date('Y-m-01'); ?>">
            </div>
            <div class="form-group">
                <label>To:</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $_GET['end_date'] ?? date('Y-m-d'); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filter Range</button>
            <a href="reports.php" class="btn btn-default">Reset</a>
        </form>
    </div>

    <div class="row">
        <div class="col-md-3">
            <h4>Class Trends</h4>
            <div class="list-group">
                <?php
                $trend_res = mysql_query("SELECT course, ROUND(SUM(CASE WHEN status_type IN ('present', 'late') THEN 1 WHEN status_type='half-day' THEN 0.5 ELSE 0 END)/COUNT(*)*100,1) as avg FROM attendance GROUP BY course");
                while($t = mysql_fetch_assoc($trend_res)): ?>
                    <div class="list-group-item">
                        <strong><?php echo $t['course']; ?></strong>: <?php echo $t['avg']; ?>%
                        <div class="progress" style="height:10px; margin-top:5px;"><div class="progress-bar progress-bar-info" style="width:<?php echo $t['avg']; ?>%"></div></div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <div class="col-md-9">
            <h4>Monthly Report Breakdown</h4>

    <table class="table table-striped table-hover mt-4">
        <thead>
            <tr class="info">
                <th>Reg No.</th>
                <th>Student Name</th>
                <th>Month</th>
                <th>Present</th>
                <th>Late</th>
                <th>Half-Day</th>
                <th>Absent</th>
                <th>Total Days</th>
                <th>Attendance %</th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $link;
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
                    WHERE a.stat_date BETWEEN '$start' AND '$end'
                    GROUP BY s.st_id, MONTH(a.stat_date)
                    ORDER BY MONTH(a.stat_date) DESC, s.st_id ASC";
            
            $res = mysql_query($sql);
            while($row = mysql_fetch_array($res)):
                // Formula: (Present + Late + 0.5 * HalfDay) / Total
                $weighted_present = $row['p_count'] + $row['l_count'] + ($row['h_count'] * 0.5);
                $percentage = ($weighted_present / $row['total_days']) * 100;
                
                $row_class = ($percentage < 75) ? "danger" : "";
            ?>
            <tr class="<?php echo $row_class; ?>">
                <td><?php echo $row['st_id']; ?></td>
                <td><?php echo $row['st_name']; ?></td>
                <td><?php echo $row['month_name']; ?></td>
                <td><?php echo $row['p_count']; ?></td>
                <td><?php echo $row['l_count']; ?></td>
                <td><?php echo $row['h_count']; ?></td>
                <td><?php echo $row['a_count']; ?></td>
                <td><?php echo $row['total_days']; ?></td>
                <td><strong><?php echo number_format($percentage, 2); ?>%</strong></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="well">
        <h4>Calculation Logic:</h4>
        <ul>
            <li><strong>Present/Late:</strong> Counts as 1 full day.</li>
            <li><strong>Half-Day:</strong> Counts as 0.5 day.</li>
            <li><strong>Low Attendance:</strong> Rows in <span style="color:red">red</span> indicate attendance below 75%.</li>
        </ul>
    </div>
</div>
</body>
</html>
