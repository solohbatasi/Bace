# College Management System Database Schema

This schema targets PostgreSQL and extends the existing Laravel users, roles, and permissions tables. All domain tables use bigint primary keys, foreign keys, timezone-aware timestamps, soft deletes, and audit references to `users` through `created_by`, `updated_by`, and `deleted_by`.

## Core Identity and Access

- `users`: authentication identity from Laravel Jetstream/Fortify. Extended with soft deletes and audit columns.
- `roles`: named access groups such as admin, registrar, lecturer, student, finance. Extended with soft deletes and audit columns.
- `permissions`: granular capabilities grouped by feature area. Extended with soft deletes and audit columns.
- `role_user`, `permission_user`, `permission_role`: many-to-many RBAC pivots already present in the application.

ERD relationships:

- `users` many-to-many `roles`
- `users` many-to-many `permissions`
- `roles` many-to-many `permissions`
- `users` one-to-one or optional one-to-one `students`
- `users` one-to-one or optional one-to-one `lecturers`

## Academic Structure

- `departments`: academic or administrative departments. Supports parent departments and a head lecturer.
- `academic_years`: academic calendar year records with date boundaries and one active current year.
- `semesters`: terms within an academic year, ordered by `sequence`.
- `courses`: programs offered by a department.
- `classes`: student cohorts for a course, academic year, and optional semester.
- `units`: course units/modules assigned to a course and department.

ERD relationships:

- `departments` one-to-many `courses`
- `departments` one-to-many `lecturers`
- `departments` one-to-many `students`
- `departments` one-to-many `units`
- `departments` self-referencing hierarchy through `parent_department_id`
- `lecturers` optionally head `departments`
- `academic_years` one-to-many `semesters`
- `academic_years` one-to-many `classes`
- `courses` one-to-many `classes`
- `courses` one-to-many `students`
- `courses` one-to-many `units`
- `semesters` optionally scope `classes`

## People

- `students`: student academic profile, linked to user login when portal access exists.
- `lecturers`: lecturer staff profile, linked to user login when staff portal access exists.

Important constraints:

- Active student admission numbers are unique.
- Active lecturer staff numbers are unique.
- Optional student and lecturer emails are unique while active.
- Students belong to one current course and may belong to one current class.

## Teaching and Assessment

- `lecturer_unit_assignments`: assigns a lecturer to teach a unit for a class, semester, and academic year.
- `enrollments`: registers a student into a unit for a class and term.
- `assignments`: coursework created under a lecturer/unit/class assignment.
- `assignment_submissions`: student submissions, grading, scores, and feedback.
- `attendance`: per-student attendance for a unit class session.

ERD relationships:

- `lecturers` one-to-many `lecturer_unit_assignments`
- `units` one-to-many `lecturer_unit_assignments`
- `classes` one-to-many `lecturer_unit_assignments`
- `students` one-to-many `enrollments`
- `units` one-to-many `enrollments`
- `lecturer_unit_assignments` one-to-many `assignments`
- `assignments` one-to-many `assignment_submissions`
- `students` one-to-many `assignment_submissions`
- `students` one-to-many `attendance`
- `units` one-to-many `attendance`
- `classes` one-to-many `attendance`

Important constraints:

- A student can only have one active enrollment for the same unit, semester, and academic year.
- A student can only have one active submission per assignment.
- Attendance is unique per student, unit, date, and start time while active.
- Scores and grade points cannot be negative.

## Finance

- `fees_structures`: fee schedules by course, optional class, academic year, and optional semester.
- `invoices`: student billing records generated from a fee structure or manual charge.
- `payments`: student payments, optionally linked to an invoice.
- `receipts`: issued receipts for payments.

ERD relationships:

- `courses` one-to-many `fees_structures`
- `classes` optionally one-to-many `fees_structures`
- `academic_years` one-to-many `fees_structures`
- `semesters` optionally one-to-many `fees_structures`
- `students` one-to-many `invoices`
- `fees_structures` optionally one-to-many `invoices`
- `students` one-to-many `payments`
- `invoices` optionally one-to-many `payments`
- `payments` one-to-one or one-to-many `receipts`

Important constraints:

- Invoice numbers, payment references, and receipt numbers are unique while active.
- Monetary amounts must be non-negative, with payments and receipts strictly positive.
- Invoice paid amount cannot exceed invoice total amount.
- Fee structure effective end date cannot precede effective start date.

## Notifications

- `notifications`: user-targeted database notification records with channel, type, JSON payload, read state, and sent state.

ERD relationships:

- `users` one-to-many `notifications`
- `users` optionally creates `notifications`

## PostgreSQL Index Strategy

The migration uses normal B-tree indexes for foreign keys, status fields, date lookups, and common reporting filters. It also adds PostgreSQL partial unique indexes so soft-deleted records do not permanently block reused business identifiers such as course codes, admission numbers, invoice numbers, and payment references.

## Soft Deletes and Auditing

Every core domain table includes:

- `created_by`: nullable user who created the record.
- `updated_by`: nullable user who last changed the record.
- `deleted_by`: nullable user who soft-deleted the record.
- `created_at` and `updated_at`: timezone-aware Laravel timestamps.
- `deleted_at`: timezone-aware Laravel soft-delete timestamp.

Foreign keys for audit users use `nullOnDelete`, preserving historical records even if an operator account is removed.
