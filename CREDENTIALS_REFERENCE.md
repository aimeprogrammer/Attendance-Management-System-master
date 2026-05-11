# 🔐 SYSTEM CREDENTIALS - Complete Reference

## ✅ CREDENTIALS CREATED AND ACTIVE

### 📋 ADMIN ACCOUNT
```
Role:         Administrator
Username:     oasis
Password:     admin123
Email:        admin@system.com
URL:          http://localhost/Attendance-Management-System-master/index.php
Access:       Full system admin dashboard
Permissions:  Create/edit/delete all records, view all reports
```

---

## 👥 STUDENT ACCOUNTS (5 Total)

| Student ID | Name | Email | Department | Password |
|-----------|------|-------|------------|----------|
| **STU001** | Amelia Johnson | amelia.johnson@student.com | Computer Science | **student123** |
| **STU002** | Benjamin Smith | benjamin.smith@student.com | Computer Science | **student123** |
| **STU003** | Catherine Brown | catherine.brown@student.com | Mechanical Eng | **student123** |
| **STU004** | David Wilson | david.wilson@student.com | Mechanical Eng | **student123** |
| **STU005** | Emma Davis | emma.davis@student.com | Civil Engineering | **student123** |

### 📌 How to Login as Student:
```
URL: http://localhost/Attendance-Management-System-master/index.php
Role: Student (select from dropdown)
Username: STU001 (or any STU00X)
Password: student123
```

### ✨ Student Dashboard Features:
- View personal attendance records
- Check class schedule
- View notifications
- Download attendance report
- Manage leave requests (coming soon)

---

## 👨‍🏫 TEACHER ACCOUNTS (4 Total)

| Teacher ID | Name | Email | Department | Course | Password |
|-----------|------|-------|------------|--------|----------|
| **TCH001** | Dr. Michael Anderson | michael.anderson@faculty.com | Computer Science | Data Structures | **teacher123** |
| **TCH002** | Prof. Sarah Taylor | sarah.taylor@faculty.com | Computer Science | Web Development | **teacher123** |
| **TCH003** | Dr. Robert Martinez | robert.martinez@faculty.com | Mechanical Eng | Thermodynamics | **teacher123** |
| **TCH004** | Prof. Jennifer White | jennifer.white@faculty.com | Civil Engineering | Structural Design | **teacher123** |

### 📌 How to Login as Teacher:
```
URL: http://localhost/Attendance-Management-System-master/index.php
Role: Teacher (select from dropdown)
Username: TCH001 (or any TCH00X)
Password: teacher123
```

### ✨ Teacher Dashboard Features:
- Mark student attendance
- View class attendance reports
- Track student progress
- Manage leave requests
- View student information
- Generate attendance statistics

---

## 🏃 QUICK LOGIN GUIDE

### Step 1: Go to Login Page
```
Open Browser: http://localhost/Attendance-Management-System-master/index.php
```

### Step 2: Select User Role
- 🏢 **Admin** - Full system control
- 👨‍🎓 **Student** - View personal records
- 👨‍🏫 **Teacher** - Mark attendance & manage classes

### Step 3: Enter Credentials
```
Admin:     Username: oasis     | Password: admin123
Student:   Username: STU001    | Password: student123
Teacher:   Username: TCH001    | Password: teacher123
```

### Step 4: Click Login
You'll be redirected to the respective dashboard

---

## 🎯 DASHBOARD URLS (After Login)

### Admin Dashboard
```
URL: /admin/index.php
Features: Charts, statistics, student management, announcements, payments
```

### Student Dashboard
```
URL: /student/index.php
Features: Attendance report, class list, notifications, account settings
```

### Teacher Dashboard
```
URL: /teacher/index.php
Features: Mark attendance, view reports, manage students, track progress
```

---

## 📊 TEST DATA SUMMARY

