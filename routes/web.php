<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssignmentManagementController;
use App\Http\Controllers\CourseManagementController;
use App\Http\Controllers\EnrollmentManagementController;
use App\Http\Controllers\StudentController;

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
    });

    Route::resource('students', StudentController::class)->middleware('permission:students.view');

    Route::prefix('academics')->name('academics.')->group(function () {
        Route::get('courses', [CourseManagementController::class, 'index'])->name('courses.index');
        Route::post('courses', [CourseManagementController::class, 'storeCourse'])->name('courses.store');
        Route::put('courses/{course}', [CourseManagementController::class, 'updateCourse'])->name('courses.update');
        Route::delete('courses/{course}', [CourseManagementController::class, 'destroyCourse'])->name('courses.destroy');
        Route::post('units', [CourseManagementController::class, 'storeUnit'])->name('units.store');
        Route::put('units/{unit}', [CourseManagementController::class, 'updateUnit'])->name('units.update');
        Route::post('units/{unit}/lecturers', [CourseManagementController::class, 'assignLecturer'])->name('units.lecturers.store');

        Route::get('enrollments', [EnrollmentManagementController::class, 'index'])->name('enrollments.index');
        Route::post('enrollments/register', [EnrollmentManagementController::class, 'register'])->name('enrollments.register');
        Route::post('enrollments/{registration}/approve', [EnrollmentManagementController::class, 'approve'])->name('enrollments.approve');
        Route::post('enrollments/{registration}/drop', [EnrollmentManagementController::class, 'drop'])->name('enrollments.drop');
        Route::post('enrollments/{registration}/transfer', [EnrollmentManagementController::class, 'transfer'])->name('enrollments.transfer');

        Route::get('assignments', [AssignmentManagementController::class, 'index'])->name('assignments.index');
        Route::post('assignments', [AssignmentManagementController::class, 'store'])->name('assignments.store');
        Route::put('assignments/{assignment}', [AssignmentManagementController::class, 'update'])->name('assignments.update');
        Route::post('assignments/{assignment}/publish', [AssignmentManagementController::class, 'publish'])->name('assignments.publish');
        Route::post('assignments/{assignment}/submit', [AssignmentManagementController::class, 'submit'])->name('assignments.submit');
        Route::get('assignments/{assignment}/attachments/{attachment}', [AssignmentManagementController::class, 'downloadAttachment'])->name('assignments.attachments.download');
    });
});
