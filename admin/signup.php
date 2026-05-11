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

// establish connection
include('connect.php');

$error_msg = '';
$success_msg = '';

// Handle signup
try {
  if(isset($_POST['signup'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      throw new Exception("Security validation failed (CSRF).");
    }

    if(empty($_POST['email'])) throw new Exception("Email can't be empty.");
    if(empty($_POST['uname'])) throw new Exception("Username can't be empty.");
    if(empty($_POST['pass'])) throw new Exception("Password can't be empty.");
    if(empty($_POST['fname'])) throw new Exception("Full Name can't be empty.");
    if(empty($_POST['phone'])) throw new Exception("Phone Number can't be empty.");
    if(empty($_POST['type'])) throw new Exception("Role can't be empty.");

    $hashed_pass = password_hash($_POST['pass'], PASSWORD_BCRYPT);

    $stmt = mysqli_prepare(
      $link,
      "INSERT INTO admininfo (username, password, email, fname, phone, type) VALUES (?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
      throw new Exception("DB prepare failed: " . mysqli_error($link));
    }

    mysqli_stmt_bind_param(
      $stmt,
      "ssssss",
      $_POST['uname'],
      $hashed_pass,
      $_POST['email'],
      $_POST['fname'],
      $_POST['phone'],
      $_POST['type']
    );

    if (!mysqli_stmt_execute($stmt)) {
      throw new Exception("DB insert failed: " . mysqli_error($link));
    }

    mysqli_stmt_close($stmt);
    $success_msg = "✓ Signup Successfully!";
  }
} catch(Exception $e) {
  $error_msg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Signup - AMS</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
<div class="dashboard-container">
  <!-- Shared Sidebar Navigation -->
  <?php $activePage = 'signup.php'; include('sidebar.php'); ?>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-user-plus"></i> Create User</h1>
        <p style="color: var(--muted); margin: 0;">Add a new account for Student/Teacher/Admin</p>
      </div>
      <div class="user-menu">
        <span>👨‍💼 Admin</span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <?php if($success_msg): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?>
        </div>
      <?php endif; ?>

      <?php if($error_msg): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
        </div>
      <?php endif; ?>

      <div class="card" style="max-width: 820px; margin: 0 auto;">
        <div class="card-header"><i class="fas fa-user-plus"></i> Signup Details</div>

        <div class="card-body">
          <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="signup" value="1">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
              <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="your email" required>
              </div>

              <div class="form-group">
                <label for="uname">Username</label>
                <input type="text" name="uname" id="uname" class="form-control" placeholder="choose username" required>
              </div>

              <div class="form-group">
                <label for="pass">Password</label>
                <input type="password" name="pass" id="pass" class="form-control" placeholder="choose a strong password" required>
              </div>

              <div class="form-group">
                <label for="fname">Full Name</label>
                <input type="text" name="fname" id="fname" class="form-control" placeholder="your full name" required>
              </div>

              <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" name="phone" id="phone" class="form-control" placeholder="your phone number" required>
              </div>

              <div class="form-group">
                <label for="type">Role</label>
                <div class="status-toggle" style="border:none;">
                  <label style="flex:1; border:1px solid var(--border); border-radius:6px; padding:10px 12px; cursor:pointer;">
                    <input type="radio" name="type" value="student" checked style="margin-right:8px;"> Student
                  </label>
                  <label style="flex:1; border:1px solid var(--border); border-radius:6px; padding:10px 12px; cursor:pointer;">
                    <input type="radio" name="type" value="teacher" style="margin-right:8px;"> Teacher
                  </label>
                  <label style="flex:1; border:1px solid var(--border); border-radius:6px; padding:10px 12px; cursor:pointer;">
                    <input type="radio" name="type" value="admin" style="margin-right:8px;"> Admin
                  </label>
                </div>
              </div>
            </div>

            <div style="margin-top: 20px; display:flex; gap: 12px; justify-content:flex-end;">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Signup
              </button>
            </div>
          </form>

          <div style="margin-top: 18px; text-align:center;">
            <strong>Already have an account?</strong>
            <a href="../index.php">Login here.</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
