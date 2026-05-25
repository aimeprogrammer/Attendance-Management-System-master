<?php
session_start();
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('location: ../index.php');
    exit;
}
include('connect.php');

// Fetch recent logs
$logs = [];
$sql = "SELECT log_id, user_id, action, created_at FROM system_logs ORDER BY created_at DESC LIMIT 100";
$res = mysqli_query($link, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $logs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Logs - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <div class="main-content" style="margin-left:0; width:100%;">
        <div class="top-header">
            <h1><i class="fas fa-fingerprint"></i> System Activity Logs</h1>
            <div class="user-menu">
                <a href="index.php" class="btn btn-light">Back to Dashboard</a>
            </div>
        </div>

        <div class="page-content">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Audit Trail (Last 100 Actions)
                </div>
                <div class="card-body">
                    <?php if (empty($logs)): ?>
                        <div class="alert alert-info">No activity logs found.</div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>User / Actor</th>
                                        <th>Action Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td style="white-space: nowrap;">
                                                <small class="text-muted"><?php echo $log['created_at']; ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-info"><?php echo htmlspecialchars($log['user_id']); ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                    $action = htmlspecialchars($log['action']);
                                                    // Highlight certain keywords
                                                    $action = str_replace('attendance', '<strong class="text-primary">attendance</strong>', $action);
                                                    $action = str_replace('marks', '<strong class="text-success">marks</strong>', $action);
                                                    $action = str_replace('Leave', '<strong class="text-warning">Leave</strong>', $action);
                                                    echo $action; 
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card" style="margin-top: 20px;">
                <div class="card-body">
                    <p class="text-muted"><i class="fas fa-shield-alt"></i> These logs are read-only and serve as the official system audit trail for security compliance.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>