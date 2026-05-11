<?php
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

// Prefill from REMEMBER_ME_CREDENTIALS.md (if present)
$remember_username = '';
$remember_password = '';
$remember_role = ''; // student|teacher|admin
$remember_file = __DIR__ . '/REMEMBER_ME_CREDENTIALS.md';
if (is_file($remember_file)) {
    $txt = file_get_contents($remember_file);
    if (is_string($txt)) {
        $m1 = [];
        if (preg_match('/Role:\s*(student|teacher|admin)\s*/i', $txt, $m1)) $remember_role = strtolower($m1[1]);
        $m2 = [];
        if (preg_match('/Username:\s*(.+)\s*/i', $txt, $m2)) $remember_username = trim($m2[1]);
        $m3 = [];
        if (preg_match('/Password:\s*(.*)\s*/i', $txt, $m3)) $remember_password = trim($m3[1]);
    }
}

if(isset($_POST['login']))
{
	//start of try block

	try{
		// CSRF Check
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
			throw new Exception("Security validation failed (CSRF).");
		}

		//checking empty fields
		if(empty($_POST['username'])){
			throw new Exception("Username is required!");
		}
		if(empty($_POST['password'])){
			throw new Exception("Password is required!");
		}

		//establishing connection with db and things
		include ('connect.php');
		
		//checking login info into database
		$row = 0;

		$role = $_POST['type']; // student|teacher|admin
		$username = $_POST['username'];
		$password = $_POST['password'];

		// Backward compatible: if a stored password is plain-text, allow direct compare.
		if ($role === 'admin') {
			$stmt = mysqli_prepare($link, "SELECT password FROM admininfo WHERE username=? AND type=? LIMIT 1");
			if ($stmt) {
				mysqli_stmt_bind_param($stmt, "ss", $username, $role);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_bind_result($stmt, $stored_password);

				if (mysqli_stmt_fetch($stmt)) {
					if (password_verify($password, $stored_password) || $password === $stored_password) {
						$row = 1;
					}
				}
				mysqli_stmt_close($stmt);
			}
		} else if ($role === 'student') {
			$stmt = mysqli_prepare($link, "SELECT password FROM students WHERE st_id=? LIMIT 1");
			if ($stmt) {
				mysqli_stmt_bind_param($stmt, "s", $username);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_bind_result($stmt, $stored_password);

				if (mysqli_stmt_fetch($stmt)) {
					if (password_verify($password, $stored_password) || $password === $stored_password) {
						$row = 1;
					}
				}
				mysqli_stmt_close($stmt);
			}
		} else if ($role === 'teacher') {
			$stmt = mysqli_prepare($link, "SELECT password FROM teachers WHERE tc_id=? LIMIT 1");
			if ($stmt) {
				mysqli_stmt_bind_param($stmt, "s", $username);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_bind_result($stmt, $stored_password);

				if (mysqli_stmt_fetch($stmt)) {
					if (password_verify($password, $stored_password) || $password === $stored_password) {
						$row = 1;
					}
				}
				mysqli_stmt_close($stmt);
			}
		}

		if ($row > 0 && $role === 'teacher') {
			$_SESSION['name'] = 'oasis';
			$_SESSION['role'] = 'teacher';
			$_SESSION['tc_id'] = $username;
			header('location: teacher/index.php');
			exit;
		} else if ($row > 0 && $role === 'student') {
			$_SESSION['name'] = 'oasis';
			$_SESSION['role'] = 'student';
			$_SESSION['st_id'] = $username;
			header('location: student/dashboard.php');
			exit;
		} else if ($row > 0 && $role === 'admin') {
			$_SESSION['name'] = 'oasis';
			$_SESSION['role'] = 'admin';
			header('location: admin/index.php');
			exit;
		} else {
			throw new Exception("Username,Password or Role is wrong, try again!");
		}
	}

	//end of try block
	catch(Exception $e){
		$error_msg=$e->getMessage();
	}
	//end of try-catch
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Login - Attendance Management System</title>
	<link rel="stylesheet" type="text/css" href="css/main.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" >
	<!-- Optional theme -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" >
	<!-- Latest compiled and minified JavaScript -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<style>

		body {
			display: flex;
			align-items: center;
			justify-content: center;
			min-height: 100vh;
			background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
		}

		.login-container {
			width: 100%;
			max-width: 520px;
			padding: 25px;
		}

		.login-card {
			background: white;
			border-radius: 12px;
			box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
			padding: 40px;
		}

		.login-header {
			text-align: center;
			margin-bottom: 30px;
		}

		.login-header .logo {
			font-size: 48px;
			margin-bottom: 15px;
			color: var(--primary);
		}

		.login-header h1 {
			font-size: 28px;
			color: var(--dark);
			margin: 0 0 8px 0;
			font-weight: 700;
		}

		.login-header p {
			color: var(--muted);
			font-size: 14px;
			margin: 0;
		}

		.form-group {
			margin-bottom: 20px;
		}

		.form-group label {
			display: block;
			margin-bottom: 8px;
			font-weight: 600;
			color: var(--dark);
			font-size: 14px;
		}

		.form-group input,
		.form-group select {
			width: 100%;
			padding: 12px 15px;
			border: 2px solid var(--border);
			border-radius: 8px;
			font-size: 14px;
			transition: all 0.3s ease;
		}

		.form-group input:focus,
		.form-group select:focus {
			outline: none;
			border-color: var(--primary);
			box-shadow: 0 0 0 4px rgba(75, 119, 190, 0.1);
		}

		.role-selector {
			display: flex;
			gap: 12px;
			margin-top: 12px;
		}

		.role-option {
			flex: 1;
		}

		.role-option input[type="radio"] {
			display: none;
		}

		.role-option label {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 12px;
			border: 2px solid var(--border);
			border-radius: 8px;
			background: white;
			cursor: pointer;
			transition: all 0.3s ease;
			margin-bottom: 0;
			font-weight: 500;
			gap: 6px;
		}

		.role-option input[type="radio"]:checked + label {
			background-color: var(--primary);
			color: white;
			border-color: var(--primary);
		}

		.error-message {
			background-color: #f8d7da;
			color: #721c24;
			padding: 12px 15px;
			border-radius: 8px;
			border-left: 4px solid var(--danger);
			margin-bottom: 20px;
			font-size: 14px;
		}

		.login-button {
			width: 100%;
			padding: 12px;
			background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
			color: white;
			border: none;
			border-radius: 8px;
			font-size: 16px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			margin-top: 10px;
		}

		.login-button:hover {
			transform: translateY(-2px);
			box-shadow: 0 6px 20px rgba(75, 119, 190, 0.4);
		}

		.login-button:active {
			transform: translateY(0);
		}

		.login-footer {
			margin-top: 25px;
			text-align: center;
		}

		.login-footer p {
			font-size: 13px;
			color: var(--muted);
			margin: 8px 0;
		}

		.login-footer a {
			color: var(--primary);
			text-decoration: none;
			font-weight: 600;
			transition: color 0.3s ease;
		}

		.login-footer a:hover {
			color: var(--primary-dark);
		}

		@media (max-width: 480px) {
			.login-card {
				padding: 30px 20px;
			}

			.login-header h1 {
				font-size: 24px;
			}

			.role-selector {
				flex-direction: column;
			}
		}
	</style>
