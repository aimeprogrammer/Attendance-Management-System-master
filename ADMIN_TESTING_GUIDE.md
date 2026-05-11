# 🚀 QUICK START GUIDE - Admin Dashboard Testing

## 🔑 Login Credentials
```
Username: oasis
Password: [Use the password set during admin creation]
```

## 📋 Testing Checklist

### 1. Admin Dashboard Features
- [ ] Navigate to `/admin/index.php`
- [ ] Verify login redirects non-admin users
- [ ] Check infographic cards display statistics
- [ ] Verify Bar Chart shows students/teachers by department
- [ ] Check Pie Chart displays attendance distribution
- [ ] Verify Line Graph shows 30-day attendance trends
- [ ] Check Scatter Plot shows batch attendance rates

### 2. Student Management (admin/student_management.php)
- [ ] Click "Add New Student" → fill form → submit
- [ ] Verify student appears in table
- [ ] Click "Edit" → modify fields → save
- [ ] Click "Delete" → confirm deletion
- [ ] Test search by ID, name, or email
- [ ] Test filter by department
- [ ] Verify CSRF protection (form should reject without token)

### 3. Programs Management (admin/programs.php)
- [ ] Click "Create New Program" → fill form
- [ ] Add multiple programs
- [ ] Edit existing program
- [ ] Delete program (if no batches assigned)
- [ ] Verify batch count displays correctly

### 4. Batches Management (admin/batches.php)
- [ ] Create new batch → select program
- [ ] Verify student count displays
- [ ] Edit batch name and years
- [ ] Delete batch
- [ ] Test duplicate batch ID detection

### 5. Subjects Management (admin/subjects.php)
- [ ] Add subject with program assignment
- [ ] Edit subject code and name
- [ ] Delete subject
- [ ] Verify program filter works

### 6. Announcements (admin/announcements.php)
- [ ] Create announcement with type and priority
- [ ] Set visibility (all/students/teachers/admin)
- [ ] Set expiry date (optional)
- [ ] Edit announcement
- [ ] Publish/unpublish (toggle is_active)
- [ ] Delete announcement
- [ ] Verify badges display correctly

### 7. Payments (admin/payments.php)
- [ ] Verify statistics cards show totals
- [ ] Record manual payment
- [ ] Select student from dropdown
- [ ] Select program from dropdown
- [ ] Choose payment method
- [ ] Verify payment appears in history table
- [ ] Test payment calculations

## 🔍 SQL Queries to Verify Data

### Check students
```sql
SELECT COUNT(*) FROM students;
SELECT * FROM students LIMIT 5;
```

### Check announcements
```sql
SELECT * FROM announcements ORDER BY published_date DESC;
```

### Check payments
```sql
SELECT * FROM student_payments ORDER BY payment_date DESC;
SELECT SUM(payment_amount) as total FROM student_payments WHERE status='completed';
```

### Check attendance trends
```sql
SELECT DATE(attendance_date) as date, COUNT(*) as count
FROM attendance
WHERE DATE(attendance_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(attendance_date)
ORDER BY date DESC;
```

## 🐛 Troubleshooting

### Issue: "Access Denied" redirect
**Solution**: Ensure you're logged in as admin and `$_SESSION['name']` equals 'oasis'

### Issue: Charts not displaying
**Solution**: 
1. Check browser console for errors
2. Verify MySQL tables exist (attendance, students, teachers, programs)
3. Ensure Chart.js CDN is accessible
4. Check database connection in connect.php

### Issue: CSRF token errors
**Solution**:
1. Ensure session is started (<?php session_start();)
2. Verify token is in hidden form field
3. Check $_POST['csrf_token'] matches $_SESSION['csrf_token']

### Issue: Dropdown showing no options
**Solution**: 
1. Ensure data exists in programs/batches tables
2. Check foreign key relationships
3. Verify SQL query syntax in page

### Issue: Table showing "No records found"
**Solution**:
1. Insert test data into database
2. Check SQL LIMIT clause
3. Verify search/filter parameters aren't too restrictive

## 📊 Sample Data Generation

### Insert test students
```sql
INSERT INTO students (st_id, st_name, st_dept, st_batch, st_semester, st_email) VALUES
('STU001', 'John Doe', 'Computer Science', 'B1', 1, 'john@example.com'),
('STU002', 'Jane Smith', 'Computer Science', 'B1', 1, 'jane@example.com'),
('STU003', 'Bob Johnson', 'Mechanical Eng', 'B2', 2, 'bob@example.com');
```

### Insert test programs
```sql
INSERT INTO programs (program_id, program_name) VALUES
('CS', 'Bachelor of Science - Computer Science'),
('ME', 'Bachelor of Science - Mechanical Engineering'),
('CE', 'Bachelor of Science - Civil Engineering');
```

### Insert test announcements
```sql
INSERT INTO announcements (title, content, announcement_type, priority, published_by, published_date) VALUES
('Welcome to AMS', 'Welcome to the new admin dashboard!', 'announcement', 'high', 'oasis', NOW()),
('Maintenance Notice', 'System will be down for maintenance on Sunday.', 'notice', 'medium', 'oasis', NOW());
```

## ✅ Final Validation

After testing all features:
- [ ] All CRUD operations work (Create, Read, Update, Delete)
- [ ] Charts display real data from database
- [ ] Sidebar navigation active states work
- [ ] Search/filter functionality works
- [ ] CSRF protection validates on forms
- [ ] Error messages display for invalid input
- [ ] Success messages display after operations
- [ ] Responsive design works on mobile (test with F12)
- [ ] No console JavaScript errors
- [ ] No SQL errors in PHP

## 🎉 Success!
If all items are checked, your admin dashboard is fully functional!

---

*For detailed documentation, see: ADMIN_DASHBOARD_DOCUMENTATION.md*
