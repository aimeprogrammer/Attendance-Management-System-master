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
    <title>Teachers - Teacher Dashboard</title>
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
                <h1>Teachers Directory</h1>
                <p style="color: var(--muted); margin: 0;">View faculty and teaching staff</p>
            </div>
            <div class="user-menu">
                <span>👨‍🏫 <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="../logout.php">Logout</a>
            </div>
        </div>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Filter Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-filter"></i> Filter Teachers
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div style="display: flex; gap: 15px; align-items: flex-end;">
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label for="tc_dept">
                                    <i class="fas fa-building"></i> Department
                                </label>
                                <input 
                                    type="text" 
                                    id="tc_dept" 
                                    name="tc_dept" 
                                    class="form-control" 
                                    placeholder="Enter department"
                                />
                            </div>
                            <button type="submit" name="tc_btn" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Teachers List -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <i class="fas fa-users"></i> Teacher List
                </div>
                <div class="card-body">
                    <div style="display:flex; justify-content:center;">
                        <div style="width:100%; max-width:1100px;">
                            <div class="table-wrap">
                                <table class="table table-striped" style="margin: 0 auto; text-align:center;">
                                    <thead>
                                        <tr>
                                            <th style="width: 18%;">Teacher ID</th>
                                            <th style="width: 26%;">Name</th>
                                            <th style="width: 18%;">Department</th>
                                            <th style="width: 20%;">Course</th>
                                            <th style="width: 18%;">Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if(isset($_POST['tc_btn'])) {
                                            $tc_dept = htmlspecialchars($_POST['tc_dept']);
                                            if(empty($tc_dept)) {
                                                echo "<tr><td colspan='5' class='text-center text-muted'><i class='fas fa-info-circle'></i> Please enter a department</td></tr>";
                                            } else {
                                                $query = "SELECT tc_id, tc_name, tc_dept, tc_course, tc_email FROM teachers WHERE tc_dept = ? ORDER BY tc_id ASC";
                                                $stmt = mysqli_prepare($link, $query);
                                                mysqli_stmt_bind_param($stmt, "s", $tc_dept);
                                                mysqli_stmt_execute($stmt);
                                                $result = mysqli_stmt_get_result($stmt);

                                                if(mysqli_num_rows($result) > 0) {
                                                    while($data = mysqli_fetch_assoc($result)) {
                                                        echo "<tr>";
                                                        echo "<td><strong>" . htmlspecialchars($data['tc_id']) . "</strong></td>";
                                                        echo "<td>" . htmlspecialchars($data['tc_name']) . "</td>";
                                                        echo "<td>" . htmlspecialchars($data['tc_dept']) . "</td>";
                                                        echo "<td>" . htmlspecialchars($data['tc_course']) . "</td>";
                                                        echo "<td><a href='mailto:" . htmlspecialchars($data['tc_email']) . "'>" . htmlspecialchars($data['tc_email']) . "</a></td>";
                                                        echo "</tr>";
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='5' class='text-center text-muted'><i class='fas fa-search'></i> No teachers found in " . htmlspecialchars($tc_dept) . " department</td></tr>";
                                                }
                                                mysqli_stmt_close($stmt);
                                            }
                                        } else {
                                            // Show all teachers if no filter applied
                                            $query = "SELECT tc_id, tc_name, tc_dept, tc_course, tc_email FROM teachers ORDER BY tc_id ASC LIMIT 100";
                                            $result = mysqli_query($link, $query);

                                            if(mysqli_num_rows($result) > 0) {
                                                while($data = mysqli_fetch_assoc($result)) {
                                                    echo "<tr>";
                                                    echo "<td><strong>" . htmlspecialchars($data['tc_id']) . "</strong></td>";
                                                    echo "<td>" . htmlspecialchars($data['tc_name']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($data['tc_dept']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($data['tc_course']) . "</td>";
                                                    echo "<td><a href='mailto:" . htmlspecialchars($data['tc_email']) . "'>" . htmlspecialchars($data['tc_email']) . "</a></td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='5' class='text-center text-muted'><i class='fas fa-database'></i> No teachers in system</td></tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Faculty Information
                </div>
                <div class="card-body">
                    <p>This page displays all faculty members in the system. You can:</p>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 8px 0;"><strong>✓ Filter by Department:</strong> Search for teachers by department</li>
                        <li style="padding: 8px 0;"><strong>✓ View Details:</strong> See complete teacher information and assigned courses</li>
                        <li style="padding: 8px 0;"><strong>✓ Contact:</strong> Click emails to send messages to faculty</li>
                        <li style="padding: 8px 0;"><strong>✓ Course Details:</strong> See which courses each teacher teaches</li>
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
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });
    });
</script>
