<?php
ob_start();
session_start();

if($_SESSION['name']!='oasis') {
  header('location: ../index.php');
  exit;
}

include('connect.php');
require_once __DIR__ . '/../lib/attendance_report.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - Student Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar Navigation (Unified) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-graduation-cap"></i>
                <span>Student Portal</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>

            <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">ACADEMICS</li>
            <li><a href="academics.php"><i class="fas fa-book-open"></i> Academic Information</a></li>
            <li><a href="exams_results.php"><i class="fas fa-sticky-note"></i> Exam & Results</a></li>
            <li><a href="attendance_report_page.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payment & Fees</a></li>
            <li><a href="notices.php"><i class="fas fa-bullhorn"></i> Notices & Announcements</a></li>
            <li><a href="communications.php"><i class="fas fa-comments"></i> Communications</a></li>

            <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">MY RECORDS</li>
            <li><a href="account.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
            <li><a href="report.php" class="active"><i class="fas fa-chart-bar"></i> Attendance Report</a></li>
            <li><a href="students.php"><i class="fas fa-users"></i> Class Directory</a></li>
            <li><a href="leave.php"><i class="fas fa-calendar"></i> Leave Requests</a></li>

            <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">NOTIFICATIONS</li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>

            <li><hr style="margin: 15px 0; border: none; border-top: 1px solid rgba(255,255,255,0.1);"></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div>
                <h1>Attendance Report</h1>
                <p style="color: var(--muted); margin: 0;">Track your attendance statistics</p>
            </div>
            <div class="user-menu">
                <span>👤 <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="../logout.php">Logout</a>
            </div>
        </div>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Filter Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-filter"></i> Filter Your Report
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; align-items: end;">
                            <div class="form-group">
                                <label for="whichcourse">
                                    <i class="fas fa-book"></i> Select Course
                                </label>
                                <select name="whichcourse" id="whichcourse">
                                    <option value="">-- Select a Course --</option>
                                    <option value="algo">Analysis of Algorithms</option>
                                    <option value="algolab">Analysis of Algorithms Lab</option>
                                    <option value="dbms">Database Management System</option>
                                    <option value="dbmslab">Database Management System Lab</option>
                                    <option value="weblab">Web Programming Lab</option>
                                    <option value="os">Operating System</option>
                                    <option value="oslab">Operating System Lab</option>
                                    <option value="obm">Object Based Modeling</option>
                                    <option value="softcomp">Soft Computing</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="sr_id">
                                    <i class="fas fa-id-card"></i> Your Registration No.
                                </label>
                                <input 
                                    type="text" 
                                    name="sr_id" 
                                    id="sr_id" 
                                    class="form-control" 
                                    placeholder="Enter your reg. no." 
                                />
                            </div>

                            <div>
                                <button type="submit" name="sr_btn" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            // CSV export (simple download)
            if (isset($_GET['export']) && $_GET['export'] === 'csv' && !empty($_GET['sr_id'])) {
                $export_sr_id = htmlspecialchars($_GET['sr_id']);
                $export_course = htmlspecialchars($_GET['whichcourse'] ?? '');
                if (empty($export_course)) {
                    $export_course = htmlspecialchars($_POST['whichcourse'] ?? '');
                }

                // If course is still empty, force user to re-generate report with a selected course.
                if (empty($export_course)) {
                    echo '<div class="alert alert-warning" style="margin-top: 20px;">Course is required for CSV export.</div>';
                } else {
                    $summary = ams_getAttendanceSummary($link, $export_sr_id, $export_course);
                    $records = ams_getAttendanceRecords($link, $export_sr_id, $export_course);

                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="attendance_report_'.date('Ymd').'.csv"');

                    $out = fopen('php://output', 'w');
                    fputcsv($out, ['Registration No.', $export_sr_id]);
                    fputcsv($out, ['Course', ucfirst($export_course)]);
                    if ($summary) {
                        fputcsv($out, ['Total Classes', $summary['total_classes']]);
                        fputcsv($out, ['Present (legacy)', $summary['present_days']]);
                        fputcsv($out, ['Absent (legacy)', $summary['absent_days']]);
                        fputcsv($out, ['Attendance % (weighted)', $summary['attendance_percentage']]);
                    }
                    fputcsv($out, []);
                    fputcsv($out, ['Date', 'Status', 'Check-in Time', 'Remarks']);
                    foreach ($records as $r) {
                        fputcsv($out, [
                            $r['stat_date'] ?? '',
                            $r['st_status'] ?? '',
                            $r['check_in_time'] ?? '',
                            $r['remarks'] ?? ''
                        ]);
                    }
                    fclose($out);
                    exit;
                }
            }

            // Check if form is submitted and generate report
            if(isset($_POST['sr_btn']) && !empty($_POST['sr_id'])) {
                $sr_id = htmlspecialchars($_POST['sr_id']);
                $course = htmlspecialchars($_POST['whichcourse']);

                if(empty($course)) {
                    echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Please select a course</div>';
                } else {
                    // Query for attendance data
                    $query = "SELECT COUNT(*) as total FROM attendance WHERE stat_id = ? AND course = ?";
                    $stmt = mysqli_prepare($link, $query);
                    mysqli_stmt_bind_param($stmt, "ss", $sr_id, $course);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_bind_result($stmt, $total_classes);
                    mysqli_stmt_fetch($stmt);
                    mysqli_stmt_close($stmt);

                    // Query for present count
                    $query = "SELECT COUNT(*) as present FROM attendance WHERE stat_id = ? AND course = ? AND st_status IN ('Present', 'present')";
                    $stmt = mysqli_prepare($link, $query);
                    mysqli_stmt_bind_param($stmt, "ss", $sr_id, $course);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_bind_result($stmt, $present_count);
                    mysqli_stmt_fetch($stmt);
                    mysqli_stmt_close($stmt);

                    $absent_count = $total_classes - $present_count;
                    $attendance_percentage = $total_classes > 0 ? round(($present_count / $total_classes) * 100, 1) : 0;

                    if($total_classes == 0) {
                        echo '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No attendance records found for this course</div>';
                    } else {
                        ?>

                        <!-- Report Stats -->
                        <div class="stats-grid" style="margin-top: 30px;">
                            <div class="stat-card info">
                                <div class="stat-icon">📊</div>
                                <div class="stat-label">Attendance Rate</div>
                                <div class="stat-value"><?php echo $attendance_percentage; ?>%</div>
                            </div>
                            <div class="stat-card success">
                                <div class="stat-icon">✓</div>
                                <div class="stat-label">Days Present</div>
                                <div class="stat-value"><?php echo $present_count; ?></div>
                            </div>
                            <div class="stat-card danger">
                                <div class="stat-icon">✗</div>
                                <div class="stat-label">Days Absent</div>
                                <div class="stat-value"><?php echo $absent_count; ?></div>
                            </div>
                            <div class="stat-card warning">
                                <div class="stat-icon">📚</div>
                                <div class="stat-label">Total Classes</div>
                                <div class="stat-value"><?php echo $total_classes; ?></div>
                            </div>
                        </div>

                        <!-- Detailed Report -->
                        <div class="card" style="margin-top: 30px;">
                            <div class="card-header">
                                <i class="fas fa-receipt"></i> Detailed Attendance Summary
                            </div>
                            <div class="card-body">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                                    <!-- Left Column -->
                                    <div>
                                        <h4 style="margin-bottom: 15px;">Registration Information</h4>
                                        <table class="table" style="font-size: 14px;">
                                            <tr>
                                                <td style="border: none;"><strong>Registration No.:</strong></td>
                                                <td style="border: none;"><?php echo $sr_id; ?></td>
                                            </tr>
                                            <tr>
                                                <td style="border: none;"><strong>Course:</strong></td>
                                                <td style="border: none;"><?php echo ucfirst($course); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="border: none;"><strong>Report Date:</strong></td>
                                                <td style="border: none;"><?php echo date('F j, Y'); ?></td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Right Column -->
                                    <div>
                                        <h4 style="margin-bottom: 15px;">Attendance Breakdown</h4>
                                        <table class="table" style="font-size: 14px;">
                                            <tr>
                                                <td style="border: none;"><strong>Total Classes:</strong></td>
                                                <td style="border: none;"><span class="badge badge-info"><?php echo $total_classes; ?></span></td>
                                            </tr>
                                            <tr>
                                                <td style="border: none;"><strong>Present:</strong></td>
                                                <td style="border: none;"><span class="badge badge-success"><?php echo $present_count; ?></span></td>
                                            </tr>
                                            <tr>
                                                <td style="border: none;"><strong>Absent:</strong></td>
                                                <td style="border: none;"><span class="badge badge-danger"><?php echo $absent_count; ?></span></td>
                                            </tr>
                                            <tr>
                                                <td style="border: none;"><strong style="font-size: 16px;">Percentage:</strong></td>
                                                <td style="border: none;">
                                                    <div style="font-size: 20px; font-weight: 700; color: var(--primary);"><?php echo $attendance_percentage; ?>%</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div style="margin-top: 30px;">
                                    <label style="margin-bottom: 10px; display: block;">Attendance Progress</label>
                                    <div style="background: var(--light); height: 25px; border-radius: 12px; overflow: hidden; position: relative;">
                                        <div style="background: linear-gradient(90deg, var(--success) 0%, var(--primary) 100%); height: 100%; width: <?php echo $attendance_percentage; ?>%; transition: width 0.5s ease;"></div>
                                        <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-weight: 600; font-size: 12px; z-index: 10;"><?php echo $attendance_percentage; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Records Table -->
                        <div class="card" style="margin-top: 30px;">
                            <div class="card-header">
                                <i class="fas fa-list"></i> Attendance Records
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Check-in Time</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT stat_date, st_status, check_in_time, remarks FROM attendance WHERE stat_id = ? AND course = ? ORDER BY stat_date DESC";
                                        $stmt = mysqli_prepare($link, $query);
                                        mysqli_stmt_bind_param($stmt, "ss", $sr_id, $course);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);

                                        if(mysqli_num_rows($result) > 0) {
                                            while($row = mysqli_fetch_assoc($result)) {
                                                $status_class = strtolower($row['st_status']) === 'present' ? 'success' : 'danger';
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['stat_date']) . "</td>";
                                                echo "<td><span class='badge badge-" . $status_class . "'>" . htmlspecialchars($row['st_status']) . "</span></td>";
                                                echo "<td>" . htmlspecialchars($row['check_in_time'] ?? '-') . "</td>";
                                                echo "<td><small>" . htmlspecialchars($row['remarks'] ?? '-') . "</small></td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center text-muted'>No records found</td></tr>";
                                        }
                                        mysqli_stmt_close($stmt);
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Export Options -->
                        <div class="card" style="margin-top: 30px;">
                            <div class="card-header">
                                <i class="fas fa-download"></i> Export Report
                            </div>
                            <div class="card-body">
                                <a
                                    class="btn btn-primary btn-sm"
                                    href="?export=csv&sr_id=<?php echo urlencode($sr_id); ?>&whichcourse=<?php echo urlencode($course); ?>" title="Export CSV">
                                    <i class="fas fa-file-csv"></i> Export as CSV
                                </a>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="fas fa-file-pdf"></i> Export as PDF
                                </button>
                                <button class="btn btn-success btn-sm" onclick="window.print()">
                                    <i class="fas fa-print"></i> Print Report
                                </button>
                            </div>
                        </div>


                        <?php
                    }
                }
            }
            ?>
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


