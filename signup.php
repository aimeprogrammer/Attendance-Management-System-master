<?php

include('connect.php');

try{
    
    if(isset($_POST['signup'])){

        if(empty($_POST['email'])){
            throw new Exception("Email cannot be empty.");
        }

        if(empty($_POST['uname'])){
            throw new Exception("Username cannot be empty.");
        }

        if(empty($_POST['pass'])){
            throw new Exception("Password cannot be empty.");
        }
        
        if(empty($_POST['fname'])){
            throw new Exception("Full name cannot be empty.");
        }
        if(empty($_POST['phone'])){
            throw new Exception("Phone number cannot be empty.");
        }
        if(empty($_POST['type'])){
            throw new Exception("Role cannot be empty.");
        }

        // Check if username already exists
        $check_query = mysql_query("SELECT username FROM admininfo WHERE username='$_POST[uname]'");
        if(mysql_num_rows($check_query) > 0){
            throw new Exception("Username already exists. Please choose another.");
        }

        // Check if email already exists
        $check_email = mysql_query("SELECT email FROM admininfo WHERE email='$_POST[email]'");
        if(mysql_num_rows($check_email) > 0){
            throw new Exception("Email already registered. Please use another.");
        }

        // Hash the password for security
        $hashed_password = password_hash($_POST['pass'], PASSWORD_DEFAULT);

        $result = mysql_query("INSERT INTO admininfo(username, password, email, fname, phone, type) 
                               VALUES('$_POST[uname]', '$hashed_password', '$_POST[email]', '$_POST[fname]', '$_POST[phone]', '$_POST[type]')");
        
        if($result){
            $success_msg = "Signup Successfully! You can now login.";

            header("Location: index.php");
            exit();
        } else {
            throw new Exception("Registration failed. Please try again.");
        }
    }
}
catch(Exception $e){
    $error_msg = $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - Attendance Management System</title>
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" >
    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" >
    <!-- Latest compiled and minimal JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            margin: 0;
            padding: 20px;
        }

        .signup-container {
            width: 100%;
            max-width: 560px;
            padding: 20px;
        }

        .signup-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            padding: 40px;
        }

        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .signup-header .logo {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .signup-header h1 {
            font-size: 28px;
            color: var(--dark);
            margin: 0 0 8px 0;
            font-weight: 700;
        }

        .signup-header p {
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

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
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
            gap: 8px;
        }

        .role-option input[type="radio"]:checked + label {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .alert-message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger);
        }

        .signup-button {
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

        .signup-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(75, 119, 190, 0.4);
        }

        .signup-button:active {
            transform: translateY(0);
        }

        .signup-footer {
            margin-top: 25px;
            text-align: center;
        }

        .signup-footer p {
            font-size: 13px;
            color: var(--muted);
            margin: 8px 0;
        }

        .signup-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-footer a:hover {
            color: var(--primary-dark);
        }

        @media (max-width: 480px) {
            .signup-card {
                padding: 30px 20px;
            }

            .signup-header h1 {
                font-size: 24px;
            }

            .role-selector {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-card">
            <!-- Header -->
            <div class="signup-header">
                <div class="logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>AMS</h1>
                <p>Create your account</p>
            </div>

            <!-- Success Message -->
            <?php if(isset($success_msg) && $success_msg): ?>
                <div class="alert-message alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if(isset($error_msg) && $error_msg): ?>
                <div class="alert-message alert-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <!-- Signup Form -->
            <form method="post" action="">
                <!-- Email -->
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="your.email@example.com" 
                        required 
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    />
                </div>

                <!-- Username -->
                <div class="form-group">
                    <label for="uname">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input 
                        type="text" 
                        id="uname" 
                        name="uname" 
                        placeholder="Choose a username" 
                        required 
                        value="<?php echo isset($_POST['uname']) ? htmlspecialchars($_POST['uname']) : ''; ?>"
                    />
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="pass">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input 
                        type="password" 
                        id="pass" 
                        name="pass" 
                        placeholder="Choose a strong password" 
                        required 
                    />
                </div>

                <!-- Full Name -->
                <div class="form-group">
                    <label for="fname">
                        <i class="fas fa-id-card"></i> Full Name
                    </label>
                    <input 
                        type="text" 
                        id="fname" 
                        name="fname" 
                        placeholder="Your full name" 
                        required 
                        value="<?php echo isset($_POST['fname']) ? htmlspecialchars($_POST['fname']) : ''; ?>"
                    />
                </div>

                <!-- Phone Number -->
                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i> Phone Number
                    </label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        placeholder="Your phone number" 
                        required 
                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                    />
                </div>

                <!-- Role Selection -->
                <div class="form-group">
                    <label><i class="fas fa-user-tag"></i> Register as</label>
                    <div class="role-selector">
                        <div class="role-option">
                            <input type="radio" id="role_student" name="type" value="student" <?php echo (isset($_POST['type']) && $_POST['type'] == 'student') ? 'checked' : 'checked'; ?> />
                            <label for="role_student">
                                <i class="fas fa-graduation-cap"></i> Student
                            </label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="role_teacher" name="type" value="teacher" <?php echo (isset($_POST['type']) && $_POST['type'] == 'teacher') ? 'checked' : ''; ?> />
                            <label for="role_teacher">
                                <i class="fas fa-chalkboard-user"></i> Teacher
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Signup Button -->
                <button type="submit" class="signup-button" name="signup">
                    <i class="fas fa-save"></i> Sign Up
                </button>
            </form>

            <!-- Footer Links -->
            <div class="signup-footer">
                <p>
                    Already have an account?
                    <a href="index.php">
                        <i class="fas fa-sign-in-alt"></i> Login here
                    </a>
                </p>
                <p>
                    <a href="reset.php">
                        <i class="fas fa-key"></i> Forgot your password?
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>