<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\AcademicYear;
use App\Models\CollegeClass;
use App\Models\Lecturer;
use App\Models\LecturerUnitAssignment;
use App\Models\Semester;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseManagementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $filters = $request->only(['search', 'department_id']);

        return Inertia::render('Academics/Courses', [
            'courses' => Course::query()
                ->with(['department:id,name,code', 'units.lecturerAssignments.lecturer:id,title,first_name,last_name'])
                ->withCount('units')
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")))
                ->when($filters['department_id'] ?? null, fn ($query, $department) => $query->where('department_id', $department))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'departments' => Department::orderBy('name')->get(['id', 'name', 'code']),
            'lecturers' => Lecturer::orderBy('last_name')->get(['id', 'title', 'first_name', 'last_name', 'department_id']),
            'classes' => CollegeClass::orderBy('name')->get(['id', 'name', 'code', 'course_id']),
            'semesters' => Semester::orderByDesc('starts_on')->get(['id', 'name', 'academic_year_id']),
            'academicYears' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'is_current']),
        ]);
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        Course::create($this->courseData($request) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('flash.banner', 'Course created.');
    }

    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $course->update($this->courseData($request, $course) + ['updated_by' => $request->user()->id]);

        return back()->with('flash.banner', 'Course updated.');
    }

    public function destroyCourse(Request $request, Course $course): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $course->forceFill(['deleted_by' => $request->user()->id])->save();
        $course->delete();

        return back()->with('flash.banner', 'Course deleted.');
    }

    public function storeUnit(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        Unit::create($this->unitData($request) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('flash.banner', 'Unit created.');
    }

    public function updateUnit(Request $request, Unit $unit): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $unit->update($this->unitData($request, $unit) + ['updated_by' => $request->user()->id]);

        return back()->with('flash.banner', 'Unit updated.');
    }

    public function assignLecturer(Request $request, Unit $unit): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $data = $request->validate([
            'lecturer_id' => ['required', 'exists:lecturers,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'is_primary' => ['boolean'],
        ]);

        LecturerUnitAssignment::updateOrCreate(
            [
                'lecturer_id' => $data['lecturer_id'],
                'unit_id' => $unit->id,
                'class_id' => $data['class_id'],
                'semester_id' => $data['semester_id'],
                'academic_year_id' => $data['academic_year_id'],
            ],
            [
                'is_primary' => $data['is_primary'] ?? true,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]
        );

        return back()->with('flash.banner', 'Lecturer assigned.');
    }

    private function courseData(Request $request, ?Course $course = null): array
    {
        return $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['required', 'max:30', Rule::unique('courses')->ignore($course)->whereNull('deleted_at')],
            'name' => ['required', 'max:255'],
            'qualification_level' => ['nullable', 'max:80'],
            'duration_semesters' => ['required', 'integer', 'min:1'],
            'fees' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }

    private function unitData(Request $request, ?Unit $unit = null): array
    {
        return $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['required', 'max:30', Rule::unique('units')->where('course_id', $request->input('course_id'))->ignore($unit)->whereNull('deleted_at')],
            'name' => ['required', 'max:255'],
            'credit_hours' => ['required', 'integer', 'min:1'],
            'year_level' => ['required', 'integer', 'min:1'],
            'semester_sequence' => ['required', 'integer', 'min:1'],
            'is_core' => ['boolean'],
            'is_active' => ['boolean'],
        ]);
    }
}
