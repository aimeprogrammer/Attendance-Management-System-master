<?php
session_start();
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('location: ../index.php'); exit;
}
include('connect.php');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));

$success_msg = '';
if (isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $username = $_GET['delete'];
    if ($username !== 'oasis') {
        $stmt = mysqli_prepare($link, "DELETE FROM admininfo WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        if (mysqli_stmt_execute($stmt)) $success_msg = "User deleted successfully.";
        mysqli_stmt_close($stmt);
    }
}

$users = [];
$res = mysqli_query($link, "SELECT username, email, fname, type FROM admininfo ORDER BY type ASC");
while($row = mysqli_fetch_assoc($res)) $users[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - AMS</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php $activePage = 'users.php'; include('sidebar.php'); ?>
    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-user-shield"></i> System Accounts</h1>
            <a href="signup.php" class="btn btn-primary"><i class="fas fa-plus"></i> New User</a>
        </div>
        <div class="page-content">
            <?php if($success_msg) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
            <div class="card">
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr><th>Username</th><th>Name</th><th>Role</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($u['fname']); ?></td>
                                <td><span class="badge badge-info"><?php echo ucfirst($u['type']); ?></span></td>
                                <td>
                                    <?php if($u['username'] !== 'oasis'): ?>
                                        <a href="?delete=<?php echo urlencode($u['username']); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete user?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>