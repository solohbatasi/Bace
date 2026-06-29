<?php

namespace App\Http\Controllers;

use App\Models\CollegeClass;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\ExaminationResult;
use App\Models\ScoreLevel;
use App\Models\SemesterRegistration;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExaminationResultController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAnyPermission('results.view|examinations.edit|examinations.manage|classes.manage'), 403);

        $filters = $request->only(['department_id', 'course_id', 'subcourse_id', 'unit_id', 'examination_id', 'class_id', 'result_search', 'result_status']);
        $user = $request->user()->loadMissing('student');
        $isLearner = (bool) $user->student && ! $user->hasAnyPermission('examinations.edit|examinations.manage|classes.manage');

        if ($isLearner) {
            return Inertia::render('Academics/ExaminationResults', [
                'isLearner' => true,
                'filters' => $filters,
                'departments' => [],
                'courses' => [],
                'units' => [],
                'classes' => [],
                'examinations' => [],
                'selectedExamination' => null,
                'entries' => null,
                'learnerResults' => ExaminationResult::query()
                    ->with([
                        'examination:id,course_id,subcourse_id,unit_id,academic_year_id,semester_id,code,name,max_score',
                        'examination.course:id,code,name',
                        'examination.subcourse:id,parent_course_id,code,name',
                        'examination.unit:id,course_id,code,name',
                        'examination.academicYear:id,name',
                        'examination.semester:id,name',
                        'registration:id,class_id',
                        'registration.class:id,code,name',
                        'enrollment:id,class_id',
                        'enrollment.class:id,code,name',
                    ])
                    ->where('student_id', $user->student->id)
                    ->whereNotNull('recorded_at')
                    ->when($filters['result_search'] ?? null, fn ($query, $search) => $query->whereHas('examination', fn ($query) => $query
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")))
                    ->latest('recorded_at')
                    ->paginate(20)
                    ->withQueryString()
                    ->through(fn (ExaminationResult $result) => $this->learnerResultPayload($result)),
            ]);
        }

        $selectedExamination = null;
        $entries = null;

        if ($filters['examination_id'] ?? null) {
            $selectedExamination = Examination::with([
                'course:id,code,name,department_id,grading_mode',
                'course.scoreLevels',
                'subcourse:id,parent_course_id,code,name,department_id,grading_mode',
                'subcourse.scoreLevels',
                'unit:id,code,name,course_id,grading_mode',
                'unit.scoreLevels',
                'unit.course:id,parent_course_id,code,name,grading_mode',
                'unit.course.scoreLevels',
                'scoreLevels',
                'results',
            ])->find($filters['examination_id']);

            if ($selectedExamination) {
                $effective = $this->effectiveScoreLevels($selectedExamination);
                $selectedExamination->setAttribute('effective_grading_mode', $effective['grading_mode']);
                $selectedExamination->setAttribute('effective_score_levels', $effective['levels']->values());
                $selectedExamination->setAttribute('score_level_source', $effective['source']);

                if ($filters['class_id'] ?? null) {
                    $entries = $this->paginatedResultEntries($request, $selectedExamination, (int) $filters['class_id']);
                }
            }
        }

        return Inertia::render('Academics/ExaminationResults', [
            'isLearner' => false,
            'filters' => $filters,
            'departments' => Department::orderBy('name')->get(['id', 'code', 'name']),
            'courses' => Course::with(['subcourses' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
                ->orderBy('name')
                ->get(['id', 'parent_course_id', 'department_id', 'code', 'name', 'has_units', 'is_active']),
            'units' => Unit::orderBy('code')->get(['id', 'course_id', 'department_id', 'code', 'name']),
            'classes' => CollegeClass::orderBy('name')->get(['id', 'course_id', 'department_id', 'code', 'name']),
            'examinations' => Examination::with(['course:id,code,name,department_id', 'subcourse:id,parent_course_id,code,name,department_id', 'unit:id,code,name,course_id'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'course_id', 'subcourse_id', 'unit_id', 'code', 'name', 'scope_type', 'academic_year_id', 'semester_id', 'max_score']),
            'selectedExamination' => $selectedExamination,
            'entries' => $entries,
            'learnerResults' => null,
        ]);
    }

    public function store(Request $request, Examination $examination): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission('examinations.edit|examinations.manage|classes.manage'), 403);

        $maxScore = (float) ($examination->max_score ?: 100);
        $data = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'results' => ['required', 'array'],
            'results.*.student_id' => ['required', 'exists:students,id'],
            'results.*.semester_registration_id' => ['nullable', 'exists:semester_registrations,id'],
            'results.*.enrollment_id' => ['nullable', 'exists:enrollments,id'],
            'results.*.score' => ['nullable', 'numeric', 'min:0', "max:{$maxScore}"],
            'results.*.grade' => ['nullable', 'string', 'max:20'],
            'results.*.comment' => ['nullable', 'string', 'max:255'],
        ]);

        $examination->loadMissing(['course.scoreLevels', 'unit.scoreLevels', 'unit.course.scoreLevels', 'scoreLevels', 'results']);
        $eligibleRows = $this->allResultEntries($examination, (int) $data['class_id'])->keyBy('student_id');
        $effective = $this->effectiveScoreLevels($examination);

        DB::transaction(function () use ($request, $examination, $data, $eligibleRows, $effective): void {
            foreach ($data['results'] as $row) {
                $eligible = $eligibleRows->get((int) $row['student_id']);

                if (! $eligible) {
                    continue;
                }

                if (! $examination->can_edit_results && $eligible['has_result']) {
                    continue;
                }

                $score = filled($row['score'] ?? null) ? (float) $row['score'] : null;
                $grade = $row['grade'] ?? null;

                if ($score !== null && blank($grade) && $this->modeUsesGrade($effective['grading_mode']) && $this->modeUsesRange($effective['grading_mode'])) {
                    $grade = $this->gradeForScore($score, $effective['levels']);
                }

                if ($score === null && blank($grade) && blank($row['comment'] ?? null)) {
                    continue;
                }

                $examination->results()->updateOrCreate(
                    ['student_id' => $eligible['student_id']],
                    [
                        'semester_registration_id' => $eligible['semester_registration_id'],
                        'enrollment_id' => $eligible['enrollment_id'],
                        'score' => $score,
                        'grade' => $grade,
                        'comment' => $row['comment'] ?? null,
                        'recorded_at' => now(),
                        'recorded_by' => $request->user()->id,
                        'created_by' => $request->user()->id,
                        'updated_by' => $request->user()->id,
                    ]
                );
            }
        });

        return back()->with('flash.banner', 'Examination results saved.');
    }

    private function effectiveScoreLevels(Examination $examination): array
    {
        $sources = [
            ['source' => 'examination', 'mode' => $examination->grading_mode, 'levels' => $examination->scoreLevels],
        ];

        if ($examination->unit) {
            $sources[] = ['source' => 'unit', 'mode' => $examination->unit->grading_mode, 'levels' => $examination->unit->scoreLevels];
            if ($examination->subcourse) {
                $sources[] = ['source' => 'subcourse', 'mode' => $examination->subcourse->grading_mode, 'levels' => $examination->subcourse->scoreLevels];
            }
            $sources[] = ['source' => 'course', 'mode' => $examination->unit->course?->grading_mode, 'levels' => $examination->unit->course?->scoreLevels ?? collect()];
        } else {
            if ($examination->subcourse) {
                $sources[] = ['source' => 'subcourse', 'mode' => $examination->subcourse->grading_mode, 'levels' => $examination->subcourse->scoreLevels];
            }
            $sources[] = ['source' => 'course', 'mode' => $examination->course?->grading_mode, 'levels' => $examination->course?->scoreLevels ?? collect()];
        }

        foreach ($sources as $source) {
            if ($source['levels'] instanceof Collection && $source['levels']->isNotEmpty()) {
                return [
                    'source' => $source['source'],
                    'grading_mode' => $source['mode'] ?: 'score_levels_with_grades',
                    'levels' => $source['levels']->values(),
                ];
            }
        }

        return [
            'source' => 'none',
            'grading_mode' => $examination->grading_mode ?: 'score_levels_with_grades',
            'levels' => collect(),
        ];
    }

    private function paginatedResultEntries(Request $request, Examination $examination, int $classId)
    {
        $paginator = $this->baseEntryQuery($examination, $classId, $request)
            ->paginate(50)
            ->withQueryString();

        $results = $examination->results->keyBy('student_id');
        $paginator->getCollection()->transform(fn ($row) => $this->resultEntryFromRow($row, $results->get($row->student_id)));

        return $paginator;
    }

    private function allResultEntries(Examination $examination, int $classId): Collection
    {
        $results = $examination->results->keyBy('student_id');

        return $this->baseEntryQuery($examination, $classId)
            ->get()
            ->map(fn ($row) => $this->resultEntryFromRow($row, $results->get($row->student_id)));
    }

    private function baseEntryQuery(Examination $examination, int $classId, ?Request $request = null)
    {
        $search = $request?->input('result_search');
        $status = $request?->input('result_status');

        if ($examination->unit_id) {
            return Enrollment::query()
                ->with(['student:id,admission_number,first_name,last_name', 'class:id,name,code'])
                ->where('unit_id', $examination->unit_id)
                ->where('class_id', $classId)
                ->where('status', 'approved')
                ->when($examination->subcourse_id, fn ($query, $subcourseId) => $query->where('subcourse_id', $subcourseId))
                ->when($search, fn ($query, $search) => $query->whereHas('student', fn ($query) => $query
                    ->where('admission_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")))
                ->when($status === 'scored', fn ($query) => $query->whereHas('examinationResults', fn ($query) => $query->where('examination_id', $examination->id)))
                ->when($status === 'unscored', fn ($query) => $query->whereDoesntHave('examinationResults', fn ($query) => $query->where('examination_id', $examination->id)))
                ->when($examination->scope_type === 'semester', fn ($query) => $query
                    ->where('academic_year_id', $examination->academic_year_id)
                    ->where('semester_id', $examination->semester_id))
                ->orderBy('id');
        }

        return SemesterRegistration::query()
            ->with(['student:id,admission_number,first_name,last_name', 'class:id,name,code'])
            ->where('course_id', $examination->course_id)
            ->when($examination->subcourse_id, fn ($query, $subcourseId) => $query->where('subcourse_id', $subcourseId))
            ->where('class_id', $classId)
            ->where('status', 'approved')
            ->when($search, fn ($query, $search) => $query->whereHas('student', fn ($query) => $query
                ->where('admission_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")))
            ->when($status === 'scored', fn ($query) => $query->whereHas('examinationResults', fn ($query) => $query->where('examination_id', $examination->id)))
            ->when($status === 'unscored', fn ($query) => $query->whereDoesntHave('examinationResults', fn ($query) => $query->where('examination_id', $examination->id)))
            ->when($examination->scope_type === 'semester', fn ($query) => $query
                ->where('academic_year_id', $examination->academic_year_id)
                ->where('semester_id', $examination->semester_id))
            ->orderBy('id');
    }

    private function resultEntryFromRow(Enrollment|SemesterRegistration $row, $result): array
    {
        return [
            'student_id' => $row->student->id,
            'semester_registration_id' => $row instanceof Enrollment ? $row->semester_registration_id : $row->id,
            'enrollment_id' => $row instanceof Enrollment ? $row->id : null,
            'admission_number' => $row->student->admission_number,
            'student_name' => trim($row->student->first_name.' '.$row->student->last_name),
            'class_name' => $row->class?->code.' - '.$row->class?->name,
            'has_result' => (bool) $result,
            'score' => $result?->score,
            'grade' => $result?->grade,
            'comment' => $result?->comment,
        ];
    }

    private function learnerResultPayload(ExaminationResult $result): array
    {
        $examination = $result->examination;
        $target = $examination?->unit ?: ($examination?->subcourse ?: $examination?->course);
        $class = $result->enrollment?->class ?: $result->registration?->class;

        return [
            'id' => $result->id,
            'examination' => $examination,
            'target_label' => trim(($target?->code ?: '').' - '.($target?->name ?: ''), ' -'),
            'class_label' => trim(($class?->code ?: '').' - '.($class?->name ?: ''), ' -'),
            'score' => $result->score,
            'grade' => $result->grade,
            'comment' => $result->comment,
            'recorded_at' => $result->recorded_at,
        ];
    }

    private function gradeForScore(float $score, Collection $levels): ?string
    {
        $level = $levels->first(fn (ScoreLevel $level) => $level->min_score !== null
            && $level->max_score !== null
            && $score >= (float) $level->min_score
            && $score <= (float) $level->max_score);

        return $level?->grade;
    }

    private function modeUsesGrade(string $mode): bool
    {
        return in_array($mode, ['grade_only', 'score_levels_with_grades'], true);
    }

    private function modeUsesRange(string $mode): bool
    {
        return in_array($mode, ['score_levels', 'score_levels_with_grades'], true);
    }
}
