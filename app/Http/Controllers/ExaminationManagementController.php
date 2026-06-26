<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Examination;
use App\Models\Semester;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ExaminationManagementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAnyPermission('examinations.view|classes.manage'), 403);

        $filters = $request->only(['search', 'owner_type', 'course_id', 'unit_id', 'scope_type', 'status']);

        return Inertia::render('Academics/Examinations', [
            'examinations' => Examination::query()
                ->with(['course:id,code,name,department_id', 'unit:id,code,name,course_id', 'unit.course:id,code,name', 'academicYear:id,name', 'semester:id,name', 'scoreLevels'])
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")))
                ->when(($filters['owner_type'] ?? null) === 'course', fn ($query) => $query->whereNotNull('course_id'))
                ->when(($filters['owner_type'] ?? null) === 'unit', fn ($query) => $query->whereNotNull('unit_id'))
                ->when($filters['course_id'] ?? null, fn ($query, $courseId) => $query->where(fn ($query) => $query
                    ->where('course_id', $courseId)
                    ->orWhereHas('unit', fn ($query) => $query->where('course_id', $courseId))))
                ->when($filters['unit_id'] ?? null, fn ($query, $unitId) => $query->where('unit_id', $unitId))
                ->when($filters['scope_type'] ?? null, fn ($query, $scope) => $query->where('scope_type', $scope))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'scopeTypes' => Examination::SCOPE_TYPES,
            'departments' => Department::orderBy('name')->get(['id', 'code', 'name']),
            'courses' => Course::orderBy('name')->get(['id', 'department_id', 'code', 'name', 'has_units']),
            'units' => Unit::with('course:id,code,name')->orderBy('code')->get(['id', 'course_id', 'department_id', 'code', 'name']),
            'academicYears' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'is_current']),
            'semesters' => Semester::orderByDesc('starts_on')->get(['id', 'academic_year_id', 'name', 'is_current']),
            'permissions' => [
                'canAdd' => $request->user()->hasAnyPermission('examinations.add|classes.manage'),
                'canEdit' => $request->user()->hasAnyPermission('examinations.edit|classes.manage'),
                'canDelete' => $request->user()->hasAnyPermission('examinations.delete|classes.manage'),
                'canManage' => $request->user()->hasAnyPermission('examinations.manage|classes.manage'),
                'canManageScoreLevels' => $request->user()->hasAnyPermission('examinations.manage|classes.manage'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('examinations.add|classes.manage'), 403);

        Examination::create($this->examinationData($request) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('flash.banner', 'Examination created.');
    }

    public function update(Request $request, Examination $examination): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('examinations.edit|classes.manage'), 403);

        $examination->update($this->examinationData($request, $examination) + [
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('flash.banner', 'Examination updated.');
    }

    public function destroy(Request $request, Examination $examination): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('examinations.delete|classes.manage'), 403);

        $examination->forceFill(['deleted_by' => $request->user()->id])->save();
        $examination->delete();

        return back()->with('flash.banner', 'Examination deleted.');
    }

    private function examinationData(Request $request, ?Examination $examination = null): array
    {
        $data = $request->validate([
            'owner_type' => ['required', Rule::in(['course', 'unit'])],
            'course_id' => ['nullable', 'exists:courses,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
            'code' => ['nullable', 'max:40', Rule::unique('examinations')->ignore($examination)->whereNull('deleted_at')],
            'name' => ['required', 'max:255'],
            'scope_type' => ['required', Rule::in(Examination::SCOPE_TYPES)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'max_score' => ['nullable', 'numeric', 'min:0'],
            'weight_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grading_mode' => ['nullable', Rule::in(['grade_only', 'score_levels', 'score_levels_with_grades'])],
            'is_analysed' => ['boolean'],
            'include_in_final_analysis' => ['boolean'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);
        $data['grading_mode'] ??= 'score_levels_with_grades';

        if ($data['owner_type'] === 'course') {
            $data['unit_id'] = null;
            if (blank($data['course_id'] ?? null)) {
                throw ValidationException::withMessages(['course_id' => 'Select the course for this examination.']);
            }
        } else {
            $data['course_id'] = null;
            if (blank($data['unit_id'] ?? null)) {
                throw ValidationException::withMessages(['unit_id' => 'Select the unit for this examination.']);
            }
        }

        if ($data['scope_type'] === 'permanent') {
            $data['academic_year_id'] = null;
            $data['semester_id'] = null;
            $data['starts_on'] = null;
            $data['ends_on'] = null;
        }

        if ($data['scope_type'] === 'semester') {
            if (blank($data['academic_year_id'] ?? null) || blank($data['semester_id'] ?? null)) {
                throw ValidationException::withMessages(['semester_id' => 'Select an academic year and semester.']);
            }

            $data['starts_on'] = null;
            $data['ends_on'] = null;
        }

        if ($data['scope_type'] === 'period') {
            if (blank($data['starts_on'] ?? null) || blank($data['ends_on'] ?? null)) {
                throw ValidationException::withMessages(['starts_on' => 'Set the start and end dates for this period examination.']);
            }

            $data['academic_year_id'] = null;
            $data['semester_id'] = null;
        }

        unset($data['owner_type']);

        return $data;
    }
}
