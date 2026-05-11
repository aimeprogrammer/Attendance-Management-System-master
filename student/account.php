<?php
ob_start();
session_start();

if($_SESSION['name']!='oasis') {
  header('location: ../index.php');
  exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

include('connect.php');

$success_msg = "";
$error_msg = "";
$student_data = null;

try {
    if(isset($_POST['load_account'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Security validation failed (CSRF).");
        }

        $st_id = htmlspecialchars($_POST['st_id']);
        
        $stmt = mysqli_prepare($link, "SELECT st_id, st_name, st_dept, st_batch, st_sem, st_email FROM students WHERE st_id=?");
        mysqli_stmt_bind_param($stmt, "s", $st_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) > 0) {
            $student_data = mysqli_fetch_assoc($result);
        } else {
            throw new Exception("Student record not found!");
        }
        mysqli_stmt_close($stmt);
    }

    if(isset($_POST['done'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Security validation failed (CSRF).");
        }

        if (empty($_POST['name'])) {
            throw new Exception("Name cannot be empty");
        }
        if (empty($_POST['dept'])) {
            throw new Exception("Department cannot be empty");
        }
        if(empty($_POST['batch'])) {
            throw new Exception("Batch cannot be empty");
        }
        if(empty($_POST['email'])) {
            throw new Exception("Email cannot be empty");
        }

        $sid = htmlspecialchars($_POST['id']);
        
        $stmt = mysqli_prepare($link, "UPDATE students SET st_name=?, st_dept=?, st_batch=?, st_sem=?, st_email=? WHERE st_id=?");
        mysqli_stmt_bind_param($stmt, "ssiiss", $_POST['name'], $_POST['dept'], $_POST['batch'], $_POST['semester'], $_POST['email'], $sid);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = '✓ Account updated successfully!';
            // Reload student data
            $stmt2 = mysqli_prepare($link, "SELECT st_id, st_name, st_dept, st_batch, st_sem, st_email FROM students WHERE st_id=?");
            mysqli_stmt_bind_param($stmt2, "s", $sid);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);
            $student_data = mysqli_fetch_assoc($result2);
            mysqli_stmt_close($stmt2);
        }
        mysqli_stmt_close($stmt);
    }
}
catch(Exception $e) {
    $error_msg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Student Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar Navigation -->
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
            <li><a href="account.php" class="active"><i class="fas fa-user-circle"></i> My Profile</a></li>
            <li><a href="report.php"><i class="fas fa-chart-bar"></i> Attendance Report</a></li>
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
                <h1>My Account</h1>
                <p style="color: var(--muted); margin: 0;">Manage your profile information</p>
            </div>
            <div class="user-menu">
                <span>👤 <?php echo htmlspecialchars($_SESSION['name']); ?></span>
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

            <?php if(!$student_data): ?>
                <!-- Search Card -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-search"></i> Find Your Account
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div style="display: flex; gap: 15px; align-items: flex-end;">
                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                    <label for="st_id">
                                        <i class="fas fa-id-card"></i> Your Registration No.
                                    </label>
                                    <input 
                                        type="text" 
                                        id="st_id" 
                                        name="st_id" 
                                        class="form-control" 
                                        placeholder="Enter your registration number"
                                        required
                                    />
                                </div>
                                <button type="submit" name="load_account" class="btn btn-primary">
                                    <i class="fas fa-arrow-right"></i> Load Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card" style="margin-top: 30px;">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Getting Started
                    </div>
                    <div class="card-body">
                        <ol style="line-height: 1.8; color: var(--muted);">
                            <li>Enter your <strong>registration number</strong> above to locate your account</li>
                            <li>Once loaded, you can <strong>update your profile information</strong></li>
                            <li>All changes are <strong>immediately saved</strong> to the system</li>
                            <li>For security, your password cannot be changed here. <a href="../reset.php" style="color: var(--primary);">Reset password →</a></li>
                        </ol>
                    </div>
                </div>
            <?php else: ?>
                <!-- Profile Card -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user-circle"></i> Profile Information
                    </div>
                    <form method="post" action="">
                        <div class="card-body">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($student_data['st_id']); ?>">

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                                <!-- Left Column -->
                                <div>
                                    <div class="form-group">
                                        <label for="reg_no">
                                            <i class="fas fa-id-card"></i> Registration No.
                                        </label>
                                        <input 
                                            type="text" 
                                            id="reg_no" 
                                            class="form-control" 
                                            value="<?php echo htmlspecialchars($student_data['st_id']); ?>"
                                            disabled
                                        />
                                        <small style="color: var(--muted);">Cannot be changed</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="name">
                                            <i class="fas fa-user"></i> Full Name *
                                        </label>
                                        <input 
                                            type="text" 
                                            id="name" 
                                            name="name" 
                                            class="form-control" 
                                            value="<?php echo htmlspecialchars($student_data['st_name']); ?>"
                                            required
                                        />
                                    </div>

                                    <div class="form-group">
                                        <label for="dept">
                                            <i class="fas fa-building"></i> Department *
                                        </label>
                                        <input 
                                            type="text" 
                                            id="dept" 
                                            name="dept" 
                                            class="form-control" 
                                            value="<?php echo htmlspecialchars($student_data['st_dept']); ?>"
                                            required
                                        />
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div>
                                    <div class="form-group">
                                        <label for="batch">
                                            <i class="fas fa-graduation-cap"></i> Batch *
                                        </label>
                                        <input 
                                            type="text" 
                                            id="batch" 
                                            name="batch" 
                                            class="form-control" 
                                            value="<?php echo htmlspecialchars($student_data['st_batch']); ?>"
                                            required
                                        />
                                    </div>

                                    <div class="form-group">
                                        <label for="semester">
                                            <i class="fas fa-book"></i> Semester
                                        </label>
                                        <input 
                                            type="text" 
                                            id="semester" 
                                            name="semester" 
                                            class="form-control" 
                                            value="<?php echo htmlspecialchars($student_data['st_sem']); ?>"
                                        />
                                    </div>

                                    <div class="form-group">
                                        <label for="email">
                                            <i class="fas fa-envelope"></i> Email Address *
                                        </label>
                                        <input 
                                            type="email" 
                                            id="email" 
                                            name="email" 
                                            class="form-control" 
                                            value="<?php echo htmlspecialchars($student_data['st_email']); ?>"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="done" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="index.php" class="btn btn-light">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Account Actions -->
                <div class="card" style="margin-top: 30px;">
                    <div class="card-header">
                        <i class="fas fa-cog"></i> Account Actions
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <a href="../reset.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-key"></i> Change Password
                            </a>
                            <a href="../logout.php" class="btn btn-danger btn-block">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
            </div>
            <input type="submit" class="btn btn-primary col-md-3 col-md-offset-7" value="Go!" name="sr_btn" />
          </form>
          <div class="content"></div>


      <?php

      if(isset($_POST['sr_btn'])){

      //initializing student ID from form data
       $sr_id = $_POST['sr_id'];

       $i=0;

       //searching students information respected to the particular ID
       $stmt = mysqli_prepare($link, "SELECT * FROM students WHERE st_id=?");
       mysqli_stmt_bind_param($stmt, "s", $sr_id);
       mysqli_stmt_execute($stmt);
       $result = mysqli_stmt_get_result($stmt);

       while ($data = mysqli_fetch_array($result)) {
         $i++;
       
       ?>
<form action="" method="post" class="form-horizontal col-md-6 col-md-offset-3">
   <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
   <table class="table table-striped">
  
          <tr>
            <td>Registration No.:</td>
            <td><?php echo $data['st_id']; ?></td>
          </tr>

          <tr>
              <td>Student's Name:</td>
              <td><input type="text" name="name" value="<?php echo $data['st_name']; ?>"></input></td>
          </tr>

          <tr>
              <td>Department:</td>
              <td><input type="text" name="dept" value="<?php echo $data['st_dept']; ?>"></input></td>
          </tr>

          <tr>
              <td>Batch:</td>
              <td><input type="text" name="batch" value="<?php echo $data['st_batch']; ?>"></input></td>
          </tr>
          
          <tr>
              <td>Semester:</td>
              <td><input type="text" name="semester" value="<?php echo $data['st_sem']; ?>"></input></td>
          </tr>

          <tr>
              <td>Email:</td>
              <td><input type="text" name="email" value="<?php echo $data['st_email']; ?>"></input></td>
          </tr>
          <input type="hidden" name="id" value="<?php echo $sr_id; ?>">
          
          <tr><td></td></tr>
          <tr>
                <td></td>
                <td><input type="submit" class="btn btn-primary col-md-3 col-md-offset-7" value="Update" name="done" /></td>
                
          </tr>

    </table>
</form>
     <?php 
   } 
     }  
     ?>


      </div>

  </div>

  </center>
<!-- Contents, Tables, Forms, Images ended -->

</body>
<!-- Menus ended -->

</html>
