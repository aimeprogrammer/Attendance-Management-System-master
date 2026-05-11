<?php
header('Location: dashboard.php');
exit;

session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

// Prefill from REMEMBER_ME_CREDENTIALS.md (if present)
$remember_username = '';
$remember_password = '';
$remember_role = ''; // student|teacher|admin
$remember_file = __DIR__ . '/../REMEMBER_ME_CREDENTIALS.md';
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
		// Backward compatible with old DB rows where passwords were stored in plain-text.
		$row = 0;
		$stmt = mysqli_prepare($link, "SELECT password FROM admininfo WHERE username=? AND type=? LIMIT 1");
		if ($stmt) {
			mysqli_stmt_bind_param($stmt, "ss", $_POST['username'], $_POST['type']);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_bind_result($stmt, $stored_password);
			
			if (mysqli_stmt_fetch($stmt)) {
				if (password_verify($_POST['password'], $stored_password) || $_POST['password'] === $stored_password) {
					$row = 1;
				}
			}
			mysqli_stmt_close($stmt);
		}


        if($row>0 && $_POST["type"] == 'teacher'){
			$_SESSION['name'] = 'oasis';
			$_SESSION['role'] = 'teacher';
			$_SESSION['tc_id'] = $_POST['username'];
			header('location: teacher/index.php');
			exit;
		}

		else if($row>0 &&  $_POST["type"] == 'student'){
			$_SESSION['name'] = 'oasis';
			$_SESSION['role'] = 'student';
			$_SESSION['st_id'] = $_POST['username'];
			header('location: dashboard.php');
			exit;
		}

		else if($row>0 && $_POST["type"] == 'admin'){
			$_SESSION['name'] = 'oasis';
			$_SESSION['role'] = 'admin';
			header('location: admin/index.php');
			exit;
		}

		else{
			throw new Exception("Username, Password or Role is wrong, try again!");
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
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		/* =============================================
		   CSS Variables
		   ============================================= */
		:root {
			--primary: #4b77be;
			--primary-dark: #3a63a3;
			--primary-light: #6b93d6;
			--secondary: #f39c12;
			--success: #27ae60;
			--danger: #e74c3c;
			--warning: #f39c12;
			--info: #3498db;
			--light: #ecf0f1;
			--dark: #2c3e50;
			--muted: #7f8c8d;
			--border: #dcdde1;
			--white: #ffffff;
			--bg-light: #f8f9fa;
			--shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
			--shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
			--shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.2);
			--shadow-xl: 0 20px 60px rgba(0, 0, 0, 0.3);
			--radius-sm: 6px;
			--radius: 10px;
			--radius-lg: 16px;
			--radius-xl: 20px;
			--transition-fast: 0.2s ease;
			--transition: 0.3s ease;
			--transition-slow: 0.5s ease;
		}

		/* =============================================
		   Reset & Base Styles
		   ============================================= */
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
			position: relative;
			overflow-x: hidden;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
		}

		/* =============================================
		   Animated Background Particles
		   ============================================= */
		body::before {
			content: '';
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: 
				radial-gradient(circle at 20% 50%, rgba(75, 119, 190, 0.15) 0%, transparent 50%),
				radial-gradient(circle at 80% 20%, rgba(52, 152, 219, 0.1) 0%, transparent 50%),
				radial-gradient(circle at 40% 80%, rgba(39, 174, 96, 0.1) 0%, transparent 50%),
				radial-gradient(circle at 70% 60%, rgba(243, 156, 18, 0.08) 0%, transparent 50%);
			animation: bgPulse 8s ease-in-out infinite alternate;
			z-index: 0;
		}

		@keyframes bgPulse {
			0% {
				opacity: 0.5;
				transform: scale(1);
			}
			100% {
				opacity: 1;
				transform: scale(1.1);
			}
		}

		/* Floating particles */
		.particles {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			pointer-events: none;
			z-index: 0;
		}

		.particle {
			position: absolute;
			background: rgba(255, 255, 255, 0.1);
			border-radius: 50%;
			animation: float 6s ease-in-out infinite;
		}

		.particle:nth-child(1) { width: 4px; height: 4px; left: 10%; top: 20%; animation-delay: 0s; }
		.particle:nth-child(2) { width: 6px; height: 6px; left: 25%; top: 60%; animation-delay: 1s; }
		.particle:nth-child(3) { width: 3px; height: 3px; left: 50%; top: 30%; animation-delay: 2s; }
		.particle:nth-child(4) { width: 5px; height: 5px; left: 70%; top: 70%; animation-delay: 3s; }
		.particle:nth-child(5) { width: 4px; height: 4px; left: 85%; top: 40%; animation-delay: 4s; }
		.particle:nth-child(6) { width: 6px; height: 6px; left: 40%; top: 10%; animation-delay: 5s; }
		.particle:nth-child(7) { width: 3px; height: 3px; left: 60%; top: 80%; animation-delay: 2.5s; }
		.particle:nth-child(8) { width: 5px; height: 5px; left: 15%; top: 45%; animation-delay: 3.5s; }

		@keyframes float {
			0%, 100% {
				transform: translateY(0) translateX(0) scale(1);
				opacity: 0.3;
			}
			25% {
				transform: translateY(-20px) translateX(10px) scale(1.5);
				opacity: 0.6;
			}
			50% {
				transform: translateY(-10px) translateX(-10px) scale(1);
				opacity: 0.3;
			}
			75% {
				transform: translateY(-30px) translateX(15px) scale(1.8);
				opacity: 0.7;
			}
		}

		/* =============================================
		   Login Container
		   ============================================= */
		.login-container {
			width: 100%;
			max-width: 560px;
			padding: 20px;
			position: relative;
			z-index: 1;
			animation: slideUp 0.6s ease-out;
		}

		@keyframes slideUp {
			from {
				opacity: 0;
				transform: translateY(30px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		/* =============================================
		   Login Card
		   ============================================= */
		.login-card {
			background: rgba(255, 255, 255, 0.98);
			backdrop-filter: blur(20px);
			border-radius: var(--radius-xl);
			box-shadow: var(--shadow-xl);
			padding: 45px 40px;
			border: 1px solid rgba(255, 255, 255, 0.2);
			position: relative;
			overflow: hidden;
			min-width: 420px;
		}

		.login-card::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 4px;
			background: linear-gradient(90deg, var(--primary), var(--info), var(--success), var(--warning));
			background-size: 300% 100%;
			animation: gradientBar 3s ease infinite;
		}

		@keyframes gradientBar {
			0% { background-position: 0% 50%; }
			50% { background-position: 100% 50%; }
			100% { background-position: 0% 50%; }
		}

		/* =============================================
		   Login Header
		   ============================================= */
		.login-header {
			text-align: center;
			margin-bottom: 35px;
		}

		.login-header .logo-icon {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 80px;
			height: 80px;
			background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
			border-radius: 50%;
			margin-bottom: 20px;
			box-shadow: 0 10px 30px rgba(75, 119, 190, 0.3);
			animation: logoFloat 3s ease-in-out infinite;
		}

		@keyframes logoFloat {
			0%, 100% { transform: translateY(0); }
			50% { transform: translateY(-8px); }
		}

		.login-header .logo-icon i {
			font-size: 36px;
			color: white;
		}

		.login-header h1 {
			font-size: 32px;
			color: var(--dark);
			margin: 0 0 8px 0;
			font-weight: 700;
			letter-spacing: -0.5px;
		}

		.login-header .subtitle {
			color: var(--muted);
			font-size: 14px;
			margin: 0;
			font-weight: 500;
		}

		.login-header .version-badge {
			display: inline-block;
			margin-top: 10px;
			padding: 4px 12px;
			background: var(--bg-light);
			border-radius: 20px;
			font-size: 11px;
			color: var(--muted);
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 1px;
		}

		/* =============================================
		   Form Styles
		   ============================================= */
		.form-group {
			margin-bottom: 22px;
		}

		.form-group label {
			display: flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 8px;
			font-weight: 600;
			color: var(--dark);
			font-size: 13px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.form-group label i {
			color: var(--primary);
			font-size: 14px;
			width: 16px;
			text-align: center;
		}

		.input-wrapper {
			position: relative;
		}

		.input-wrapper .input-icon {
			position: absolute;
			left: 14px;
			top: 50%;
			transform: translateY(-50%);
			color: var(--muted);
			font-size: 16px;
			transition: color var(--transition-fast);
			pointer-events: none;
			z-index: 1;
		}

		.input-wrapper input {
			width: 100%;
			padding: 13px 15px 13px 42px;
			border: 2px solid var(--border);
			border-radius: var(--radius);
			font-size: 14px;
			transition: all var(--transition);
			background: var(--white);
			color: var(--dark);
			font-family: inherit;
		}

		.input-wrapper input:focus {
			outline: none;
			border-color: var(--primary);
			box-shadow: 0 0 0 4px rgba(75, 119, 190, 0.1);
			background: #fafbff;
		}

		.input-wrapper input:focus + .input-icon,
		.input-wrapper input:focus ~ .input-icon {
			color: var(--primary);
		}

		.input-wrapper input::placeholder {
			color: #b0b8c1;
			font-size: 13px;
		}

		/* Password toggle */
		.password-toggle {
			position: absolute;
			right: 14px;
			top: 50%;
			transform: translateY(-50%);
			cursor: pointer;
			color: var(--muted);
			font-size: 16px;
			transition: color var(--transition-fast);
			z-index: 1;
			background: none;
			border: none;
			padding: 5px;
		}

		.password-toggle:hover {
			color: var(--primary);
		}

		/* =============================================
		   Role Selector
		   ============================================= */
		.role-selector {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 10px;
			margin-top: 8px;
		}

		.role-option {
			position: relative;
		}

		.role-option input[type="radio"] {
			display: none;
		}

		.role-option label {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 14px 10px;
			border: 2px solid var(--border);
			border-radius: var(--radius);
			background: white;
			cursor: pointer;
			transition: all var(--transition);
			margin-bottom: 0;
			text-transform: none;
			letter-spacing: normal;
			gap: 6px;
			font-weight: 500;
			font-size: 12px;
			text-align: center;
		}

		.role-option label i {
			font-size: 22px;
			transition: color var(--transition);
			color: var(--muted);
		}

		.role-option label:hover {
			border-color: var(--primary-light);
			background: #f8faff;
		}

		.role-option label:hover i {
			color: var(--primary);
		}

		.role-option input[type="radio"]:checked + label {
			background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
			color: white;
			border-color: var(--primary);
			box-shadow: 0 4px 15px rgba(75, 119, 190, 0.3);
			transform: translateY(-2px);
		}

		.role-option input[type="radio"]:checked + label i {
			color: white;
		}

		/* =============================================
		   Error Message
		   ============================================= */
		.error-message {
			background: #fff5f5;
			color: #c53030;
			padding: 14px 18px;
			border-radius: var(--radius);
			border-left: 4px solid var(--danger);
			margin-bottom: 25px;
			font-size: 13px;
			display: flex;
			align-items: center;
			gap: 10px;
			animation: shakeAlert 0.5s ease;
			font-weight: 500;
		}

		.error-message i {
			font-size: 18px;
			flex-shrink: 0;
			color: var(--danger);
		}

		@keyframes shakeAlert {
			0%, 100% { transform: translateX(0); }
			10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
			20%, 40%, 60%, 80% { transform: translateX(5px); }
		}

		/* =============================================
		   Success Message
		   ============================================= */
		.success-message {
			background: #f0fff4;
			color: #276749;
			padding: 14px 18px;
			border-radius: var(--radius);
			border-left: 4px solid var(--success);
			margin-bottom: 25px;
			font-size: 13px;
			display: flex;
			align-items: center;
			gap: 10px;
			font-weight: 500;
		}

		.success-message i {
			font-size: 18px;
			flex-shrink: 0;
			color: var(--success);
		}

		/* =============================================
		   Login Button
		   ============================================= */
		.login-button {
			width: 100%;
			padding: 14px;
			background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
			color: white;
			border: none;
			border-radius: var(--radius);
			font-size: 16px;
			font-weight: 600;
			cursor: pointer;
			transition: all var(--transition);
			margin-top: 8px;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 10px;
			font-family: inherit;
			letter-spacing: 0.3px;
			position: relative;
			overflow: hidden;
		}

		.login-button::before {
			content: '';
			position: absolute;
			top: 0;
			left: -100%;
			width: 100%;
			height: 100%;
			background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
			transition: left 0.5s ease;
		}

		.login-button:hover::before {
			left: 100%;
		}

		.login-button:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 25px rgba(75, 119, 190, 0.4);
		}

		.login-button:active {
			transform: translateY(0);
			box-shadow: 0 4px 15px rgba(75, 119, 190, 0.3);
		}

		.login-button i {
			font-size: 18px;
		}

		/* =============================================
		   Footer Links
		   ============================================= */
		.login-footer {
			margin-top: 30px;
			text-align: center;
			padding-top: 20px;
			border-top: 1px solid var(--border);
		}

		.login-footer p {
			font-size: 13px;
			color: var(--muted);
			margin: 10px 0;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 5px;
		}

		.login-footer a {
			color: var(--primary);
			text-decoration: none;
			font-weight: 600;
			transition: all var(--transition-fast);
			padding: 2px 4px;
			border-radius: 3px;
		}

		.login-footer a:hover {
			color: var(--primary-dark);
			background: rgba(75, 119, 190, 0.05);
			text-decoration: none;
		}

		.login-footer .divider {
			display: inline-block;
			width: 4px;
			height: 4px;
			background: var(--border);
			border-radius: 50%;
			margin: 0 8px;
		}

		/* =============================================
		   Remember Me Checkbox
		   ============================================= */
		.remember-me {
			display: flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 20px;
			font-size: 13px;
			color: var(--muted);
		}

		.remember-me input[type="checkbox"] {
			width: 16px;
			height: 16px;
			accent-color: var(--primary);
			cursor: pointer;
		}

		.remember-me label {
			cursor: pointer;
			user-select: none;
		}

		/* =============================================
		   Loading State
		   ============================================= */
		.login-button.loading {
			pointer-events: none;
			opacity: 0.8;
		}

		.login-button .spinner {
			display: none;
			width: 20px;
			height: 20px;
			border: 2px solid rgba(255, 255, 255, 0.3);
			border-top-color: white;
			border-radius: 50%;
			animation: spin 0.6s linear infinite;
		}

		.login-button.loading .spinner {
			display: inline-block;
		}

		.login-button.loading .btn-text {
			display: none;
		}

		@keyframes spin {
			to { transform: rotate(360deg); }
		}

		/* =============================================
		   Responsive Design
		   ============================================= */
		@media (max-width: 480px) {
			.login-container {
				padding: 15px;
			}

			.login-card {
				padding: 35px 25px;
				border-radius: var(--radius-lg);
			}

			.login-header h1 {
				font-size: 26px;
			}

			.login-header .logo-icon {
				width: 65px;
				height: 65px;
			}

			.login-header .logo-icon i {
				font-size: 28px;
			}

			.role-selector {
				grid-template-columns: 1fr;
				gap: 8px;
			}

			.role-option label {
				flex-direction: row;
				padding: 12px 15px;
				font-size: 13px;
			}

			.role-option label i {
				font-size: 18px;
			}

			.input-wrapper input {
				padding: 12px 15px 12px 40px;
				font-size: 13px;
			}
		}

		@media (max-width: 360px) {
			.login-card {
				padding: 25px 18px;
			}

			.login-header {
				margin-bottom: 25px;
			}
		}
	</style>
</head>
<body>
	<!-- Animated Background Particles -->
	<div class="particles">
		<div class="particle"></div>
		<div class="particle"></div>
		<div class="particle"></div>
		<div class="particle"></div>
		<div class="particle"></div>
		<div class="particle"></div>
		<div class="particle"></div>
		<div class="particle"></div>
	</div>

	<div class="login-container">
		<div class="login-card">
			<!-- Header -->
			<div class="login-header">
				<div class="logo-icon">
					<i class="fas fa-graduation-cap"></i>
				</div>
				<h1>Attendance MS</h1>
				<p class="subtitle">Sign in to your account</p>
				<span class="version-badge">v1.0</span>
			</div>

			<!-- Error Message -->
			<?php if(isset($error_msg)): ?>
				<div class="error-message">
					<i class="fas fa-exclamation-circle"></i>
					<?php echo htmlspecialchars($error_msg); ?>
				</div>
			<?php endif; ?>

			<!-- Login Form -->
			<form method="post" action="" id="loginForm">
				<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

				<!-- Username -->
				<div class="form-group">
					<label for="username">
						<i class="fas fa-user"></i> Username
					</label>
					<div class="input-wrapper">
						<i class="fas fa-user input-icon"></i>
						<input 
							type="text" 
							id="username" 
							name="username" 
							placeholder="Enter your username" 
							required
							autocomplete="username"
							autofocus
							value="<?php echo htmlspecialchars($remember_username); ?>"
						/>
					</div>
				</div>

				<!-- Password -->
				<div class="form-group">
					<label for="password">
						<i class="fas fa-lock"></i> Password
					</label>
					<div class="input-wrapper">
						<i class="fas fa-lock input-icon"></i>
						<input 
							type="password" 
							id="password" 
							name="password" 
							placeholder="Enter your password" 
							required
							autocomplete="current-password"
							value="<?php echo htmlspecialchars($remember_password); ?>"
						/>
						<button type="button" class="password-toggle" onclick="togglePassword()" title="Show/Hide password">
							<i class="fas fa-eye" id="toggleIcon"></i>
						</button>
					</div>
				</div>

				<!-- Role Selection -->
				<div class="form-group">
					<label><i class="fas fa-user-tag"></i> Login as</label>
					<div class="role-selector">
						<div class="role-option">
							<input type="radio" id="role_student" name="type" value="student" <?php echo ($remember_role === 'student') ? 'checked' : ''; ?> />
							<label for="role_student">
								<i class="fas fa-user-graduate"></i>
								<span>Student</span>
							</label>
						</div>
						<div class="role-option">
							<input type="radio" id="role_teacher" name="type" value="teacher" <?php echo ($remember_role === 'teacher') ? 'checked' : ''; ?> />
							<label for="role_teacher">
								<i class="fas fa-chalkboard-teacher"></i>
								<span>Teacher</span>
							</label>
						</div>
						<div class="role-option">
							<input type="radio" id="role_admin" name="type" value="admin" <?php echo ($remember_role === 'admin') ? 'checked' : ''; ?> />
							<label for="role_admin">
								<i class="fas fa-user-shield"></i>
								<span>Admin</span>
							</label>
						</div>
					</div>
				</div>

				<!-- Remember Me -->
				<div class="remember-me">
					<input type="checkbox" id="remember" name="remember" />
					<label for="remember">Remember me on this device</label>
				</div>

				<!-- Login Button -->
				<button type="submit" name="login" class="login-button" id="loginBtn">
					<span class="btn-text"><i class="fas fa-sign-in-alt"></i> Sign In</span>
					<span class="spinner"></span>
				</button>
			</form>

			<!-- Footer Links -->
			<div class="login-footer">
				<p>
					<i class="fas fa-key"></i>
					<a href="reset.php">Forgot your password?</a>
				</p>
				<p>
					<span>Don't have an account?</span>
					<a href="signup.php">
						<i class="fas fa-user-plus"></i> Create one here
					</a>
				</p>
			</div>
		</div>
	</div>

	<script>
		// Toggle password visibility
		function togglePassword() {
			const passwordInput = document.getElementById('password');
			const toggleIcon = document.getElementById('toggleIcon');
			
			if (passwordInput.type === 'password') {
				passwordInput.type = 'text';
				toggleIcon.classList.remove('fa-eye');
				toggleIcon.classList.add('fa-eye-slash');
			} else {
				passwordInput.type = 'password';
				toggleIcon.classList.remove('fa-eye-slash');
				toggleIcon.classList.add('fa-eye');
			}
		}

		// Loading state on form submit
		document.getElementById('loginForm').addEventListener('submit', function(e) {
			const btn = document.getElementById('loginBtn');
			btn.classList.add('loading');
			
			// Remove loading after 3 seconds if form doesn't redirect
			setTimeout(function() {
				btn.classList.remove('loading');
			}, 3000);
		});

		// Auto-hide error message after 5 seconds
		document.addEventListener('DOMContentLoaded', function() {
			const errorMsg = document.querySelector('.error-message');
			if (errorMsg) {
				setTimeout(function() {
					errorMsg.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
					errorMsg.style.opacity = '0';
					errorMsg.style.transform = 'translateY(-10px)';
					setTimeout(function() {
						if (errorMsg.parentNode) {
							errorMsg.remove();
						}
					}, 500);
				}, 5000);
			}
		});

		// Keyboard shortcut: Enter to submit
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Enter' && document.activeElement.tagName !== 'BUTTON') {
				const form = document.getElementById('loginForm');
				if (form) {
					e.preventDefault();
					form.dispatchEvent(new Event('submit'));
				}
			}
		});
	</script>
</body>
</html>
