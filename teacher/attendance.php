<?php
ob_start();
session_start();
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
  header('location: ../index.php');
  exit;
}
include('connect.php');

$tcId = $_SESSION['tc_id'] ?? '';
// Fetch Teacher Department for scoping
$tcDept = '';
$stmt = mysqli_prepare($link, "SELECT tc_dept FROM teachers WHERE tc_id = ?");
mysqli_stmt_bind_param($stmt, "s", $tcId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $tcDept);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Fetch subjects for the teacher's department
$subjects_list = [];
$qSub = "SELECT s.subject_id, s.subject_name, s.subject_code 
         FROM subjects s 
         JOIN programs p ON s.program_id = p.program_id 
         WHERE p.program_name = ? OR p.program_id = ?";
$stmtSub = mysqli_prepare($link, $qSub);
mysqli_stmt_bind_param($stmtSub, "ss", $tcDept, $tcDept);
mysqli_stmt_execute($stmtSub);
$resSub = mysqli_stmt_get_result($stmtSub);
while($row = mysqli_fetch_assoc($resSub)) $subjects_list[] = $row;
mysqli_stmt_close($stmtSub);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

$success_msg = "";
$error_msg = "";

// Handle Attendance Submission
if (isset($_POST['att_save'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security validation failed.";
    } else {
        $course = htmlspecialchars($_POST['course']);
        $att_date = htmlspecialchars($_POST['att_date']);
        
        if(empty($course) || empty($att_date)) {
            $error_msg = "Please select course and date";
        } else {
            $success = true;
            foreach ($_POST['st_id'] as $index => $st_id) {
                $status = htmlspecialchars($_POST['status'][$index]);
                $check_in = !empty($_POST['check_in'][$index]) ? htmlspecialchars($_POST['check_in'][$index]) : NULL;
                $remarks = htmlspecialchars($_POST['remarks'][$index]);
                $legacy_status = ($status == 'absent') ? 'Absent' : 'Present';

                // Delete existing to prevent duplicates
                $del_stmt = mysqli_prepare($link, "DELETE FROM attendance WHERE stat_id=? AND course=? AND stat_date=?");
                mysqli_stmt_bind_param($del_stmt, "sss", $st_id, $course, $att_date);
                mysqli_stmt_execute($del_stmt);
                mysqli_stmt_close($del_stmt);

                // Insert new record
                $ins_stmt = mysqli_prepare($link, "INSERT INTO attendance (stat_id, course, st_status, stat_date, check_in_time, status_type, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($ins_stmt, "sssssss", $st_id, $course, $legacy_status, $att_date, $check_in, $status, $remarks);
                if (!mysqli_stmt_execute($ins_stmt)) {
                    $success = false;
                }
                mysqli_stmt_close($ins_stmt);
            }
            
            if($success) {
                $success_msg = "✓ Attendance for " . date('F j, Y', strtotime($att_date)) . " has been saved successfully!";
                
                // Log the action
                $log_action = "Marked attendance for Course: $course on Date: $att_date";
                $actor = $_SESSION['tc_id'] ?? $_SESSION['name'];
                $log_stmt = mysqli_prepare($link, "INSERT INTO system_logs (user_id, action) VALUES (?, ?)");
                mysqli_stmt_bind_param($log_stmt, "ss", $actor, $log_action);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            } else {
                $error_msg = "Error saving attendance. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Teacher Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .attendance-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px; }
        .status-input { display: flex; gap: 10px; }
        .status-toggle { display: flex; border-radius: 6px; overflow: hidden; border: 1px solid var(--border); }
        .status-toggle input { display: none; }
        .status-toggle label { flex: 1; padding: 8px 12px; text-align: center; cursor: pointer; transition: all 0.3s; font-size: 12px; font-weight: 500; }
        .status-toggle input:checked + label { background: var(--primary); color: white; }
        .status-toggle.danger input:checked + label { background: var(--danger); }
        .student-row { display: grid; grid-template-columns: 0.5fr 2fr 1fr 1.5fr 1fr; gap: 15px; align-items: end; padding: 15px; background: var(--bg-light); border-radius: 8px; margin-bottom: 10px; }
        @media (max-width: 1024px) { .student-row { grid-template-columns: 1fr; gap: 10px; } }
    </style>
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
                <h1>Mark Attendance</h1>
                <p style="color: var(--muted); margin: 0;">Record student attendance for your classes</p>
            </div>
            <div class="user-menu">
                <span>👨‍🏫 <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="../logout.php">Logout</a>
            </div>
        </div>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Status Messages -->
            <?php if($success_msg): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <!-- Filter Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-filter"></i> Select Class & Date
                </div>
                <div class="card-body">
                    <form method="get" action="" id="filterForm">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; align-items: flex-end;">
                            <div class="form-group">
                                <label for="course_filter">
                                    <i class="fas fa-book"></i> Course *
                                </label>
                                <select name="course_filter" id="course_filter" class="form-control" required>
                                    <option value="">-- Select Course --</option>
                                    <?php foreach($subjects_list as $sub): ?>
                                        <option value="<?php echo htmlspecialchars($sub['subject_code']); ?>" <?php echo ($_GET['course_filter'] ?? '') == $sub['subject_code'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sub['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date_filter">
                                    <i class="fas fa-calendar"></i> Date *
                                </label>
                                <input 
                                    type="date" 
                                    name="date_filter" 
                                    id="date_filter" 
                                    class="form-control" 
                                    value="<?php echo $_GET['date_filter'] ?? date('Y-m-d'); ?>"
                                    required
                                />
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-arrow-right"></i> Load Class
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php 
            // Load students if course and date are selected
            if(isset($_GET['course_filter']) && isset($_GET['date_filter'])):
                $course_filter = htmlspecialchars($_GET['course_filter']);
                $date_filter = htmlspecialchars($_GET['date_filter']);
                
                $query = "SELECT st_id, st_name, st_dept, st_batch FROM students WHERE st_dept = ? ORDER BY st_id ASC";
                $stmt = mysqli_prepare($link, $query);
                mysqli_stmt_bind_param($stmt, "s", $tcDept);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
                mysqli_stmt_close($stmt);
                
                // Pre-load existing attendance status
                $existing = [];
                $loadStmt = mysqli_prepare($link, "SELECT stat_id, status_type FROM attendance WHERE course = ? AND stat_date = ?");
                mysqli_stmt_bind_param($loadStmt, "ss", $course_filter, $date_filter);
                mysqli_stmt_execute($loadStmt);
                $resLoad = mysqli_stmt_get_result($loadStmt);
                while($row = mysqli_fetch_assoc($resLoad)) {
                    $existing[$row['stat_id']] = $row['status_type'];
                }
                mysqli_stmt_close($loadStmt);

                if(count($students) > 0):
            ?>

            <!-- Attendance Marking Card -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <i class="fas fa-users-check"></i> Mark Attendance - <?php echo date('F j, Y', strtotime($date_filter)); ?>
                </div>
                <form method="post" action="">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="course" value="<?php echo $course_filter; ?>">
                        <input type="hidden" name="att_date" value="<?php echo $date_filter; ?>">

                        <!-- Summary Stats -->
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
                            <div style="background: var(--bg-light); padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: var(--primary);"><?php echo count($students); ?></div>
                                <div style="font-size: 12px; color: var(--muted);">Total Students</div>
                            </div>
                            <div style="background: var(--bg-light); padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: var(--success);" id="presentCount">0</div>
                                <div style="font-size: 12px; color: var(--muted);">Marked Present</div>
                            </div>
                            <div style="background: var(--bg-light); padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: var(--danger);" id="absentCount"><?php echo count($students); ?></div>
                                <div style="font-size: 12px; color: var(--muted);">Marked Absent</div>
                            </div>
                            <div style="background: var(--bg-light); padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: var(--info);" id="percentageCount">0%</div>
                                <div style="font-size: 12px; color: var(--muted);">Attendance %</div>
                            </div>
                        </div>

                        <!-- Bulk Actions -->
                        <div style="display: flex; gap: 10px; margin-bottom: 20px; padding: 15px; background: var(--bg-light); border-radius: 8px;">
                            <button type="button" class="btn btn-success btn-sm" onclick="markAll('present')">
                                <i class="fas fa-check-double"></i> Mark All Present
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="markAll('absent')">
                                <i class="fas fa-times-circle"></i> Mark All Absent
                            </button>
                        </div>

                        <!-- Students List -->
                        <div style="max-height: 600px; overflow-y: auto;">
                            <?php foreach($students as $index => $student): 
                                $st_id = htmlspecialchars($student['st_id']);
                                $st_name = htmlspecialchars($student['st_name']);
                                $currentStatus = $existing[$st_id] ?? 'present';
                            ?>
                            <div class="student-row">
                                <div style="font-weight: 600; color: var(--primary);"><?php echo $st_id; ?></div>
                                <div><?php echo $st_name; ?></div>
                                <div style="font-size: 12px; color: var(--muted);"><?php echo htmlspecialchars($student['st_dept']); ?></div>
                                
                                <!-- Status Toggle -->
                                <div class="status-toggle">
                                    <input type="radio" name="status[<?php echo $index; ?>]" id="present_<?php echo $index; ?>" value="present" <?php echo $currentStatus == 'present' ? 'checked' : ''; ?>>
                                    <label for="present_<?php echo $index; ?>"><i class="fas fa-check"></i> Present</label>
                                    
                                    <input type="radio" name="status[<?php echo $index; ?>]" id="absent_<?php echo $index; ?>" value="absent" <?php echo $currentStatus == 'absent' ? 'checked' : ''; ?>>
                                    <label for="absent_<?php echo $index; ?>" style="background: var(--border);"><i class="fas fa-times"></i> Absent</label>
                                </div>

                                <!-- Check-in Time -->
                                <input 
                                    type="time" 
                                    name="check_in[<?php echo $index; ?>]" 
                                    class="form-control" 
                                    placeholder="Check-in time"
                                />
                                
                                <input type="hidden" name="st_id[<?php echo $index; ?>]" value="<?php echo $st_id; ?>">
                                <input type="hidden" name="remarks[<?php echo $index; ?>]" value="">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="att_save" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Save Attendance for <?php echo date('M d, Y', strtotime($date_filter)); ?>
                        </button>
                    </div>
                </form>
            </div>

            <script>
                function markAll(status) {
                    const inputs = document.querySelectorAll('input[type="radio"]');
                    inputs.forEach(input => {
                        if(input.value === status) input.checked = true;
                    });
                    updateCounts();
                }

                function updateCounts() {
                    const present = document.querySelectorAll('input[value="present"]:checked').length;
                    const total = <?php echo count($students); ?>;
                    const absent = total - present;
                    const percentage = total > 0 ? Math.round((present / total) * 100) : 0;
                    
                    document.getElementById('presentCount').textContent = present;
                    document.getElementById('absentCount').textContent = absent;
                    document.getElementById('percentageCount').textContent = percentage + '%';
                }

                // Add event listeners to all status radios
                document.querySelectorAll('input[type="radio"]').forEach(input => {
                    input.addEventListener('change', updateCounts);
                });

                // Initial count
                updateCounts();
            </script>

            <?php else: ?>
                <div class="alert alert-info" style="margin-top: 30px;">
                    <i class="fas fa-info-circle"></i> No students found in the system.
                </div>
            <?php endif; ?>

            <?php endif; ?>

            <!-- Instructions Card -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <i class="fas fa-question-circle"></i> How to Mark Attendance
                </div>
                <div class="card-body">
                    <ol style="line-height: 1.8;">
                        <li><strong>Select Course:</strong> Choose the course/subject from the dropdown</li>
                        <li><strong>Select Date:</strong> Pick the date for attendance marking</li>
                        <li><strong>Load Class:</strong> Click "Load Class" to display all students</li>
                        <li><strong>Mark Status:</strong> Click "Present" or "Absent" for each student</li>
                        <li><strong>Optional Check-in:</strong> Record check-in time if needed</li>
                        <li><strong>Bulk Actions:</strong> Use "Mark All Present" or "Mark All Absent" buttons for quick marking</li>
                        <li><strong>Save:</strong> Click "Save Attendance" to submit the records</li>
                    </ol>
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
