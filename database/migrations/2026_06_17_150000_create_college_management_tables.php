<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addAuditColumnsToExistingTables();

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('head_lecturer_id')->nullable();
            $table->string('code', 30);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index('parent_department_id');
            $table->index('head_lecturer_id');
            $table->index('is_active');
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_current')->default(false);
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['starts_on', 'ends_on']);
            $table->index('is_current');
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name', 80);
            $table->unsignedSmallInteger('sequence');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_current')->default(false);
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['academic_year_id', 'sequence']);
            $table->index('is_current');
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('qualification_level', 80)->nullable();
            $table->unsignedSmallInteger('duration_semesters');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index('department_id');
            $table->index('is_active');
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_lecturer_id')->nullable();
            $table->string('code', 50);
            $table->string('name');
            $table->unsignedSmallInteger('year_level');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['course_id', 'academic_year_id']);
            $table->index('class_lecturer_id');
            $table->index('is_active');
        });

        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('staff_number', 50);
            $table->string('title', 30)->nullable();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('hired_on')->nullable();
            $table->string('employment_status', 30)->default('active');
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index('department_id');
            $table->index('employment_status');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('head_lecturer_id')->references('id')->on('lecturers')->nullOnDelete();
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->foreign('class_lecturer_id')->references('id')->on('lecturers')->nullOnDelete();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('admission_number', 50);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('photo_path', 2048)->nullable();
            $table->text('address')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relationship', 80)->nullable();
            $table->string('guardian_phone', 30)->nullable();
            $table->string('guardian_email')->nullable();
            $table->text('guardian_address')->nullable();
            $table->date('admitted_on');
            $table->string('status', 30)->default('active');
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['course_id', 'class_id']);
            $table->index('department_id');
            $table->index('status');
        });

        Schema::create('student_academic_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('institution_name');
            $table->string('qualification')->nullable();
            $table->string('grade')->nullable();
            $table->date('started_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->text('notes')->nullable();
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index('student_id');
            $table->index(['started_on', 'completed_on']);
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->unsignedTinyInteger('credit_hours')->default(3);
            $table->unsignedSmallInteger('year_level');
            $table->unsignedSmallInteger('semester_sequence');
            $table->boolean('is_core')->default(true);
            $table->boolean('is_active')->default(true);
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['course_id', 'year_level', 'semester_sequence']);
            $table->index('department_id');
            $table->index('is_active');
        });

        Schema::create('lecturer_unit_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecturer_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->boolean('is_primary')->default(true);
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['lecturer_id', 'academic_year_id', 'semester_id']);
            $table->index(['unit_id', 'class_id']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_registration_id')->nullable();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->date('enrolled_on');
            $table->string('status', 30)->default('enrolled');
            $table->decimal('final_grade_points', 5, 2)->nullable();
            $table->string('final_grade', 5)->nullable();
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['student_id', 'academic_year_id', 'semester_id']);
            $table->index(['unit_id', 'class_id']);
            $table->index('status');
        });

        Schema::create('semester_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->timestampTz('registered_at');
            $table->timestampTz('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['student_id', 'academic_year_id', 'semester_id']);
            $table->index(['status', 'registered_at']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('semester_registration_id')->references('id')->on('semester_registrations')->nullOnDelete();
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecturer_unit_assignment_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestampTz('opens_at')->nullable();
            $table->timestampTz('due_at');
            $table->decimal('max_score', 8, 2)->default(100);
            $table->string('submission_type', 30)->default('file');
            $table->string('status', 30)->default('draft');
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['lecturer_unit_assignment_id', 'due_at']);
            $table->index('status');
        });

        Schema::create('assignment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 40)->default('public');
            $table->string('path', 2048);
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index('assignment_id');
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('graded_by')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->timestampTz('submitted_at')->nullable();
            $table->text('submission_text')->nullable();
            $table->string('file_path', 2048)->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestampTz('graded_at')->nullable();
            $table->string('status', 30)->default('draft');
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['assignment_id', 'student_id']);
            $table->index('graded_by');
            $table->index('status');
        });

        Schema::create('assignment_submission_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->timestampTz('submitted_at');
            $table->boolean('is_late')->default(false);
            $table->text('submission_text')->nullable();
            $table->string('disk', 40)->default('public');
            $table->string('file_path', 2048)->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['assignment_submission_id', 'version_number']);
            $table->index('is_late');
        });

        Schema::create('fees_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('currency', 3)->default('KES');
            $table->decimal('tuition_amount', 12, 2)->default(0);
            $table->decimal('registration_amount', 12, 2)->default(0);
            $table->decimal('exam_amount', 12, 2)->default(0);
            $table->decimal('library_amount', 12, 2)->default(0);
            $table->decimal('other_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['course_id', 'academic_year_id', 'semester_id']);
            $table->index('is_active');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('fees_structure_id')->nullable()->constrained('fees_structures')->nullOnDelete();
            $table->string('invoice_number', 50);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 3)->default('KES');
            $table->decimal('subtotal_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['student_id', 'status']);
            $table->index('fees_structure_id');
            $table->index('invoice_date');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_reference', 80);
            $table->date('payment_date');
            $table->string('method', 40);
            $table->string('currency', 3)->default('KES');
            $table->decimal('amount', 12, 2);
            $table->string('status', 30)->default('pending');
            $table->string('external_transaction_id')->nullable();
            $table->text('notes')->nullable();
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['student_id', 'payment_date']);
            $table->index('invoice_id');
            $table->index('status');
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('receipt_number', 50);
            $table->date('receipt_date');
            $table->string('currency', 3)->default('KES');
            $table->decimal('amount', 12, 2);
            $table->string('status', 30)->default('issued');
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index('payment_id');
            $table->index('invoice_id');
            $table->index('receipt_date');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 80);
            $table->string('channel', 40)->default('database');
            $table->string('title');
            $table->text('body');
            $table->jsonb('data')->nullable();
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['user_id', 'read_at']);
            $table->index(['type', 'channel']);
            $table->index('sent_at');
        });

        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained()->restrictOnDelete();
            $table->foreignId('lecturer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->date('attendance_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('status', 30)->default('present');
            $table->text('remarks')->nullable();
            $this->auditColumns($table);
            $table->softDeletesTz();
            $table->timestampsTz();

            $table->index(['student_id', 'attendance_date']);
            $table->index(['unit_id', 'class_id', 'attendance_date']);
            $table->index('status');
        });

        $this->addPostgresConstraintsAndPartialIndexes();
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('fees_structures');
        Schema::dropIfExists('assignment_submission_versions');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignment_attachments');
        Schema::dropIfExists('assignments');
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['semester_registration_id']);
        });
        Schema::dropIfExists('semester_registrations');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('lecturer_unit_assignments');
        Schema::dropIfExists('units');
        Schema::dropIfExists('student_academic_histories');
        Schema::dropIfExists('students');

        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['class_lecturer_id']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['head_lecturer_id']);
        });

        Schema::dropIfExists('lecturers');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('departments');

        $this->dropAuditColumnsFromExistingTables();
    }

    private function auditColumns(Blueprint $table): void
    {
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
    }

    private function addAuditColumnsToExistingTables(): void
    {
        foreach (['users', 'roles', 'permissions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                $table->foreignId('deleted_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
                $table->softDeletesTz();
            });
        }
    }

    private function dropAuditColumnsFromExistingTables(): void
    {
        foreach (['permissions', 'roles', 'users'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
                $table->dropConstrainedForeignId('updated_by');
                $table->dropConstrainedForeignId('deleted_by');
                $table->dropSoftDeletesTz();
            });
        }
    }

    private function addPostgresConstraintsAndPartialIndexes(): void
    {
        $statements = [
            "CREATE UNIQUE INDEX departments_code_active_unique ON departments (lower(code)) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX departments_name_active_unique ON departments (lower(name)) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX academic_years_name_active_unique ON academic_years (name) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX academic_years_one_current_unique ON academic_years (is_current) WHERE is_current = true AND deleted_at IS NULL",
            "CREATE UNIQUE INDEX semesters_year_sequence_active_unique ON semesters (academic_year_id, sequence) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX semesters_one_current_per_year_unique ON semesters (academic_year_id, is_current) WHERE is_current = true AND deleted_at IS NULL",
            "CREATE UNIQUE INDEX courses_code_active_unique ON courses (lower(code)) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX classes_code_year_active_unique ON classes (lower(code), academic_year_id) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX lecturers_staff_number_active_unique ON lecturers (lower(staff_number)) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX lecturers_email_active_unique ON lecturers (lower(email)) WHERE email IS NOT NULL AND deleted_at IS NULL",
            "CREATE UNIQUE INDEX students_admission_number_active_unique ON students (lower(admission_number)) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX students_email_active_unique ON students (lower(email)) WHERE email IS NOT NULL AND deleted_at IS NULL",
            "CREATE UNIQUE INDEX units_course_code_active_unique ON units (course_id, lower(code)) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX lecturer_unit_assignments_active_unique ON lecturer_unit_assignments (lecturer_id, unit_id, class_id, semester_id, academic_year_id) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX enrollments_student_unit_term_active_unique ON enrollments (student_id, unit_id, semester_id, academic_year_id) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX semester_registrations_student_term_active_unique ON semester_registrations (student_id, semester_id, academic_year_id) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX assignment_submissions_student_assignment_active_unique ON assignment_submissions (assignment_id, student_id) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX assignment_submission_versions_number_active_unique ON assignment_submission_versions (assignment_submission_id, version_number) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX fees_structures_course_class_term_active_unique ON fees_structures (course_id, coalesce(class_id, 0), academic_year_id, coalesce(semester_id, 0)) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX invoices_number_active_unique ON invoices (lower(invoice_number)) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX payments_reference_active_unique ON payments (lower(payment_reference)) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX receipts_number_active_unique ON receipts (lower(receipt_number)) WHERE deleted_at IS NULL",
            "CREATE UNIQUE INDEX attendance_student_unit_session_active_unique ON attendance (student_id, unit_id, attendance_date, coalesce(starts_at, '00:00:00'::time)) WHERE deleted_at IS NULL",
            "ALTER TABLE academic_years ADD CONSTRAINT academic_years_dates_check CHECK (ends_on > starts_on)",
            "ALTER TABLE semesters ADD CONSTRAINT semesters_dates_check CHECK (ends_on > starts_on)",
            "ALTER TABLE courses ADD CONSTRAINT courses_duration_check CHECK (duration_semesters > 0)",
            "ALTER TABLE courses ADD CONSTRAINT courses_fees_check CHECK (fees >= 0)",
            "ALTER TABLE classes ADD CONSTRAINT classes_year_level_check CHECK (year_level > 0)",
            "ALTER TABLE classes ADD CONSTRAINT classes_capacity_check CHECK (capacity IS NULL OR capacity > 0)",
            "ALTER TABLE units ADD CONSTRAINT units_credit_hours_check CHECK (credit_hours > 0)",
            "ALTER TABLE units ADD CONSTRAINT units_year_level_check CHECK (year_level > 0)",
            "ALTER TABLE units ADD CONSTRAINT units_semester_sequence_check CHECK (semester_sequence > 0)",
            "ALTER TABLE fees_structures ADD CONSTRAINT fees_structures_amounts_check CHECK (tuition_amount >= 0 AND registration_amount >= 0 AND exam_amount >= 0 AND library_amount >= 0 AND other_amount >= 0 AND total_amount >= 0)",
            "ALTER TABLE fees_structures ADD CONSTRAINT fees_structures_dates_check CHECK (effective_to IS NULL OR effective_to >= effective_from)",
            "ALTER TABLE invoices ADD CONSTRAINT invoices_amounts_check CHECK (subtotal_amount >= 0 AND discount_amount >= 0 AND tax_amount >= 0 AND total_amount >= 0 AND paid_amount >= 0 AND paid_amount <= total_amount)",
            "ALTER TABLE payments ADD CONSTRAINT payments_amount_check CHECK (amount > 0)",
            "ALTER TABLE receipts ADD CONSTRAINT receipts_amount_check CHECK (amount > 0)",
            "ALTER TABLE assignments ADD CONSTRAINT assignments_max_score_check CHECK (max_score > 0)",
            "ALTER TABLE assignment_submissions ADD CONSTRAINT assignment_submissions_score_check CHECK (score IS NULL OR score >= 0)",
            "ALTER TABLE students ADD CONSTRAINT students_status_check CHECK (status IN ('active', 'deferred', 'graduated', 'suspended', 'expelled'))",
            "ALTER TABLE student_academic_histories ADD CONSTRAINT student_academic_histories_dates_check CHECK (completed_on IS NULL OR started_on IS NULL OR completed_on >= started_on)",
        ];

        foreach ($statements as $statement) {
            DB::statement($statement);
        }
    }
};
