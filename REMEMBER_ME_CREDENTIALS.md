# Remember Me (Credentials Memory)

This project currently shows a **“Remember me”** checkbox on the login form, but it does not yet persist credentials.

## What we will store (requested)
- Save “Remember me” data in a file (Markdown/notes-first, until PHP persistence is added).
- Recommended stored fields (safe):
  - `username`
  - `role` (`student | teacher | admin`)

## What to do
1. User checks **Remember me** and logs in.
2. System writes a record to this project file(s) to “remember” the last login identity.
3. Next time, login can:
   - prefill `username` and `type` (student/teacher/admin)
   - NOT auto-login unless explicitly requested

## Format (example record)
- Role: student
- Username: STU001
- Saved at: 2026-05-10

## Notes
- Storing passwords in plain text is NOT recommended.
