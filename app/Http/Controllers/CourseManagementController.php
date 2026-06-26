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
        abort_unless($request->user()->hasAnyPermission('courses.view|classes.manage'), 403);

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
            'unitCourses' => Course::query()
                ->where('has_units', true)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'department_id', 'code', 'name', 'has_units']),
            'lecturers' => Lecturer::orderBy('last_name')->get(['id', 'title', 'first_name', 'last_name', 'department_id']),
            'classes' => CollegeClass::orderBy('name')->get(['id', 'name', 'code', 'course_id']),
            'semesters' => Semester::orderByDesc('starts_on')->get(['id', 'name', 'academic_year_id']),
            'academicYears' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'is_current']),
            'permissions' => [
                'canAddCourse' => $request->user()->hasAnyPermission('courses.add|classes.manage'),
                'canEditCourse' => $request->user()->hasAnyPermission('courses.edit|classes.manage'),
                'canDeleteCourse' => $request->user()->hasAnyPermission('courses.delete|classes.manage'),
                'canAddUnit' => $request->user()->hasAnyPermission('units.add|classes.manage'),
                'canEditUnit' => $request->user()->hasAnyPermission('units.edit|classes.manage'),
                'canAssignLecturer' => $request->user()->hasAnyPermission('units.manage|lecturers.manage|classes.manage'),
            ],
        ]);
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('courses.add|classes.manage'), 403);

        Course::create($this->courseData($request) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('flash.banner', 'Course created.');
    }

    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('courses.edit|classes.manage'), 403);

        $course->update($this->courseData($request, $course) + ['updated_by' => $request->user()->id]);

        return back()->with('flash.banner', 'Course updated.');
    }

    public function destroyCourse(Request $request, Course $course): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('courses.delete|classes.manage'), 403);

        $course->forceFill(['deleted_by' => $request->user()->id])->save();
        $course->delete();

        return back()->with('flash.banner', 'Course deleted.');
    }

    public function storeUnit(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('units.add|classes.manage'), 403);

        Unit::create($this->unitData($request) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('flash.banner', 'Unit created.');
    }

    public function updateUnit(Request $request, Unit $unit): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('units.edit|classes.manage'), 403);

        $unit->update($this->unitData($request, $unit) + ['updated_by' => $request->user()->id]);

        return back()->with('flash.banner', 'Unit updated.');
    }

    public function assignLecturer(Request $request, Unit $unit): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('units.manage|lecturers.manage|classes.manage'), 403);

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
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['required', 'max:30', Rule::unique('courses')->ignore($course)->whereNull('deleted_at')],
            'name' => ['required', 'max:255'],
            'qualification_level' => ['nullable', 'max:80'],
            'duration_type' => ['required', Rule::in(['semesters', 'custom'])],
            'duration_semesters' => ['required_if:duration_type,semesters', 'nullable', 'integer', 'min:1'],
            'duration' => ['required_if:duration_type,custom', 'nullable', 'max:80'],
            'fees' => ['required', 'numeric', 'min:0'],
            'has_units' => ['boolean'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        if ($data['duration_type'] === 'semesters') {
            $data['duration'] = null;
        } else {
            $data['duration_semesters'] = null;
        }

        return $data;
    }

    private function unitData(Request $request, ?Unit $unit = null): array
    {
        $data = $request->validate([
            'course_id' => [
                'required',
                Rule::exists('courses', 'id')->where('has_units', true)->where('is_active', true),
            ],
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['required', 'max:30', Rule::unique('units')->where('course_id', $request->input('course_id'))->ignore($unit)->whereNull('deleted_at')],
            'name' => ['required', 'max:255'],
            'duration' => ['nullable', 'max:80'],
            'credit_hours' => ['required', 'integer', 'min:1'],
            'year_level' => ['required', 'integer', 'min:1'],
            'semester_sequence' => ['required', 'integer', 'min:1'],
            'is_core' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $course = Course::findOrFail($data['course_id']);
        if ((int) $course->department_id !== (int) $data['department_id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'department_id' => 'The selected course does not belong to this department.',
            ]);
        }

        return $data;
    }
}
