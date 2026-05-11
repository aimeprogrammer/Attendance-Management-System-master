<?php
// Database connection
$link = mysqli_connect('localhost', 'root', '', 'attsystem');

if (!$link) {
    die('Connection failed: ' . mysqli_connect_error());
}

echo "=== ADMIN CREDENTIALS ===\n";
echo "Username: oasis\n";
echo "Email: admin@system.local\n";
echo "Note: Check admin creation script or config file for password\n\n";

echo "=== EXISTING STUDENTS IN DATABASE ===\n";
$result = mysqli_query($link, "SELECT st_id, st_name, st_email, st_dept FROM students ORDER BY st_id LIMIT 10");
if ($result && mysqli_num_rows($result) > 0) {
    printf("%-15s | %-25s | %-30s | %-20s\n", "Student ID", "Name", "Email", "Department");
    echo str_repeat("-", 95) . "\n";
    while ($row = mysqli_fetch_assoc($result)) {
        printf("%-15s | %-25s | %-30s | %-20s\n", 
            $row['st_id'], 
            substr($row['st_name'], 0, 25), 
            $row['st_email'], 
            $row['st_dept']
        );
    }
} else {
    echo "No students found in database.\n";
}

echo "\n=== EXISTING TEACHERS IN DATABASE ===\n";
$result = mysqli_query($link, "SELECT tc_id, tc_name, tc_email, tc_dept FROM teachers ORDER BY tc_id LIMIT 10");
if ($result && mysqli_num_rows($result) > 0) {
    printf("%-15s | %-25s | %-30s | %-20s\n", "Teacher ID", "Name", "Email", "Department");
    echo str_repeat("-", 95) . "\n";
    while ($row = mysqli_fetch_assoc($result)) {
        printf("%-15s | %-25s | %-30s | %-20s\n", 
            $row['tc_id'], 
            substr($row['tc_name'], 0, 25), 
            $row['tc_email'], 
            $row['tc_dept']
        );
    }
} else {
    echo "No teachers found in database.\n";
}

echo "\n=== LOGIN INFORMATION ===\n";
echo "Students use: st_id (Student ID) + password\n";
echo "Teachers use: tc_id (Teacher ID) + password\n";
echo "Admin uses: 'oasis' + admin password\n";

echo "\n=== TO CREATE TEST CREDENTIALS ===\n";
echo "1. Go to signup.php to register new users\n";
echo "2. Or use admin/student_management.php to add students\n";
echo "3. Or run SQL INSERT statements directly\n";

mysqli_close($link);
?>
