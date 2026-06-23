<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\CollegeClass;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\SemesterRegistration;
use App\Models\Student;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $student = $request->user()->student;
        $canManage = $request->user()->hasPermission('classes.manage');

        abort_unless($canManage || $student, 403);

        return Inertia::render('Academics/Enrollments', [
            'canManage' => $canManage,
            'student' => $student?->load(['course:id,name,code', 'class:id,name,code']),
            'registrations' => SemesterRegistration::query()
                ->with(['student:id,admission_number,first_name,last_name', 'class:id,name,code', 'semester:id,name', 'academicYear:id,name', 'enrollments.unit:id,code,name,credit_hours'])
                ->when(! $canManage, fn ($query) => $query->where('student_id', $student->id))
                ->latest('registered_at')
                ->paginate(10),
            'currentYear' => AcademicYear::where('is_current', true)->first(),
            'semesters' => Semester::with('academicYear:id,name')->orderByDesc('starts_on')->get(['id', 'academic_year_id', 'name', 'sequence', 'is_current']),
            'units' => Unit::query()
                ->with('course:id,name,code')
                ->when($student, fn ($query) => $query->where('course_id', $student->course_id))
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'course_id', 'department_id', 'code', 'name', 'credit_hours', 'year_level', 'semester_sequence']),
            'classes' => CollegeClass::orderBy('name')->get(['id', 'course_id', 'department_id', 'name', 'code']),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $student = $request->user()->student;
        abort_unless($student, 403);

        $data = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'unit_ids' => ['required', 'array', 'min:1'],
            'unit_ids.*' => ['exists:units,id'],
        ]);

        DB::transaction(function () use ($request, $student, $data): void {
            $registration = SemesterRegistration::create([
                'student_id' => $student->id,
                'class_id' => $student->class_id,
                'semester_id' => $data['semester_id'],
                'academic_year_id' => $data['academic_year_id'],
                'registered_at' => now(),
                'status' => 'pending',
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            foreach ($data['unit_ids'] as $unitId) {
                Enrollment::create([
                    'semester_registration_id' => $registration->id,
                    'student_id' => $student->id,
                    'unit_id' => $unitId,
                    'class_id' => $student->class_id,
                    'semester_id' => $data['semester_id'],
                    'academic_year_id' => $data['academic_year_id'],
                    'enrolled_on' => now()->toDateString(),
                    'status' => 'pending',
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);
            }
        });

        return back()->with('flash.banner', 'Semester registration submitted.');
    }

    public function approve(Request $request, SemesterRegistration $registration): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $registration->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $registration->enrollments()->update(['status' => 'approved', 'updated_by' => $request->user()->id]);

        return back()->with('flash.banner', 'Enrollment approved.');
    }

    public function drop(Request $request, SemesterRegistration $registration): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $registration->update(['status' => 'dropped', 'notes' => $request->input('notes'), 'updated_by' => $request->user()->id]);
        $registration->enrollments()->update(['status' => 'dropped', 'updated_by' => $request->user()->id]);

        return back()->with('flash.banner', 'Enrollment dropped.');
    }

    public function transfer(Request $request, SemesterRegistration $registration): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $data = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'unit_ids' => ['required', 'array', 'min:1'],
            'unit_ids.*' => ['exists:units,id'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $registration, $data): void {
            $registration->update([
                'class_id' => $data['class_id'],
                'status' => 'transferred',
                'notes' => $data['notes'] ?? null,
                'updated_by' => $request->user()->id,
            ]);

            $registration->enrollments()->update(['status' => 'transferred', 'updated_by' => $request->user()->id]);

            foreach ($data['unit_ids'] as $unitId) {
                Enrollment::updateOrCreate(
                    [
                        'student_id' => $registration->student_id,
                        'unit_id' => $unitId,
                        'semester_id' => $registration->semester_id,
                        'academic_year_id' => $registration->academic_year_id,
                    ],
                    [
                        'semester_registration_id' => $registration->id,
                        'class_id' => $data['class_id'],
                        'enrolled_on' => now()->toDateString(),
                        'status' => 'approved',
                        'created_by' => $request->user()->id,
                        'updated_by' => $request->user()->id,
                    ]
                );
            }
        });

        return back()->with('flash.banner', 'Enrollment transferred.');
    }
}
