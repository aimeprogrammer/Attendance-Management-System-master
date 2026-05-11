<?php
ob_start();
session_start();
if($_SESSION['name']!='oasis') {
  header('location: ../index.php');
  exit;
}
include('../connect.php');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

// Handle Approval/Rejection
if (isset($_POST['update_status'])) {
    if ($_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $leave_id = $_POST['leave_id'];
        $new_status = $_POST['status'];
        $st_id = $_POST['st_id'];

        // If approving, deduct from balance
        if ($new_status == 'approved') {
            mysqli_query($link, "UPDATE students SET leave_balance = leave_balance - 1 WHERE st_id = '$st_id'");
        }

        $stmt = mysqli_prepare($link, "UPDATE leave_requests SET status = ? WHERE leave_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $leave_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Add notification for student
        $msg = "Your leave request (ID: $leave_id) has been $new_status.";
        $notif_stmt = mysqli_prepare($link, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        mysqli_stmt_bind_param($notif_stmt, "ss", $st_id, $msg);
        mysqli_stmt_execute($notif_stmt);
        mysqli_stmt_close($notif_stmt);

        // Create system log - FIXED
        $action = "Leave request $leave_id for student $st_id was marked as $new_status.";
        $log_stmt = mysqli_prepare($link, "INSERT INTO system_logs (user_id, action) VALUES (?, ?)");
        $actor = $_SESSION['name'] ?? 'system'; // FIXED: Use actual session user
        mysqli_stmt_bind_param($log_stmt, "ss", $actor, $action);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
        
        // Redirect to refresh page
        header('location: leave_requests.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Leave Management - OAMS 1.0</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php include('includes/sidebar.php'); ?>

    <div class="main-content">
        <div class="page-content">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clipboard-list"></i> Leave Request Approval Workflow
                </div>
                <div class="card-body">
                    <div class="table-wrap">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Leave Date</th>
                                    <th>Reason</th>
                                    <th>Current Balance</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT l.*, s.leave_balance, s.st_name
                                        FROM leave_requests l
                                        INNER JOIN students s ON l.st_id = s.st_id
                                        WHERE l.status = 'pending'
                                        ORDER BY l.leave_date ASC";
                                $res = mysqli_query($link, $sql);
                                
                                if(!$res) {
                                    echo "<tr><td colspan='6' class='text-center text-danger'>SQL Error: " . mysqli_error($link) . "</td></tr>";
                                } elseif(mysqli_num_rows($res) == 0) {
                                    echo "<tr><td colspan='6' class='text-center'>No pending requests.</td></tr>";
                                } else {
                                    while($row = mysqli_fetch_assoc($res)):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['st_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['st_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['leave_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td><span class="badge badge-info"><?php echo (int)$row['leave_balance']; ?> days left</span></td>
                                    <td>
                                        <form method="post" class="form-inline" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="leave_id" value="<?php echo (int)$row['leave_id']; ?>">
                                            <input type="hidden" name="st_id" value="<?php echo htmlspecialchars($row['st_id']); ?>">
                                            <select name="status" class="form-control input-sm">
                                                <option value="pending">⏳ Pending</option>
                                                <option value="approved">✅ Approve</option>
                                                <option value="rejected">❌ Reject</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php
                                    endwhile;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