</div>

</header>
<!-- Menus ended -->

<center>

<!-- Content, Tables, Forms, Texts, Images started -->
<div class="row">

  <div class="content">
    <h3>Student Report</h3>
    <br>
    <form method="post" action="" class="form-horizontal col-md-6 col-md-offset-3">

  <div class="form-group">

    <label  for="input1" class="col-sm-3 control-label">Select Subject</label>
      <div class="col-sm-4">
      <select name="whichcourse" id="input1">
         <option  value="algo">Analysis of Algorithms</option>
         <option  value="algolab">Analysis of Algorithms Lab</option>
        <option  value="dbms">Database Management System</option>
        <option  value="dbmslab">Database Management System Lab</option>
        <option  value="weblab">Web Programming Lab</option>
        <option  value="os">Operating System</option>
        <option  value="oslab">Operating System Lab</option>
        <option  value="obm">Object Based Modeling</option>
        <option  value="softcomp">Soft Computing</option>

      </select>
      </div>

  </div>

        <div class="form-group">
           <label for="input1" class="col-sm-3 control-label">Your Reg. No.</label>
              <div class="col-sm-7">
                  <input type="text" name="sr_id"  class="form-control" id="input1" placeholder="enter your reg. no." />
              </div>
        </div>
        <input type="submit" class="btn btn-primary col-md-3 col-md-offset-7" value="Go!" name="sr_btn" />
    </form>

    <div class="content"><br></div>

    <form method="post" action="" class="form-horizontal col-md-6 col-md-offset-3">
    <table class="table table-striped">

   <?php

    //checking the form for ID
    if(isset($_POST['sr_btn'])){

    //initializing ID 
     $sr_id = $_POST['sr_id'];
     $course = $_POST['whichcourse'];

     $i=0;
     $count_pre = 0;
     
     //query for searching respective ID
    //  $all_query = mysql_query("select * from reports where reports.st_id='$sr_id' and reports.course = '$course'");
    //  $count_tot = mysql_num_rows($all_query);
     $all_query = mysql_query("select stat_id,count(*) as countP from attendance where attendance.stat_id='$sr_id' and attendance.course = '$course' and attendance.st_status='Present'");
     $singleT= mysql_query("select count(*) as countT from attendance where attendance.stat_id='$sr_id' and attendance.course = '$course'");
     $count_tot;
     if ($row=mysql_fetch_row($singleT))
     {
     $count_tot=$row[0];
     }

     while ($data = mysql_fetch_array($all_query)) {
       $i++;
      //  if($data['st_status'] == "Present"){
      //     $count_pre++;
      //  }
       if($i <= 1){
     ?>
        

     <tbody>
      <tr>
          <td>Registration No.: </td>
          <td><?php echo $data['stat_id']; ?></td>
      </tr>

      <tr>
        <td>Total Class (Days): </td>
        <td><?php echo $count_tot; ?> </td>
      </tr>

      <tr>
        <td>Present (Days): </td>
        <td><?php echo $data[1]; ?> </td>
      </tr>

      <tr>
        <td>Absent (Days): </td>
        <td><?php echo $count_tot -  $data[1]; ?> </td>
      </tr>

    </tbody>

   <?php

     }  
    }}
     ?>
    </table>
  </form>
  </div>

</div>
<!-- Contents, Tables, Forms, Images ended -->

</center>

</body>


</html>
