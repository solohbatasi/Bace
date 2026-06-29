<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AcademicSettingsController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssignmentManagementController;
use App\Http\Controllers\CourseManagementController;
use App\Http\Controllers\DepartmentManagementController;
use App\Http\Controllers\EnrollmentManagementController;
use App\Http\Controllers\ExaminationManagementController;
use App\Http\Controllers\ExaminationResultController;
use App\Http\Controllers\LecturerManagementController;
use App\Http\Controllers\LessonTicketController;
use App\Http\Controllers\OrganisationSettingsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ScoreLevelController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UnitManagementController;
use App\Models\Course;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
        Route::post('users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
        Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::post('users/{user}/terminate', [UserController::class, 'terminate'])->name('users.terminate');

        Route::resource('roles', RoleController::class)
            ->except(['create', 'edit', 'show'])
            ->middlewareFor('index', 'permission:roles.view')
            ->middlewareFor('store', 'permission:roles.add')
            ->middlewareFor('update', 'permission:roles.edit')
            ->middlewareFor('destroy', 'permission:roles.delete');
        Route::resource('permissions', PermissionController::class)->except(['create', 'edit', 'show']);

        Route::get('system-health', SystemHealthController::class)->name('system-health')->middleware('permission:system-health.view|health.view');
        Route::delete('system-health/sessions/{session}', [SystemHealthController::class, 'destroySession'])->name('system-health.sessions.destroy')->middleware('permission:system-health.delete|tokens.revoke');
        Route::delete('system-health/tokens/{token}', [SystemHealthController::class, 'destroyToken'])->name('system-health.tokens.destroy')->middleware('permission:api-tokens.delete|tokens.revoke');

        Route::get('organisation-settings', [OrganisationSettingsController::class, 'index'])->name('organisation-settings.index')->middleware('permission:organisation-settings.view|classes.manage');
        Route::put('organisation-settings', [OrganisationSettingsController::class, 'update'])->name('organisation-settings.update')->middleware('permission:organisation-settings.edit|classes.manage');
    });

    // Student routes - ADD THE ENROLL ROUTE HERE
    Route::resource('students', StudentController::class)->middleware('permission:students.view');
    Route::post('students/enroll', [StudentController::class, 'enroll'])->name('students.enroll')->middleware('permission:students.view');

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index')->middleware('permission:payments.view|finance.view');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store')->middleware('permission:payments.add|finance.manage');
        Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update')->middleware('permission:payments.edit|finance.manage');
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy')->middleware('permission:payments.delete|finance.manage');
        Route::get('tickets', [LessonTicketController::class, 'index'])->name('tickets.index')->middleware('permission:tickets.view|finance.view');
        Route::post('tickets/rules', [LessonTicketController::class, 'storeRule'])->name('tickets.rules.store')->middleware('permission:tickets.manage|finance.manage');
        Route::put('tickets/rules/{rule}', [LessonTicketController::class, 'updateRule'])->name('tickets.rules.update')->middleware('permission:tickets.manage|finance.manage');
        Route::delete('tickets/rules/{rule}', [LessonTicketController::class, 'destroyRule'])->name('tickets.rules.destroy')->middleware('permission:tickets.manage|finance.manage');
        Route::post('tickets/issue', [LessonTicketController::class, 'issue'])->name('tickets.issue')->middleware('permission:tickets.add|finance.manage');
        Route::post('tickets/check-in', [LessonTicketController::class, 'checkIn'])->name('tickets.check-in')->middleware('permission:tickets.manage|attendance.manage|lecturers.manage|finance.manage');
        Route::get('tickets/{ticket}/download', [LessonTicketController::class, 'download'])->name('tickets.download')->middleware('permission:tickets.view|finance.view');
        Route::get('tickets/{ticket}/verify', [LessonTicketController::class, 'verify'])->name('tickets.verify')->middleware('permission:tickets.view|finance.view');
    });

    Route::prefix('academics')->name('academics.')->group(function () {
        Route::get('settings', [AcademicSettingsController::class, 'index'])->name('settings.index')->middleware('permission:academic-settings.view|classes.manage');
        Route::post('settings/academic-years', [AcademicSettingsController::class, 'storeAcademicYear'])->name('settings.academic-years.store')->middleware('permission:academic-years.add|classes.manage');
        Route::put('settings/academic-years/{academicYear}', [AcademicSettingsController::class, 'updateAcademicYear'])->name('settings.academic-years.update')->middleware('permission:academic-years.edit|classes.manage');
        Route::delete('settings/academic-years/{academicYear}', [AcademicSettingsController::class, 'destroyAcademicYear'])->name('settings.academic-years.destroy')->middleware('permission:academic-years.delete|classes.manage');
        Route::post('settings/semesters', [AcademicSettingsController::class, 'storeSemester'])->name('settings.semesters.store')->middleware('permission:semesters.add|classes.manage');
        Route::put('settings/semesters/{semester}', [AcademicSettingsController::class, 'updateSemester'])->name('settings.semesters.update')->middleware('permission:semesters.edit|classes.manage');
        Route::delete('settings/semesters/{semester}', [AcademicSettingsController::class, 'destroySemester'])->name('settings.semesters.destroy')->middleware('permission:semesters.delete|classes.manage');
        Route::post('settings/classes', [AcademicSettingsController::class, 'storeClass'])->name('settings.classes.store')->middleware('permission:classes.add|classes.manage');
        Route::put('settings/classes/{class}', [AcademicSettingsController::class, 'updateClass'])->name('settings.classes.update')->middleware('permission:classes.edit|classes.manage');
        Route::delete('settings/classes/{class}', [AcademicSettingsController::class, 'destroyClass'])->name('settings.classes.destroy')->middleware('permission:classes.delete|classes.manage');

        Route::get('courses', [CourseManagementController::class, 'index'])->name('courses.index')->middleware('permission:courses.view|classes.manage');
        Route::post('courses', [CourseManagementController::class, 'storeCourse'])->name('courses.store')->middleware('permission:courses.add|classes.manage');
        Route::put('courses/{course}', [CourseManagementController::class, 'updateCourse'])->name('courses.update')->middleware('permission:courses.edit|classes.manage');
        Route::delete('courses/{course}', [CourseManagementController::class, 'destroyCourse'])->name('courses.destroy')->middleware('permission:courses.delete|classes.manage');
        Route::put('courses/{course}/score-levels', [ScoreLevelController::class, 'updateCourse'])->name('courses.score-levels.update')->middleware('permission:courses.manage|classes.manage');
        Route::resource('departments', DepartmentManagementController::class)->except(['create', 'edit', 'show']);
        Route::resource('lecturers', LecturerManagementController::class)->except(['create', 'edit', 'show']);
        Route::get('units', [UnitManagementController::class, 'index'])->name('units.index')->middleware('permission:units.view|classes.manage');
        Route::post('units', [CourseManagementController::class, 'storeUnit'])->name('units.store')->middleware('permission:units.add|classes.manage');
        Route::put('units/{unit}', [CourseManagementController::class, 'updateUnit'])->name('units.update')->middleware('permission:units.edit|classes.manage');
        Route::delete('units/{unit}', [UnitManagementController::class, 'destroy'])->name('units.destroy')->middleware('permission:units.delete|classes.manage');
        Route::put('units/{unit}/score-levels', [ScoreLevelController::class, 'updateUnit'])->name('units.score-levels.update')->middleware('permission:units.manage|classes.manage');
        Route::post('units/{unit}/lecturers', [CourseManagementController::class, 'assignLecturer'])->name('units.lecturers.store')->middleware('permission:units.manage|lecturers.manage|classes.manage');

        Route::get('examinations', [ExaminationManagementController::class, 'index'])->name('examinations.index')->middleware('permission:examinations.view|classes.manage');
        Route::post('examinations', [ExaminationManagementController::class, 'store'])->name('examinations.store')->middleware('permission:examinations.add|classes.manage');
        Route::put('examinations/{examination}', [ExaminationManagementController::class, 'update'])->name('examinations.update')->middleware('permission:examinations.edit|classes.manage');
        Route::delete('examinations/{examination}', [ExaminationManagementController::class, 'destroy'])->name('examinations.destroy')->middleware('permission:examinations.delete|classes.manage');
        Route::put('examinations/{examination}/score-levels', [ScoreLevelController::class, 'updateExamination'])->name('examinations.score-levels.update')->middleware('permission:examinations.manage|classes.manage');
        Route::get('results', [ExaminationResultController::class, 'index'])->name('results.index')->middleware('permission:examinations.edit|examinations.manage|classes.manage');
        Route::post('results/{examination}', [ExaminationResultController::class, 'store'])->name('results.store')->middleware('permission:examinations.edit|examinations.manage|classes.manage');

        Route::get('enrollments', [EnrollmentManagementController::class, 'index'])->name('enrollments.index')->middleware('permission:enrollments.view|classes.manage');
        Route::post('enrollments/register', [EnrollmentManagementController::class, 'register'])->name('enrollments.register')->middleware('permission:enrollments.add|classes.manage');
        Route::post('enrollments/{registration}/approve', [EnrollmentManagementController::class, 'approve'])->name('enrollments.approve')->middleware('permission:enrollments.edit|enrollments.manage|classes.manage');
        Route::post('enrollments/{registration}/drop', [EnrollmentManagementController::class, 'drop'])->name('enrollments.drop')->middleware('permission:enrollments.delete|enrollments.manage|classes.manage');
        Route::post('enrollments/{registration}/transfer', [EnrollmentManagementController::class, 'transfer'])->name('enrollments.transfer')->middleware('permission:enrollments.edit|enrollments.manage|classes.manage');
        Route::post('enrollments/{registration}/score', [EnrollmentManagementController::class, 'score'])->name('enrollments.score')->middleware('permission:enrollments.edit|enrollments.manage|classes.manage');

        Route::get('assignments', [AssignmentManagementController::class, 'index'])->name('assignments.index')->middleware('permission:assignments.view|assignments.manage');
        Route::post('assignments', [AssignmentManagementController::class, 'store'])->name('assignments.store')->middleware('permission:assignments.add|assignments.manage');
        Route::put('assignments/{assignment}', [AssignmentManagementController::class, 'update'])->name('assignments.update')->middleware('permission:assignments.edit|assignments.manage');
        Route::post('assignments/{assignment}/publish', [AssignmentManagementController::class, 'publish'])->name('assignments.publish')->middleware('permission:assignments.manage');
        Route::post('assignments/{assignment}/submit', [AssignmentManagementController::class, 'submit'])->name('assignments.submit')->middleware('permission:assignments.add|assignments.manage');
        Route::get('assignments/{assignment}/attachments/{attachment}', [AssignmentManagementController::class, 'downloadAttachment'])->name('assignments.attachments.download')->middleware('permission:assignments.view|assignments.manage');
    });

    // API routes for fetching course units - ADD THIS
    Route::middleware(['auth:sanctum'])->prefix('api')->name('api.')->group(function () {
        Route::get('courses/{course}/units', function (Course $course) {
            return $course->units()->where('is_active', true)->get(['id', 'code', 'name', 'credit_hours']);
        })->name('courses.units');
    });
});
