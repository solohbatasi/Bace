<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);

        $students = Student::query()
            ->with(['department:id,name,code', 'course:id,name,code', 'class:id,name,code'])
            ->when($request->query('search'), fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('admission_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('course_id'), fn ($query, $courseId) => $query->where('course_id', $courseId))
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return response()->json($students);
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = DB::transaction(function () use ($request): Student {
            $data = $request->safe()->except(['photo', 'academic_histories']);
            $data['admission_number'] = $data['admission_number'] ?: Student::nextAdmissionNumber();
            $data['created_by'] = $request->user()->id;

            if ($request->hasFile('photo')) {
                $data['photo_path'] = $request->file('photo')->store('students/photos', 'public');
            }

            $student = Student::create($data);
            $this->syncAcademicHistories($student, $request->validated('academic_histories', []), $request->user()->id);

            return $student;
        });

        return response()->json([
            'message' => 'Student admitted.',
            'student' => $student->load(['department', 'course', 'class', 'academicHistories']),
        ], 201);
    }

    public function show(Student $student): JsonResponse
    {
        $this->authorize('view', $student);

        return response()->json([
            'student' => $student->load(['department', 'course', 'class', 'academicHistories', 'user:id,name,email']),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
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

            if ($request->has('academic_histories')) {
                $student->academicHistories()->delete();
                $this->syncAcademicHistories($student, $request->validated('academic_histories', []), $request->user()->id);
            }
        });

        return response()->json([
            'message' => 'Student updated.',
            'student' => $student->fresh()->load(['department', 'course', 'class', 'academicHistories']),
        ]);
    }

    public function destroy(Request $request, Student $student): JsonResponse
    {
        $this->authorize('delete', $student);

        $student->forceFill(['deleted_by' => $request->user()->id])->save();
        $student->delete();

        return response()->json(['message' => 'Student deleted.']);
    }

    private function syncAcademicHistories(Student $student, array $histories, int $userId): void
    {
        foreach ($histories as $history) {
            $student->academicHistories()->create(array_merge($history, [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]));
        }
    }
}