| User Type | Count | Password | Default Role |
|-----------|-------|----------|--------------|
| Admin | 1 | admin123 | Administrator |
| Students | 5 | student123 | Student |
| Teachers | 4 | teacher123 | Teacher |
| **Total** | **10** | - | - |

---

## 🔄 CHANGING PASSWORDS (Future Feature)

Currently all test accounts use default passwords. In production:

1. **For Admin**: Log in and change password in admin settings
2. **For Students**: Use account management page to change password
3. **For Teachers**: Use profile settings to change password

---

## 🚨 SECURITY NOTES

### ⚠️ Test Environment Only
- These are test credentials for development/demonstration
- **DO NOT** use in production with these passwords
- Change all passwords before deploying to production
- Use strong, unique passwords for each user

### 🔐 Password Security
- All passwords are hashed using `password_hash()` with `PASSWORD_DEFAULT` algorithm
- Passwords verified using `password_verify()` during login
- Never stored as plain text in database

### 🛡️ Access Control
- Role-based access control (RBAC) implemented
- Session verification on every protected page
- CSRF token protection on all forms
- SQL injection prevention with prepared statements

---

## 📝 DATABASE TABLES

### Admin Data
**Table**: `admininfo`
- username: oasis
- email: admin@system.com
- type: admin

### Students Data
**Table**: `students` (5 records)
- st_id: STU001 - STU005
- st_name: Student names
- st_dept: Department assignment
- st_batch: Batch/Class number
- st_email: Student email
- leave_balance: Default 15 days

### Teachers Data
**Table**: `teachers` (4 records)
- tc_id: TCH001 - TCH004
- tc_name: Teacher names
- tc_dept: Department
- tc_email: Faculty email
- tc_course: Assigned course

---

## ✅ WHAT YOU CAN DO WITH THESE CREDENTIALS

### As Admin (oasis)
- ✅ View comprehensive dashboard with charts
- ✅ Manage all student records (add, edit, delete)
- ✅ Manage programs and batches
- ✅ View all announcements
- ✅ Track all payments
- ✅ View system-wide reports
- ✅ Manage users and permissions

### As Student (STU001, etc.)
- ✅ View personal attendance records
- ✅ Check class details and schedules
- ✅ View grades and results
- ✅ Request leaves
- ✅ View announcements
- ✅ Download reports

### As Teacher (TCH001, etc.)
- ✅ Mark daily attendance
- ✅ Generate attendance reports
- ✅ View student information
- ✅ Approve/reject leave requests
- ✅ Track class attendance
- ✅ View student performance

---

## 🆘 TROUBLESHOOTING

### Login Not Working?
1. Ensure you're on the correct URL: `index.php`
2. Check if you selected the correct role (Admin/Student/Teacher)
3. Verify username and password are entered correctly (case-sensitive)
4. Check browser console (F12) for errors
5. Clear browser cookies and try again

### Dashboard Not Loading?
1. Verify database connection in `connect.php`
2. Check if MySQL is running
3. Confirm all database tables exist
4. Check PHP error logs in XAMPP

### Password Issues?
1. If you forget password, use reset.php
2. Contact system administrator for reset
3. For testing, recreate test credentials with `create_test_credentials.php`

---

## 📞 REFERENCE FILES

- **Test Credential Generator**: `create_test_credentials.php`
- **Credential Viewer**: `show_credentials.php`
- **Admin Documentation**: `ADMIN_DASHBOARD_DOCUMENTATION.md`
- **Testing Guide**: `ADMIN_TESTING_GUIDE.md`

---

## 🎓 NEXT STEPS

1. **Test Each Role**: Log in as admin, student, and teacher
2. **Explore Features**: Navigate through each dashboard
3. **Add More Data**: Use admin panel to add more students/teachers
4. **Test Operations**: Try CRUD operations on each page
5. **Generate Reports**: Create attendance reports and exports

---

**Status**: ✅ Credentials Active and Ready to Test

*Created: May 10, 2026*
*System Version: 2.0*
