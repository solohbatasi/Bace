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
use App\Http\Controllers\LecturerManagementController;
use App\Http\Controllers\OrganisationSettingsController;
use App\Http\Controllers\PaymentController;
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

        Route::resource('roles', RoleController::class)->except(['create', 'edit', 'show']);
        Route::resource('permissions', PermissionController::class)->except(['create', 'edit', 'show']);

        Route::get('system-health', SystemHealthController::class)->name('system-health');
        Route::delete('system-health/sessions/{session}', [SystemHealthController::class, 'destroySession'])->name('system-health.sessions.destroy');
        Route::delete('system-health/tokens/{token}', [SystemHealthController::class, 'destroyToken'])->name('system-health.tokens.destroy');

        Route::get('organisation-settings', [OrganisationSettingsController::class, 'index'])->name('organisation-settings.index');
        Route::put('organisation-settings', [OrganisationSettingsController::class, 'update'])->name('organisation-settings.update');
    });

    // Student routes - ADD THE ENROLL ROUTE HERE
    Route::resource('students', StudentController::class)->middleware('permission:students.view');
    Route::post('students/enroll', [StudentController::class, 'enroll'])->name('students.enroll')->middleware('permission:students.view');

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index')->middleware('permission:finance.view');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store')->middleware('permission:finance.manage');
        Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update')->middleware('permission:finance.manage');
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy')->middleware('permission:finance.manage');
    });

    Route::prefix('academics')->name('academics.')->group(function () {
        Route::get('settings', [AcademicSettingsController::class, 'index'])->name('settings.index');
        Route::post('settings/academic-years', [AcademicSettingsController::class, 'storeAcademicYear'])->name('settings.academic-years.store');
        Route::put('settings/academic-years/{academicYear}', [AcademicSettingsController::class, 'updateAcademicYear'])->name('settings.academic-years.update');
        Route::delete('settings/academic-years/{academicYear}', [AcademicSettingsController::class, 'destroyAcademicYear'])->name('settings.academic-years.destroy');
        Route::post('settings/semesters', [AcademicSettingsController::class, 'storeSemester'])->name('settings.semesters.store');
        Route::put('settings/semesters/{semester}', [AcademicSettingsController::class, 'updateSemester'])->name('settings.semesters.update');
        Route::delete('settings/semesters/{semester}', [AcademicSettingsController::class, 'destroySemester'])->name('settings.semesters.destroy');
        Route::post('settings/classes', [AcademicSettingsController::class, 'storeClass'])->name('settings.classes.store');
        Route::put('settings/classes/{class}', [AcademicSettingsController::class, 'updateClass'])->name('settings.classes.update');
        Route::delete('settings/classes/{class}', [AcademicSettingsController::class, 'destroyClass'])->name('settings.classes.destroy');

        Route::get('courses', [CourseManagementController::class, 'index'])->name('courses.index');
        Route::post('courses', [CourseManagementController::class, 'storeCourse'])->name('courses.store');
        Route::put('courses/{course}', [CourseManagementController::class, 'updateCourse'])->name('courses.update');
        Route::delete('courses/{course}', [CourseManagementController::class, 'destroyCourse'])->name('courses.destroy');
        Route::resource('departments', DepartmentManagementController::class)->except(['create', 'edit', 'show']);
        Route::resource('lecturers', LecturerManagementController::class)->except(['create', 'edit', 'show']);
        Route::get('units', [UnitManagementController::class, 'index'])->name('units.index');
        Route::post('units', [CourseManagementController::class, 'storeUnit'])->name('units.store');
        Route::put('units/{unit}', [CourseManagementController::class, 'updateUnit'])->name('units.update');
        Route::delete('units/{unit}', [UnitManagementController::class, 'destroy'])->name('units.destroy');
        Route::post('units/{unit}/lecturers', [CourseManagementController::class, 'assignLecturer'])->name('units.lecturers.store');

        Route::get('enrollments', [EnrollmentManagementController::class, 'index'])->name('enrollments.index');
        Route::post('enrollments/register', [EnrollmentManagementController::class, 'register'])->name('enrollments.register');
        Route::post('enrollments/{registration}/approve', [EnrollmentManagementController::class, 'approve'])->name('enrollments.approve');
        Route::post('enrollments/{registration}/drop', [EnrollmentManagementController::class, 'drop'])->name('enrollments.drop');
        Route::post('enrollments/{registration}/transfer', [EnrollmentManagementController::class, 'transfer'])->name('enrollments.transfer');
        Route::post('enrollments/{registration}/score', [EnrollmentManagementController::class, 'score'])->name('enrollments.score');

        Route::get('assignments', [AssignmentManagementController::class, 'index'])->name('assignments.index');
        Route::post('assignments', [AssignmentManagementController::class, 'store'])->name('assignments.store');
        Route::put('assignments/{assignment}', [AssignmentManagementController::class, 'update'])->name('assignments.update');
        Route::post('assignments/{assignment}/publish', [AssignmentManagementController::class, 'publish'])->name('assignments.publish');
        Route::post('assignments/{assignment}/submit', [AssignmentManagementController::class, 'submit'])->name('assignments.submit');
        Route::get('assignments/{assignment}/attachments/{attachment}', [AssignmentManagementController::class, 'downloadAttachment'])->name('assignments.attachments.download');
    });

    // API routes for fetching course units - ADD THIS
    Route::middleware(['auth:sanctum'])->prefix('api')->name('api.')->group(function () {
        Route::get('courses/{course}/units', function (Course $course) {
            return $course->units()->where('is_active', true)->get(['id', 'code', 'name', 'credit_hours']);
        })->name('courses.units');
    });
});
