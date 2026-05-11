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
    <title>Students - Teacher Dashboard</title>
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
                <h1>Students Management</h1>
                <p style="color: var(--muted); margin: 0;">View and manage student records</p>
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
                    <i class="fas fa-filter"></i> Filter Students
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div style="display: flex; gap: 15px; align-items: flex-end;">
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label for="sr_batch">
                                    <i class="fas fa-graduation-cap"></i> Batch (e.g., 2020)
                                </label>
                                <input 
                                    type="text" 
                                    id="sr_batch" 
                                    name="sr_batch" 
                                    class="form-control" 
                                    placeholder="Enter batch year"
                                />
                            </div>
                            <button type="submit" name="sr_btn" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Students List -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <i class="fas fa-list"></i> Students List
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Reg. No.</th>
                                <th style="width: 25%;">Name</th>
                                <th style="width: 15%;">Department</th>
                                <th style="width: 12%;">Batch</th>
                                <th style="width: 12%;">Semester</th>
                                <th style="width: 20%;">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if(isset($_POST['sr_btn'])) {
                                $sr_batch = htmlspecialchars($_POST['sr_batch']);
                                if(empty($sr_batch)) {
                                    echo "<tr><td colspan='6' class='text-center text-muted'><i class='fas fa-info-circle'></i> Please enter a batch number</td></tr>";
                                } else {
                                    $query = "SELECT st_id, st_name, st_dept, st_batch, st_sem, st_email FROM students WHERE st_batch = ? ORDER BY st_id ASC";
                                    $stmt = mysqli_prepare($link, $query);
                                    mysqli_stmt_bind_param($stmt, "s", $sr_batch);
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);

                                    if(mysqli_num_rows($result) > 0) {
                                        while($data = mysqli_fetch_assoc($result)) {
                                            echo "<tr>";
                                            echo "<td><strong>" . htmlspecialchars($data['st_id']) . "</strong></td>";
                                            echo "<td>" . htmlspecialchars($data['st_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($data['st_dept']) . "</td>";
                                            echo "<td>" . htmlspecialchars($data['st_batch']) . "</td>";
                                            echo "<td>" . htmlspecialchars($data['st_sem']) . "</td>";
                                            echo "<td><a href='mailto:" . htmlspecialchars($data['st_email']) . "'>" . htmlspecialchars($data['st_email']) . "</a></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center text-muted'><i class='fas fa-search'></i> No students found for batch " . htmlspecialchars($sr_batch) . "</td></tr>";
                                    }
                                    mysqli_stmt_close($stmt);
                                }
                            } else {
                                // Show all students if no filter applied
                                $query = "SELECT st_id, st_name, st_dept, st_batch, st_sem, st_email FROM students ORDER BY st_id ASC LIMIT 100";
                                $result = mysqli_query($link, $query);

                                if(mysqli_num_rows($result) > 0) {
                                    while($data = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td><strong>" . htmlspecialchars($data['st_id']) . "</strong></td>";
                                        echo "<td>" . htmlspecialchars($data['st_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($data['st_dept']) . "</td>";
                                        echo "<td>" . htmlspecialchars($data['st_batch']) . "</td>";
                                        echo "<td>" . htmlspecialchars($data['st_sem']) . "</td>";
                                        echo "<td><a href='mailto:" . htmlspecialchars($data['st_email']) . "'>" . htmlspecialchars($data['st_email']) . "</a></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center text-muted'><i class='fas fa-database'></i> No students in system</td></tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Student Information
                </div>
                <div class="card-body">
                    <p>This page displays all students in the system. You can:</p>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 8px 0;"><strong>✓ Filter by Batch:</strong> Search for students by their batch year</li>
                        <li style="padding: 8px 0;"><strong>✓ View Details:</strong> See complete student information</li>
                        <li style="padding: 8px 0;"><strong>✓ Contact:</strong> Click emails to contact students directly</li>
                        <li style="padding: 8px 0;"><strong>✓ Mark Attendance:</strong> Go to "Mark Attendance" to record class attendance</li>
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
</body>
</html>
          } 
              }
      ?>
      
    </table>

  </div>

</div>

</center>

</body>
</html>
