# TODO: Admin/Student/Teacher Dashboards (Full Feature Expansion)

## Scope (from TODO_DASHBOARDS_EXPANSION.md + user task)
This project must provide end-to-end features for:
- Admin: Student Management, Academic Management, Exams/Results, Payment/Finance, Attendance, User Management, Communication, System Settings, Reports/Analytics, Dashboard Summary widgets.
- Student: Personal Profile, Academic Info, Exam/Results, Attendance, Payments/Fees, Notices, Communication, Dashboard Summary.
- Teacher: Class Management, Attendance Marking + Reports/SMS, Exam Management, Results Entry/Grading, Student Performance, Class Communication, Subject Management, Reports.

## Baseline status (checked in current repo)
- Minimal pages exist under `admin/`, `teacher/`, `student/`.
- DB schema exists for advanced academic/exam/payment/sms/system_settings/etc in `database/attsystem.sql`.
- Several pages still contain legacy `mysql_*` usage; must ensure compatibility with PHP 8.1.

## Implementation steps (non-skippable)
1. Verify schema tables exist for required features.
   - Run a table check script (`check_tables.php`) and compare with expected schema.
2. Fix PHP runtime compatibility.
   - Replace or fully wrap `mysql_*` usage to ensure PHP 8.1 compatibility on all endpoints we touch.
3. Create shared UI/layout + sidebar navigation partials.
   - Keep existing style but avoid duplicating menu markup across new pages.
4. Implement Admin pages (end-to-end):
   4.1 Student Management
   4.2 Academic Management
   4.3 Exams & Results (including publish/unpublish)
   4.4 Payment & Finance
   4.5 Attendance Management (reports + CSV export)
   4.6 User Management (accounts, activation, roles)
   4.7 Communication (notices + SMS gateway integration stubs + chat placeholder)
   4.8 System Settings (site prefs, SMS creds, backups, audit logs)
   4.9 Reports & Analytics (exports)
5. Implement Student pages (end-to-end).
6. Implement Teacher pages (end-to-end).
7. Security hardening (must be applied to all write endpoints):
   - CSRF validation
   - Output escaping
   - IDOR protection
8. Smoke test plan.
   - For each role: open navigation pages; verify forms; export CSV; publish results; SMS history pages work (even if SMS provider disabled).

## Progress tracking
- [ ] Step 1: Schema verification
- [ ] Step 2: PHP 8.1 compatibility audit for legacy mysql_* endpoints
- [ ] Step 3: Shared layout/partials
- [ ] Step 4: Admin module set
- [ ] Step 5: Student module set
- [ ] Step 6: Teacher module set
- [ ] Step 7: Security hardening
- [ ] Step 8: Smoke tests

