# Admin/Teacher/Student Dashboards Expansion — TODO

## 0) Baseline verification (current repo)
- [ ] Confirm login/session model for all roles (admin/teacher/student) works end-to-end
- [ ] Confirm existing navigation routes for each dashboard page (links match real files)
- [ ] Confirm DB connection is consistent (connect.php + role connect.php usage)
- [ ] Confirm existing attendance flow works (teacher marks → student report)

## 1) Data model alignment (Admin requirements)
Current schema is minimal (students/teachers/admininfo/courses/attendance/notifications/system_logs/etc).
- [ ] Audit current tables/columns vs required: programs/courses, fees, batches, subjects, exams, results, payments, user roles/permissions, SMS, backups
- [ ] Decide which features are implemented immediately vs staged
- [ ] Implement/extend MySQL schema (allowed per user confirmation):
  - [ ] Programs/Courses (+ fees: one-time + monthly)
  - [ ] Batches/Classes and assignments
  - [ ] Subjects + subject codes
  - [ ] Exam categories + exams scheduling (MCQ/Written split, mark allocation)
  - [ ] Results publishing/unpublishing
  - [ ] Payments + payment schedules + outstanding dues
  - [ ] Attendance analytics (reports/export)
  - [ ] User management (roles/permissions + activation flags + themes/preferences)
  - [ ] SMS: gateway settings + message history + pending queue (stub-safe)

## 2) Security hardening before adding more CRUD
- [ ] Remove/replace any remaining unsafe SQL usage (raw `$_POST` interpolation)
- [ ] Ensure CSRF is present on all write endpoints
- [ ] Ensure password hashing is consistent (bcrypt) and no plaintext persists
- [ ] Ensure output escaping is safe via `htmlspecialchars`
- [ ] Ensure IDOR protection (students can’t read other students)

## 3) Admin Dashboard features (end-to-end)
### 3.1 Student Management
- [ ] View all students with filters: name, email, status, program
- [ ] Add student with full details (personal, academic, contact)
- [ ] Edit student information
- [ ] Delete student record (soft delete if preferred)
- [ ] Search students by roll number/name/email
- [ ] View student admission history
- [ ] Bulk import students (CSV upload + validation + error reporting)
- [ ] Deactivate/activate student accounts
- [ ] Student account linking: student record ↔ user login account

### 3.2 Academic Management
- [ ] Create/manage programs/courses
- [ ] Set program fees (one-time + monthly)
- [ ] Create/manage batches/classes
- [ ] Assign students to batches
- [ ] Manage subjects and subject codes
- [ ] Create exams (MCQ/Written split)
- [ ] Set exam dates and mark allocation
- [ ] Manage mark categories
- [ ] View all results and statistics
- [ ] Generate academic reports

### 3.3 Examination & Results
- [ ] Create exam categories
- [ ] Schedule exams for programs
- [ ] Add exam marks + grading (MCQ + written)
- [ ] Result analytics: pass/fail rate, average scores
- [ ] Download result reports
- [ ] Publish/unpublish results
- [ ] SMS notifications for results (stubbed + stored history)

### 3.4 Payment & Finance
- [ ] View all student payments with status
- [ ] Record manual payments
- [ ] Set payment schedules per program/month/year
- [ ] View outstanding dues
- [ ] Track monthly/annual revenue
- [ ] Add income entries (donations/fees/etc.)
- [ ] Add expense entries (salaries/utilities/etc.)
- [ ] Categorize expenses
- [ ] Generate financial reports
- [ ] View payment history by student

### 3.5 Attendance Management
- [ ] View attendance records
- [ ] Generate attendance reports by student/batch/date
- [ ] Analyze attendance trends
- [ ] Export attendance data (CSV; later PDF)

### 3.6 User Management
- [ ] Create admin/teacher/staff accounts
- [ ] Assign roles and permissions
- [ ] Edit user profiles
- [ ] Change user passwords
- [ ] Activate/deactivate user accounts
- [ ] Set user themes/preferences
- [ ] View user login history

