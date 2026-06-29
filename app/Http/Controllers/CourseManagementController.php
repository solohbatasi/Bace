<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\AcademicYear;
use App\Models\CollegeClass;
use App\Models\Enrollment;
use App\Models\Lecturer;
use App\Models\LecturerUnitAssignment;
use App\Models\Semester;
use App\Models\Student;
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

        $filters = $request->only(['search', 'department_id', 'learner_scope']);
        $user = $request->user()->loadMissing('student', 'lecturer');
        $canManageCourses = $user->hasAnyPermission('courses.add|courses.edit|courses.delete|units.add|units.edit|units.manage|lecturers.manage|classes.manage');
        $isLearner = (bool) $user->student && ! $canManageCourses;
        $isLecturer = (bool) $user->lecturer && ! $canManageCourses;
        $learnerScope = $isLearner ? ($filters['learner_scope'] ?? 'current') : 'all';
        $learnerScope = in_array($learnerScope, ['current', 'history', 'all'], true) ? $learnerScope : 'current';
        $learnerCourseIds = $isLearner ? $this->learnerCourseIds($user->student, $learnerScope) : collect();
        $learnerUnitIds = $isLearner ? $this->learnerUnitIds($user->student, $learnerScope) : collect();
        $lecturerCourseIds = $isLecturer ? $this->lecturerCourseIds($user->lecturer) : collect();
        $lecturerUnitIds = $isLecturer ? $this->lecturerUnitIds($user->lecturer) : collect();
        $teachingSummaries = $isLecturer ? $this->lecturerTeachingSummaries($user->lecturer) : collect();

        return Inertia::render('Academics/Courses', [
            'courses' => Course::query()
                ->with([
                    'department:id,name,code',
                    'parentCourse:id,code,name',
                    'subcourses' => fn ($query) => $query
                        ->when($isLearner, fn ($query) => $learnerCourseIds->isNotEmpty()
                            ? $query->whereIn('id', $learnerCourseIds)
                            : $query->whereRaw('1 = 0'))
                        ->orderBy('name')
                        ->withCount('units'),
                    'scoreLevels',
                    'units' => fn ($query) => $query
                        ->when($isLearner, fn ($query) => $learnerUnitIds->isNotEmpty()
                            ? $query->whereIn('id', $learnerUnitIds)
                            : $query->whereRaw('1 = 0'))
                        ->when($isLecturer, fn ($query) => $query->whereIn('id', $lecturerUnitIds))
                        ->with('lecturerAssignments.lecturer:id,title,first_name,last_name'),
                ])
                ->withCount(['units', 'subcourses'])
                ->when($isLearner, fn ($query) => $learnerCourseIds->isNotEmpty()
                    ? $query->whereIn('id', $learnerCourseIds)
                    : $query->whereRaw('1 = 0'))
                ->when($isLecturer, fn ($query) => $lecturerCourseIds->isNotEmpty()
                    ? $query->whereIn('id', $lecturerCourseIds)
                    : $query->whereRaw('1 = 0'))
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")))
                ->when($filters['department_id'] ?? null, fn ($query, $department) => $query->where('department_id', $department))
                ->latest()
                ->paginate(20)
                ->withQueryString()
                ->through(function (Course $course) use ($isLearner, $learnerUnitIds, $isLecturer, $lecturerUnitIds, $teachingSummaries): Course {
                    if ($isLearner && $learnerUnitIds->isNotEmpty()) {
                        $course->setRelation('units', $course->units->whereIn('id', $learnerUnitIds)->values());
                    }

                    if ($isLecturer) {
                        $course->setRelation('units', $course->units->whereIn('id', $lecturerUnitIds)->values());
                        $course->setAttribute('teaching_contexts', $teachingSummaries->get($course->id, collect())->values());
                    }

                    return $course;
                }),
            'isLearner' => $isLearner,
            'isLecturer' => $isLecturer,
            'filters' => $filters + ['learner_scope' => $learnerScope],
            'departments' => Department::orderBy('name')->get(['id', 'name', 'code']),
            'parentCourses' => Course::query()
                ->whereNull('parent_course_id')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'department_id', 'code', 'name']),
            'unitCourses' => Course::query()
                ->where('has_units', true)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'department_id', 'parent_course_id', 'code', 'name', 'has_units']),
            'lecturers' => Lecturer::orderBy('last_name')->get(['id', 'title', 'first_name', 'last_name', 'department_id']),
            'classes' => CollegeClass::orderBy('name')->get(['id', 'name', 'code', 'course_id']),
            'semesters' => Semester::orderByDesc('starts_on')->get(['id', 'name', 'academic_year_id']),
            'academicYears' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'is_current']),
            'permissions' => [
                'canAddCourse' => ! $isLearner && ! $isLecturer && $request->user()->hasAnyPermission('courses.add|classes.manage'),
                'canEditCourse' => ! $isLearner && ! $isLecturer && $request->user()->hasAnyPermission('courses.edit|classes.manage'),
                'canDeleteCourse' => ! $isLearner && ! $isLecturer && $request->user()->hasAnyPermission('courses.delete|classes.manage'),
                'canAddUnit' => ! $isLearner && ! $isLecturer && $request->user()->hasAnyPermission('units.add|classes.manage'),
                'canEditUnit' => ! $isLearner && ! $isLecturer && $request->user()->hasAnyPermission('units.edit|classes.manage'),
                'canAssignLecturer' => ! $isLearner && ! $isLecturer && $request->user()->hasAnyPermission('units.manage|lecturers.manage|classes.manage'),
                'canManageCourseScoreLevels' => ! $isLearner && ! $isLecturer && $request->user()->hasAnyPermission('courses.manage|classes.manage'),
                'canExport' => ! $isLearner && ! $isLecturer,
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

    private function learnerCourseIds(Student $student, string $scope = 'current'): \Illuminate\Support\Collection
    {
        $ids = $this->learnerStudyIds($student);

        return match ($scope) {
            'history' => $ids['all_course_ids']->diff($ids['current_course_ids'])->values(),
            'all' => $ids['all_course_ids'],
            default => $ids['current_course_ids'],
        };
    }

    private function learnerUnitIds(Student $student, string $scope = 'current'): \Illuminate\Support\Collection
    {
        $ids = $this->learnerStudyIds($student);

        return match ($scope) {
            'history' => $ids['all_unit_ids']->diff($ids['current_unit_ids'])->values(),
            'all' => $ids['all_unit_ids'],
            default => $ids['current_unit_ids'],
        };
    }

    private function learnerStudyIds(Student $student): array
    {
        $student->loadMissing([
            'semesterRegistrations:id,student_id,course_id,subcourse_id,semester_id,academic_year_id,status',
            'enrollments:id,student_id,course_id,subcourse_id,unit_id,semester_id,academic_year_id,status',
            'enrollments.unit:id,course_id',
        ]);

        $currentAcademicYearId = AcademicYear::where('is_current', true)->value('id');
        $currentSemesterId = Semester::where('is_current', true)->value('id');
        $currentRegistrationStatuses = ['pending', 'approved'];
        $currentEnrollmentStatuses = ['pending', 'approved', 'enrolled'];

        $currentRegistrations = $student->semesterRegistrations
            ->whereIn('status', $currentRegistrationStatuses)
            ->when($currentAcademicYearId, fn ($registrations) => $registrations->where('academic_year_id', $currentAcademicYearId))
            ->when($currentSemesterId, fn ($registrations) => $registrations->where('semester_id', $currentSemesterId));

        $currentEnrollments = $student->enrollments
            ->whereIn('status', $currentEnrollmentStatuses)
            ->when($currentAcademicYearId, fn ($enrollments) => $enrollments->where('academic_year_id', $currentAcademicYearId))
            ->when($currentSemesterId, fn ($enrollments) => $enrollments->where('semester_id', $currentSemesterId));

        $allCourseIds = collect([$student->course_id, $student->subcourse_id])
            ->merge($student->semesterRegistrations->pluck('course_id'))
            ->merge($student->semesterRegistrations->pluck('subcourse_id'))
            ->merge($student->enrollments->pluck('course_id'))
            ->merge($student->enrollments->pluck('subcourse_id'))
            ->merge($student->enrollments->pluck('unit.course_id'))
            ->filter()
            ->unique()
            ->values();

        $currentCourseIds = collect([$student->course_id, $student->subcourse_id])
            ->merge($currentRegistrations->pluck('course_id'))
            ->merge($currentRegistrations->pluck('subcourse_id'))
            ->merge($currentEnrollments->pluck('course_id'))
            ->merge($currentEnrollments->pluck('subcourse_id'))
            ->merge($currentEnrollments->pluck('unit.course_id'))
            ->filter()
            ->unique()
            ->values();

        return [
            'all_course_ids' => $allCourseIds,
            'current_course_ids' => $currentCourseIds,
            'all_unit_ids' => $student->enrollments->pluck('unit_id')->filter()->unique()->values(),
            'current_unit_ids' => $currentEnrollments->pluck('unit_id')->filter()->unique()->values(),
        ];
    }

    private function lecturerCourseIds(Lecturer $lecturer): \Illuminate\Support\Collection
    {
        $lecturer->loadMissing('unitAssignments.unit:id,course_id');

        return $lecturer->unitAssignments
            ->pluck('unit.course_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function lecturerUnitIds(Lecturer $lecturer): \Illuminate\Support\Collection
    {
        $lecturer->loadMissing('unitAssignments:id,lecturer_id,unit_id');

        return $lecturer->unitAssignments
            ->pluck('unit_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function lecturerTeachingSummaries(Lecturer $lecturer): \Illuminate\Support\Collection
    {
        return LecturerUnitAssignment::query()
            ->with([
                'unit:id,course_id,code,name',
                'unit.course:id,parent_course_id,code,name',
                'class:id,code,name,course_id',
                'semester:id,name',
                'academicYear:id,name',
            ])
            ->where('lecturer_id', $lecturer->id)
            ->latest()
            ->get()
            ->map(function (LecturerUnitAssignment $assignment): array {
                $learnerCount = Enrollment::query()
                    ->where('unit_id', $assignment->unit_id)
                    ->where('class_id', $assignment->class_id)
                    ->where('semester_id', $assignment->semester_id)
                    ->where('academic_year_id', $assignment->academic_year_id)
                    ->whereIn('status', ['enrolled', 'approved'])
                    ->distinct('student_id')
                    ->count('student_id');

                return [
                    'id' => $assignment->id,
                    'course_id' => $assignment->unit?->course_id,
                    'unit_id' => $assignment->unit_id,
                    'unit' => $assignment->unit,
                    'class' => $assignment->class,
                    'semester' => $assignment->semester,
                    'academic_year' => $assignment->academicYear,
                    'is_primary' => $assignment->is_primary,
                    'learner_count' => $learnerCount,
                ];
            })
            ->filter(fn (array $summary) => $summary['course_id'])
            ->groupBy('course_id');
    }

    private function courseData(Request $request, ?Course $course = null): array
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'parent_course_id' => ['nullable', 'exists:courses,id'],
            'code' => ['required', 'max:30', Rule::unique('courses')->ignore($course)->whereNull('deleted_at')],
            'name' => ['required', 'max:255'],
            'qualification_level' => ['nullable', 'max:80'],
            'duration_type' => ['required', Rule::in(['semesters', 'custom'])],
            'duration_semesters' => ['required_if:duration_type,semesters', 'nullable', 'integer', 'min:1'],
            'duration' => ['required_if:duration_type,custom', 'nullable', 'max:80'],
            'fees' => ['required', 'numeric', 'min:0'],
            'has_units' => ['boolean'],
            'grading_mode' => ['nullable', Rule::in(['grade_only', 'score_levels', 'score_levels_with_grades'])],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $data['grading_mode'] ??= 'score_levels_with_grades';

        if (($data['parent_course_id'] ?? null) && $course && (int) $data['parent_course_id'] === (int) $course->id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_course_id' => 'A course cannot be its own subcourse parent.',
            ]);
        }

        if ($data['parent_course_id'] ?? null) {
            $parent = Course::findOrFail($data['parent_course_id']);
            $data['department_id'] = $parent->department_id;
        }

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
            'grading_mode' => ['nullable', Rule::in(['grade_only', 'score_levels', 'score_levels_with_grades'])],
            'credit_hours' => ['required', 'integer', 'min:1'],
            'year_level' => ['required', 'integer', 'min:1'],
            'semester_sequence' => ['required', 'integer', 'min:1'],
            'is_core' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $data['grading_mode'] ??= 'score_levels_with_grades';

        $course = Course::findOrFail($data['course_id']);
        if ((int) $course->department_id !== (int) $data['department_id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'department_id' => 'The selected course does not belong to this department.',
            ]);
        }

        return $data;
    }
}