</head>
<body>
	<div class="login-container">
		<div class="login-card">
			<!-- Header -->
			<div class="login-header">
				<div class="logo">
					<i class="fas fa-graduation-cap"></i>
				</div>
				<h1>AMS</h1>
				<p>Attendance Management System</p>
			</div>

			<!-- Error Message -->
			<?php if(isset($error_msg)): ?>
				<div class="error-message">
					<i class="fas fa-exclamation-circle"></i>
					<?php echo htmlspecialchars($error_msg); ?>
				</div>
			<?php endif; ?>

			<!-- Login Form -->
			<form method="post" action="">
				<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

				<!-- Username -->
				<div class="form-group">
					<label for="username">
						<i class="fas fa-user"></i> Username
					</label>
					<input 
						type="text" 
						id="username" 
						name="username" 
						placeholder="Enter your username" 
						required
						autocomplete="username"
						value="<?php echo htmlspecialchars($remember_username); ?>"
					/>
				</div>

				<!-- Password -->
				<div class="form-group">
					<label for="password">
						<i class="fas fa-lock"></i> Password
					</label>
					<input 
						type="password" 
						id="password" 
						name="password" 
						placeholder="Enter your password" 
						required
						autocomplete="current-password"
					/>
				</div>

				<!-- Role Selection -->
				<div class="form-group">
					<label><i class="fas fa-user-tag"></i> Login as</label>
					<div class="role-selector">
						<div class="role-option">
							<input type="radio" id="role_student" name="type" value="student" <?php echo ($remember_role === 'student') ? 'checked' : ''; ?> />
							<label for="role_student">
								<i class="fas fa-graduation-cap"></i> Student
							</label>
						</div>
						<div class="role-option">
							<input type="radio" id="role_teacher" name="type" value="teacher" <?php echo ($remember_role === 'teacher') ? 'checked' : ''; ?> />
							<label for="role_teacher">
								<i class="fas fa-chalkboard-user"></i> Teacher
							</label>
						</div>
						<div class="role-option">
							<input type="radio" id="role_admin" name="type" value="admin" <?php echo ($remember_role === 'admin') ? 'checked' : ''; ?> />
							<label for="role_admin">
								<i class="fas fa-cog"></i> Admin
							</label>
						</div>
					</div>
				</div>

				<!-- Login Button -->
				<button type="submit" name="login" class="login-button">
					<i class="fas fa-sign-in-alt"></i> Login
				</button>
			</form>

			<!-- Footer Links -->
			<div class="login-footer">
				<p>
					<a href="reset.php">
						<i class="fas fa-key"></i> Forgot your password?
					</a>
				</p>
				<p>
					Don't have an account?
					<a href="signup.php">
						<i class="fas fa-user-plus"></i> Sign up here
					</a>
				</p>
			</div>
		</div>
	</div>
</body>
</html>