### 3.7 Communication
- [ ] Post notices/announcements
- [ ] Edit/delete notices
- [ ] Send SMS notifications (if gateway configured)
- [ ] Configure SMS gateway settings
- [ ] View SMS history and pending messages
- [ ] Manage chat/messaging system

### 3.8 System Settings & Audit
- [ ] Configure site name/logo/address/email/phone
- [ ] Manage themes and UI colors
- [ ] Set system preferences
- [ ] Configure SMS gateway credentials
- [ ] Backup database (admin-triggered)
- [ ] View system activity logs (user action audit)

### 3.9 Reports & Analytics
- [ ] Student performance dashboard
- [ ] Financial summary (income vs expenses)
- [ ] Attendance overview
- [ ] User activity logs
- [ ] Site activity with IP/browser/action details
- [ ] Export reports (PDF/CSV)

### 3.10 Dashboard Summary (Admin)
- [ ] Total students count
- [ ] Active programs count
- [ ] Today’s attendance summary
- [ ] Pending payments amount
- [ ] Recent exams scheduled
- [ ] System notifications
- [ ] Quick action buttons

## 4) Student Dashboard features (end-to-end)
- [ ] Personal profile view/edit + photo upload
- [ ] Admission history view
- [ ] Change password/contact info
- [ ] View enrolled programs/courses, batch schedule, subjects enrolled
- [ ] Course materials (if available; stub screen)
- [ ] Academic calendar
- [ ] Upcoming exams + download schedule
- [ ] Exam results + result history
- [ ] Marks breakdown and certificate download
- [ ] Attendance: record view, percent, download certificate/export
- [ ] Payments: fee structure, history, dues, due dates, receipts/invoices
- [ ] Notices/announcements: filtering + download
- [ ] Communications: messaging + announcements feed
- [ ] Dashboard summary: programs, next exam, attendance %, payment status, results, GPA/performance, latest announcements

## 5) Teacher Dashboard features (end-to-end)
- [ ] Class management: assigned batches/classes, roster download, schedule
- [ ] Attendance marking: present/absent + bulk marking + history + reports + SMS reminders
- [ ] Exam management: view assigned exams + schedule + details + paper downloads (stub)
- [ ] Results entry/grading: MCQ/written marks + auto total + submit + edit/recheck
- [ ] Student performance: individual/class trends + struggling students identification
- [ ] Class communication: notices, messages, bulk SMS
- [ ] Subject management for assigned subjects + subject-wise performance
- [ ] Profile & settings + password change + notification preferences + credentials download
- [ ] Reports: attendance, subject-wise, exam analysis, progress tracking + export
- [ ] Dashboard summary: assigned batches count, total supervised students, today attendance, upcoming exams, pending entries, announcements, quick actions

## 6) Implementation approach (to avoid missing anything)
- [ ] Implement schema foundation first (core tables + relations)
- [ ] Implement role routing + permission checks
- [ ] Migrate/upgrade existing attendance model to support programs/batches/subjects (without breaking current pages)
- [ ] Add CRUD screens incrementally:
  - [ ] Students → Programs → Batches → Subjects
  - [ ] Exams → Marks → Results publishing/analytics
  - [ ] Payments → Finance reports
  - [ ] Attendance analytics + exports
  - [ ] Notices/SMS/chat
  - [ ] System settings + logs + backups
- [ ] After each module, run smoke tests:
  - [ ] Open each dashboard page
  - [ ] Validate all filters/searches
  - [ ] Validate create/edit/delete flows
  - [ ] Validate exports work

## 7) Verification checklist (must pass before “done”)
- [ ] `tsc` not applicable (PHP), but run PHP pages smoke tests by opening key URLs
- [ ] Verify DB integrity after schema changes
- [ ] Verify no missing navigation links
- [ ] Verify exports (CSV) work for attendance/results/payments
- [ ] Verify SMS is stubbed safely and records history without crashing
- [ ] Ensure students/teachers cannot access other users’ data (IDOR checks)
