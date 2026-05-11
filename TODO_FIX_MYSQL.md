# TODO - Fix mysql_connect undefined function

- [x] Replace deprecated `mysql_*` connection code (connect.php and role-specific connect.php files) with `mysqli`-based connection.
- [x] Provide backward-compatible wrappers so existing `mysql_query`, `mysql_fetch_array`, `mysql_num_rows`, etc. continue to work.
- [ ] Ensure all pages still include the correct connect.php.
- [ ] Run a quick smoke test by opening `index.php` in browser and verifying login loads.

# TODO - Project Overview alignment (next phases)

- [ ] Fix critical security issues before adding new features:
  - [x] Stop using plaintext passwords (implement bcrypt hashing + update reset.php/login.php)
- [x] Parameterize SQL queries (remove SQL injection via $_POST interpolation)
  - [x] Add CSRF token to forms that mutate data
- [x] Upgrade database schema to match overview:
  - [x] Add tables: courses, class_sections, leave_requests, notifications, system_logs
  - [x] Add required columns to attendance: check_in_time, check_out_time, status_type (present/absent/late/half-day), remarks
- [x] Implement Attendance v2 UI/workflow:
  - [x] Date selection + date-wise tracking
  - [x] Bulk attendance for a whole class/section
  - [x] Half-day & Late options
  - [x] Store check-in/check-out times
- [x] Reporting & analytics v1:
  - [x] Student attendance percentage (by month)
  - [x] Class-wise trends
  - [x] Date-range report (filter by start/end)
  - [ ] Export stubs (CSV now; PDF/Excel later)
- [ ] Leave management v1:
  - [x] Student leave request submission
  - [x] Teacher/Admin approval workflow + leave balance
- [ ] Notification hooks v1:
  - [x] Leave request status notifications & system logs
  - [ ] Low attendance (<75%) email stub
  - [ ] Daily summary email stub
