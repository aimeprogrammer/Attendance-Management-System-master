<?php
ob_start();
session_start();

if($_SESSION['name']!='oasis') {
  header('location: ../index.php');
  exit;
}

include('connect.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Teacher Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <?php include('includes/sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div>
                <h1>Attendance Reports</h1>
                <p style="color: var(--muted); margin: 0;">View and analyze attendance data</p>
            </div>
            <div class="user-menu">
                <span>👨‍🏫 <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="../logout.php">Logout</a>
            </div>
        </div>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Tabs Navigation -->
            <div style="display: flex; gap: 0; margin-bottom: 30px; border-bottom: 2px solid var(--border);">
                <button class="tab-btn active" onclick="switchTab('individual')" style="padding: 15px 25px; background: none; border: none; border-bottom: 3px solid var(--primary); color: var(--primary); font-weight: 600; cursor: pointer; margin-bottom: -2px;">
                    <i class="fas fa-user"></i> Individual Report
                </button>
                <button class="tab-btn" onclick="switchTab('class')" style="padding: 15px 25px; background: none; border: none; border-bottom: 3px solid transparent; color: var(--muted); font-weight: 600; cursor: pointer; margin-bottom: -2px;">
                    <i class="fas fa-users"></i> Class Report
                </button>
                <button class="tab-btn" onclick="switchTab('daily')" style="padding: 15px 25px; background: none; border: none; border-bottom: 3px solid transparent; color: var(--muted); font-weight: 600; cursor: pointer; margin-bottom: -2px;">
                    <i class="fas fa-calendar"></i> Daily Report
                </button>
            </div>

            <!-- Individual Report Tab -->
            <div id="individual-tab" class="tab-content active">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-search"></i> Search Individual Report
                    </div>
                    <div class="card-body">
                        <form method="post" action="" id="individualForm">
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; align-items: flex-end;">
                                <div class="form-group">
                                    <label for="course">
                                        <i class="fas fa-book"></i> Select Course *
                                    </label>
                                    <select name="whichcourse" id="course" class="form-control" required>
                                        <option value="">-- Select Course --</option>
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
                                        <i class="fas fa-id-card"></i> Student Reg. No. *
                                    </label>
                                    <input 
                                        type="text" 
                                        name="sr_id" 
                                        id="sr_id" 
                                        class="form-control" 
                                        placeholder="Enter registration number"
                                        required
                                    />
                                </div>
                                <button type="submit" name="sr_btn" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Generate Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php
                // Individual Report Generation
                if(isset($_POST['sr_btn']) && !empty($_POST['sr_id'])) {
                    $sr_id = htmlspecialchars($_POST['sr_id']);
                    $course = htmlspecialchars($_POST['whichcourse']);

                    if(empty($course)) {
                        echo '<div class="alert alert-warning" style="margin-top: 20px;"><i class="fas fa-exclamation-triangle"></i> Please select a course</div>';
                    } else {
                        // Get student info
                        $student_query = "SELECT st_name, st_dept, st_batch FROM students WHERE st_id = ?";
                        $stmt = mysqli_prepare($link, $student_query);
                        mysqli_stmt_bind_param($stmt, "s", $sr_id);
                        mysqli_stmt_execute($stmt);
                        $student_result = mysqli_stmt_get_result($stmt);
                        $student = mysqli_fetch_assoc($student_result);
                        mysqli_stmt_close($stmt);

                        if(!$student) {
                            echo '<div class="alert alert-danger" style="margin-top: 20px;"><i class="fas fa-times-circle"></i> Student not found</div>';
                        } else {
                            // Count attendance
                            $query = "SELECT COUNT(*) as total FROM attendance WHERE stat_id = ? AND course = ?";
                            $stmt = mysqli_prepare($link, $query);
                            mysqli_stmt_bind_param($stmt, "ss", $sr_id, $course);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_bind_result($stmt, $total_classes);
                            mysqli_stmt_fetch($stmt);
                            mysqli_stmt_close($stmt);

                            $query = "SELECT COUNT(*) as present FROM attendance WHERE stat_id = ? AND course = ? AND st_status IN ('Present', 'present')";
                            $stmt = mysqli_prepare($link, $query);
                            mysqli_stmt_bind_param($stmt, "ss", $sr_id, $course);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_bind_result($stmt, $present_count);
                            mysqli_stmt_fetch($stmt);
                            mysqli_stmt_close($stmt);

                            $absent_count = $total_classes - $present_count;
                            $percentage = $total_classes > 0 ? round(($present_count / $total_classes) * 100, 1) : 0;

                            if($total_classes == 0) {
                                echo '<div class="alert alert-info" style="margin-top: 20px;"><i class="fas fa-info-circle"></i> No attendance records found</div>';
                            } else {
                                ?>
                                <!-- Report Card -->
                                <div class="card" style="margin-top: 30px;">
                                    <div class="card-header">
                                        <i class="fas fa-receipt"></i> Attendance Report for <?php echo htmlspecialchars($student['st_name']); ?>
                                    </div>
                                    <div class="card-body">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                                            <!-- Student Info -->
                                            <div>
                                                <h4 style="margin-bottom: 15px;">Student Information</h4>
                                                <table style="width: 100%; font-size: 14px;">
                                                    <tr>
                                                        <td style="padding: 8px 0; border: none;"><strong>Registration No.:</strong></td>
                                                        <td style="padding: 8px 0; border: none;"><?php echo $sr_id; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; border: none;"><strong>Name:</strong></td>
                                                        <td style="padding: 8px 0; border: none;"><?php echo htmlspecialchars($student['st_name']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; border: none;"><strong>Department:</strong></td>
                                                        <td style="padding: 8px 0; border: none;"><?php echo htmlspecialchars($student['st_dept']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; border: none;"><strong>Batch:</strong></td>
                                                        <td style="padding: 8px 0; border: none;"><?php echo htmlspecialchars($student['st_batch']); ?></td>
                                                    </tr>
                                                </table>
                                            </div>

                                            <!-- Stats -->
                                            <div>
                                                <h4 style="margin-bottom: 15px;">Attendance Statistics</h4>
                                                <table style="width: 100%; font-size: 14px;">
                                                    <tr>
                                                        <td style="padding: 8px 0; border: none;"><strong>Total Classes:</strong></td>
                                                        <td style="padding: 8px 0; border: none;"><span class="badge badge-info"><?php echo $total_classes; ?></span></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; border: none;"><strong>Present:</strong></td>
                                                        <td style="padding: 8px 0; border: none;"><span class="badge badge-success"><?php echo $present_count; ?></span></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; border: none;"><strong>Absent:</strong></td>
                                                        <td style="padding: 8px 0; border: none;"><span class="badge badge-danger"><?php echo $absent_count; ?></span></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; border: none;"><strong style="font-size: 16px;">Percentage:</strong></td>
                                                        <td style="padding: 8px 0; border: none;"><strong style="font-size: 18px; color: var(--primary);"><?php echo $percentage; ?>%</strong></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Progress Bar -->
                                        <div style="margin-top: 20px;">
                                            <label>Attendance Progress</label>
                                            <div style="background: var(--light); height: 30px; border-radius: 15px; overflow: hidden; position: relative;">
                                                <div style="background: linear-gradient(90deg, var(--success) 0%, var(--primary) 100%); height: 100%; width: <?php echo $percentage; ?>%; transition: width 0.5s ease;"></div>
                                                <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-weight: 600; z-index: 10; text-shadow: 0 1px 3px rgba(0,0,0,0.3);"><?php echo $percentage; ?>%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detailed Records -->
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
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $query = "SELECT stat_date, st_status, check_in_time FROM attendance WHERE stat_id = ? AND course = ? ORDER BY stat_date DESC";
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
                                                        echo "</tr>";
                                                    }
                                                }
                                                mysqli_stmt_close($stmt);
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                    }
                }
                ?>
            </div>

            <!-- Class Report Tab -->
            <div id="class-tab" class="tab-content" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users"></i> Class Attendance Summary
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
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
                                <?php
                                $query = "SELECT stat_date, COUNT(*) as total, 
                                        SUM(CASE WHEN st_status IN ('Present', 'present') THEN 1 ELSE 0 END) as present
                                        FROM attendance 
                                        WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                        GROUP BY stat_date 
                                        ORDER BY stat_date DESC";
                                $result = mysqli_query($link, $query);
                                
                                if(mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        $percentage = ($row['present'] / $row['total']) * 100;
                                        $absent = $row['total'] - $row['present'];
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['stat_date']) . "</td>";
                                        echo "<td>" . $row['total'] . "</td>";
                                        echo "<td><span class='badge badge-success'>" . $row['present'] . "</span></td>";
                                        echo "<td><span class='badge badge-danger'>" . $absent . "</span></td>";
                                        echo "<td><strong>" . round($percentage, 1) . "%</strong></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>No data available</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Daily Report Tab -->
            <div id="daily-tab" class="tab-content" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-calendar"></i> Daily Attendance Details
                    </div>
                    <div class="card-body">
                        <p style="color: var(--muted); margin-bottom: 20px;">Select a date to view detailed attendance records</p>
                        <p><em>Daily reports feature coming soon...</em></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function switchTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
            tab.classList.remove('active');
        });
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.style.borderBottomColor = 'transparent';
            btn.style.color = 'var(--muted)';
        });
        
        // Show selected tab
        document.getElementById(tabName + '-tab').style.display = 'block';
        document.getElementById(tabName + '-tab').classList.add('active');
        
        // Add active class to clicked button
        event.target.closest('.tab-btn').style.borderBottomColor = 'var(--primary)';
        event.target.closest('.tab-btn').style.color = 'var(--primary)';
    }

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
