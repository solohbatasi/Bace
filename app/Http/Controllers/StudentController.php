<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Models\CollegeClass;
use App\Models\Course;
use App\Models\Department;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Student::class);

        $filters = $request->only(['search', 'status', 'course_id', 'class_id']);

        return Inertia::render('Students/Index', [
            'students' => Student::query()
                ->with(['department:id,name,code', 'course:id,name,code', 'class:id,name,code'])
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('admission_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['course_id'] ?? null, fn ($query, $courseId) => $query->where('course_id', $courseId))
                ->when($filters['class_id'] ?? null, fn ($query, $classId) => $query->where('class_id', $classId))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'filters' => $filters,
            'statuses' => Student::STATUSES,
            'courses' => Course::orderBy('name')->get(['id', 'name', 'code']),
            'classes' => CollegeClass::orderBy('name')->get(['id', 'name', 'code', 'course_id']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Student::class);

        return Inertia::render('Students/Form', $this->formData());
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->safe()->except(['photo', 'academic_histories']);
            $data['admission_number'] = $data['admission_number'] ?: Student::nextAdmissionNumber();
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;

            if ($request->hasFile('photo')) {
                $data['photo_path'] = $request->file('photo')->store('students/photos', 'public');
            }

            $student = Student::create($data);

            foreach ($request->validated('academic_histories', []) as $history) {
                $student->academicHistories()->create(array_merge($history, [
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]));
            }
        });

        return redirect()->route('students.index')->with('flash.banner', 'Student admitted.');
    }

    public function show(Student $student): Response
    {
        $this->authorize('view', $student);

        return Inertia::render('Students/Show', [
            'student' => $student->load(['department', 'course', 'class', 'academicHistories', 'user:id,name,email']),
        ]);
    }

    public function edit(Student $student): Response
    {
        $this->authorize('update', $student);

        return Inertia::render('Students/Form', array_merge($this->formData(), [
            'student' => $student->load('academicHistories'),
        ]));
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        DB::transaction(function () use ($request, $student): void {
            $data = $request->safe()->except(['photo', 'academic_histories']);
            $data['updated_by'] = $request->user()->id;

            if ($request->hasFile('photo')) {
                if ($student->photo_path) {
                    Storage::disk('public')->delete($student->photo_path);
                }

                $data['photo_path'] = $request->file('photo')->store('students/photos', 'public');
            }

            $student->fill($data)->save();
            $student->academicHistories()->delete();

            foreach ($request->validated('academic_histories', []) as $history) {
                $student->academicHistories()->create(array_merge($history, [
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]));
            }
        });

        return redirect()->route('students.show', $student)->with('flash.banner', 'Student updated.');
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $student->forceFill(['deleted_by' => $request->user()->id])->save();
        $student->delete();

        return redirect()->route('students.index')->with('flash.banner', 'Student deleted.');
    }

    private function formData(): array
    {
        return [
            'statuses' => Student::STATUSES,
            'departments' => Department::orderBy('name')->get(['id', 'name', 'code']),
            'courses' => Course::orderBy('name')->get(['id', 'name', 'code', 'department_id']),
            'classes' => CollegeClass::orderBy('name')->get(['id', 'name', 'code', 'course_id']),
        ];
    }
}
