<?php
// Create test credentials for Admin, Student, and Teacher

$link = mysqli_connect('localhost', 'root', '', 'attsystem');

if (!$link) {
    die('Connection failed: ' . mysqli_connect_error());
}

echo "=== CREATING TEST CREDENTIALS ===\n\n";

function columnExists(mysqli $link, string $table, string $column): bool {
    $tableEsc = mysqli_real_escape_string($link, $table);
    $colEsc = mysqli_real_escape_string($link, $column);
    $sql = "SELECT COUNT(*) AS cnt
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$tableEsc}'
              AND COLUMN_NAME = '{$colEsc}'";
    $row = mysqli_fetch_assoc(mysqli_query($link, $sql));
    return (int)($row['cnt'] ?? 0) > 0;
}

// 1. CREATE ADMIN CREDENTIALS
echo "1️⃣  ADMIN ACCOUNT\n";
echo "   Username: oasis\n";
echo "   Password: admin123\n";
echo "   Email: admin@system.com\n";

$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admininfo_insert = "INSERT IGNORE INTO admininfo (username, password, email, fname, phone, type) 
                     VALUES ('oasis', '$admin_password', 'admin@system.com', 'System Admin', '1234567890', 'admin')";
if (mysqli_query($link, $admininfo_insert)) {
    echo "   ✅ Admin created in database\n\n";
} else {
    echo "   ⚠️ Admin already exists or error: " . mysqli_error($link) . "\n\n";
}

// Also ensure admin password is correct even if INSERT IGNORE skipped
$admin_update = "UPDATE admininfo SET password='$admin_password', type='admin' WHERE username='oasis'";
mysqli_query($link, $admin_update);

// 2. CREATE STUDENT CREDENTIALS
echo "2️⃣  STUDENT ACCOUNTS\n";

$students = [
    ['STU001', 'Amelia Johnson', 'Computer Science', 1, 1, 'amelia.johnson@student.com'],
    ['STU002', 'Benjamin Smith', 'Computer Science', 1, 1, 'benjamin.smith@student.com'],
    ['STU003', 'Catherine Brown', 'Mechanical Eng', 2, 2, 'catherine.brown@student.com'],
    ['STU004', 'David Wilson', 'Mechanical Eng', 2, 2, 'david.wilson@student.com'],
    ['STU005', 'Emma Davis', 'Civil Engineering', 3, 3, 'emma.davis@student.com'],
];

// Use default password for all test students (only if students.password exists)
$hasStudentPassword = columnExists($link, 'students', 'password');
if (!$hasStudentPassword) {
    echo "   ⚠️ Skipping student seeding: `students.password` column not found.\n\n";
} else {
    $default_student_password = password_hash('student123', PASSWORD_DEFAULT);

    foreach ($students as $student) {
        $st_id = $student[0];
        $st_name = $student[1];
        $st_dept = $student[2];
        $st_batch = (int)$student[3];
        $st_sem = (int)$student[4];
        $st_email = $student[5];

        $insert = "INSERT IGNORE INTO students (st_id, st_name, st_dept, st_batch, st_sem, st_email, password)
                   VALUES ('$st_id', '$st_name', '$st_dept', $st_batch, $st_sem, '$st_email', '$default_student_password')";
        mysqli_query($link, $insert);

        // Always ensure password is set (INSERT IGNORE won't update existing rows)
        $update = "UPDATE students SET password='$default_student_password'
                   WHERE st_id='$st_id'";
        mysqli_query($link, $update);

        echo "   ✅ {$st_id} - {$st_name} (Password: student123)\n";
    }
    echo "\n";
}

// 3. CREATE TEACHER CREDENTIALS  
echo "3️⃣  TEACHER ACCOUNTS\n";

$teachers = [
    ['TCH001', 'Dr. Michael Anderson', 'Computer Science', 'michael.anderson@faculty.com', 'Data Structures'],
    ['TCH002', 'Prof. Sarah Taylor', 'Computer Science', 'sarah.taylor@faculty.com', 'Web Development'],
    ['TCH003', 'Dr. Robert Martinez', 'Mechanical Eng', 'robert.martinez@faculty.com', 'Thermodynamics'],
    ['TCH004', 'Prof. Jennifer White', 'Civil Engineering', 'jennifer.white@faculty.com', 'Structural Design'],
];

$hasTeacherPassword = columnExists($link, 'teachers', 'password');
if (!$hasTeacherPassword) {
    echo "   ⚠️ Skipping teacher seeding: `teachers.password` column not found.\n\n";
} else {
    $default_teacher_password = password_hash('teacher123', PASSWORD_DEFAULT);

    foreach ($teachers as $teacher) {
        $tc_id = $teacher[0];
        $tc_name = $teacher[1];
        $tc_dept = $teacher[2];
        $tc_email = $teacher[3];
        $tc_course = $teacher[4];

        $insert = "INSERT IGNORE INTO teachers (tc_id, tc_name, tc_dept, tc_email, tc_course, password)
                   VALUES ('$tc_id', '$tc_name', '$tc_dept', '$tc_email', '$tc_course', '$default_teacher_password')";
        mysqli_query($link, $insert);

        // Always ensure password is set (INSERT IGNORE won't update existing rows)
        $update = "UPDATE teachers SET password='$default_teacher_password'
                   WHERE tc_id='$tc_id'";
        mysqli_query($link, $update);

        echo "   ✅ {$tc_id} - {$tc_name} (Password: teacher123)\n";
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "🔐 TEST CREDENTIALS SUMMARY\n";
echo str_repeat("=", 60) . "\n\n";

echo "📋 ADMIN LOGIN\n";
echo "   URL: /index.php\n";
echo "   Username: oasis\n";
echo "   Password: admin123\n";
echo "   Role: Admin\n\n";

echo "👥 STUDENT LOGIN EXAMPLES\n";
echo "   URL: /index.php\n";
echo "   Username: STU001\n";
echo "   Password: student123\n";
echo "   Role: Student\n";
echo "   (All students use same password: student123)\n\n";

echo "👨‍🏫 TEACHER LOGIN EXAMPLES\n";
echo "   URL: /index.php\n";
echo "   Username: TCH001\n";
echo "   Password: teacher123\n";
echo "   Role: Teacher\n";
echo "   (All teachers use same password: teacher123)\n\n";

// Verify data
echo "📊 VERIFICATION\n";
echo "   Students in DB: " . mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM students"))['count'] . "\n";
echo "   Teachers in DB: " . mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM teachers"))['count'] . "\n";
echo "   Admin accounts: " . mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM admininfo"))['count'] . "\n";

echo "\n✅ TEST CREDENTIALS CREATED SUCCESSFULLY!\n";

mysqli_close($link);
?>
