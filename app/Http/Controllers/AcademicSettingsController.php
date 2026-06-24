<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\CollegeClass;
use App\Models\Course;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AcademicSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        return Inertia::render('Academics/Settings', [
            'academicYears' => AcademicYear::query()
                ->withCount(['semesters'])
                ->orderByDesc('starts_on')
                ->get(['id', 'name', 'starts_on', 'ends_on', 'is_current']),
            'semesters' => Semester::query()
                ->with('academicYear:id,name')
                ->orderByDesc('starts_on')
                ->get(['id', 'academic_year_id', 'name', 'sequence', 'starts_on', 'ends_on', 'is_current']),
            'classes' => CollegeClass::query()
                ->with(['course:id,code,name', 'department:id,code,name', 'academicYear:id,name', 'semester:id,name', 'classLecturer:id,title,first_name,last_name'])
                ->withCount('students')
                ->orderByDesc('created_at')
                ->get(['id', 'course_id', 'department_id', 'academic_year_id', 'semester_id', 'class_lecturer_id', 'code', 'name', 'year_level', 'capacity', 'is_active']),
            'courses' => Course::orderBy('name')->get(['id', 'department_id', 'code', 'name']),
            'departments' => Department::orderBy('name')->get(['id', 'code', 'name']),
            'lecturers' => Lecturer::orderBy('last_name')->get(['id', 'department_id', 'title', 'first_name', 'last_name']),
        ]);
    }

    public function storeAcademicYear(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $data = $this->academicYearData($request);

        DB::transaction(function () use ($request, $data): void {
            if ($data['is_current'] ?? false) {
                AcademicYear::where('is_current', true)->update(['is_current' => false, 'updated_by' => $request->user()->id]);
            }

            AcademicYear::create($data + [
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('flash.banner', 'Academic year created.');
    }

    public function updateAcademicYear(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $data = $this->academicYearData($request, $academicYear);

        DB::transaction(function () use ($request, $academicYear, $data): void {
            if ($data['is_current'] ?? false) {
                AcademicYear::whereKeyNot($academicYear->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false, 'updated_by' => $request->user()->id]);
            }

            $academicYear->update($data + ['updated_by' => $request->user()->id]);
        });

        return back()->with('flash.banner', 'Academic year updated.');
    }

    public function destroyAcademicYear(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $academicYear->forceFill(['deleted_by' => $request->user()->id])->save();
        $academicYear->delete();

        return back()->with('flash.banner', 'Academic year deleted.');
    }

    public function storeSemester(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $data = $this->semesterData($request);

        DB::transaction(function () use ($request, $data): void {
            if ($data['is_current'] ?? false) {
                Semester::where('academic_year_id', $data['academic_year_id'])
                    ->where('is_current', true)
                    ->update(['is_current' => false, 'updated_by' => $request->user()->id]);
            }

            Semester::create($data + [
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('flash.banner', 'Semester created.');
    }

    public function updateSemester(Request $request, Semester $semester): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $data = $this->semesterData($request, $semester);

        DB::transaction(function () use ($request, $semester, $data): void {
            if ($data['is_current'] ?? false) {
                Semester::whereKeyNot($semester->id)
                    ->where('academic_year_id', $data['academic_year_id'])
                    ->where('is_current', true)
                    ->update(['is_current' => false, 'updated_by' => $request->user()->id]);
            }

            $semester->update($data + ['updated_by' => $request->user()->id]);
        });

        return back()->with('flash.banner', 'Semester updated.');
    }

    public function destroySemester(Request $request, Semester $semester): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $semester->forceFill(['deleted_by' => $request->user()->id])->save();
        $semester->delete();

        return back()->with('flash.banner', 'Semester deleted.');
    }

    public function storeClass(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        CollegeClass::create($this->classData($request) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('flash.banner', 'Class created.');
    }

    public function updateClass(Request $request, CollegeClass $class): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $class->update($this->classData($request, $class) + ['updated_by' => $request->user()->id]);

        return back()->with('flash.banner', 'Class updated.');
    }

    public function destroyClass(Request $request, CollegeClass $class): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('classes.manage'), 403);

        $class->forceFill(['deleted_by' => $request->user()->id])->save();
        $class->delete();

        return back()->with('flash.banner', 'Class deleted.');
    }

    private function academicYearData(Request $request, ?AcademicYear $academicYear = null): array
    {
        return $request->validate([
            'name' => ['required', 'max:20', Rule::unique('academic_years')->ignore($academicYear)->whereNull('deleted_at')],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_current' => ['boolean'],
        ]);
    }

    private function semesterData(Request $request, ?Semester $semester = null): array
    {
        return $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'max:80'],
            'sequence' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('semesters')
                    ->where('academic_year_id', $request->input('academic_year_id'))
                    ->ignore($semester)
                    ->whereNull('deleted_at'),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_current' => ['boolean'],
        ]);
    }

    private function classData(Request $request, ?CollegeClass $class = null): array
    {
        return $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
            'class_lecturer_id' => ['nullable', 'exists:lecturers,id'],
            'code' => [
                'required',
                'max:50',
                Rule::unique('classes')
                    ->where('academic_year_id', $request->input('academic_year_id'))
                    ->ignore($class)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'max:255'],
            'year_level' => ['required', 'integer', 'min:1'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);
    }
}
